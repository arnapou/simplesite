<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite;

use Arnapou\PFDB\Database;
use Arnapou\Psr\Psr11Container\ServiceLocator;
use Arnapou\Psr\Psr14EventDispatcher\PhpHandlers;
use Arnapou\Psr\Psr15HttpHandlers\Exception\NoResponseFound;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr17HttpFactories\HttpFactory;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr7HttpMessage\HtmlResponse;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Container;
use Arnapou\SimpleSite\Core\Counter;
use Arnapou\SimpleSite\Core\Image;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Core\TwigExtension;
use Arnapou\SimpleSite\Core\Utils;
use Arnapou\SimpleSite\Core\YamlContext;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

require __DIR__ . '/../vendor/autoload.php';

final class SimpleSite
{
    private static Container $container;

    /**
     * @throws Problem
     */
    public static function run(
        string $name,
        string $path_public,
        string $path_cache,
        string $path_data = '',
        string $path_templates = '',
        string $path_php = '',
        string $log_path = '',
        int $log_max_files = 7,
        string $log_level = 'notice',
        string $base_path_url = '/',
    ): void {
        $config = new Config($name, $path_public, $path_cache, $path_data, $path_templates, $path_php, $log_path, $log_max_files, $log_level, $base_path_url);

        self::handle($config)->send();
    }

    public static function handle(Config $config, ?ServerRequestInterface $request = null): Response
    {
        $request ??= new HttpFactory()->createServerRequestFromGlobals();

        self::container()->registerInstance(Config::class, $config);
        self::container()->registerInstance(ServerRequestInterface::class, $request);

        self::phpHandlers()->registerAll();
        self::phpHandlers()->eventDispatcher->listen(self::throwableHandler(...));

        try {
            self::loadPhpFiles();

            return new Response(self::router()->handle($request));
        } catch (NoResponseFound $e) {
            self::logger()->warning('404 Not Found');

            return self::error(404, $e);
        } catch (Throwable $e) {
            self::logger()->error('500 Internal Error', ['exception' => $e]);

            return self::error(500, $e);
        }
    }

    public static function config(): Config
    {
        try {
            return self::container()->get(Config::class);
        } catch (NotFoundExceptionInterface) {
            throw new Problem('Config is not initialized, you must start the project running SimpleSite::run().');
        }
    }

    public static function container(): ServiceLocator
    {
        return self::$container ??= new Container();
    }

    public static function counter(): Counter
    {
        return self::container()->get(Counter::class);
    }

    public static function database(): Database
    {
        return self::container()->get(Database::class);
    }

    public static function image(): Image
    {
        return self::container()->get(Image::class);
    }

    public static function logger(): ThrowableLogger
    {
        return self::container()->get(ThrowableLogger::class);
    }

    public static function phpHandlers(): PhpHandlers
    {
        return self::container()->get(PhpHandlers::class);
    }

    public static function request(): ServerRequestInterface
    {
        try {
            return self::container()->get(ServerRequestInterface::class);
        } catch (NotFoundExceptionInterface) {
            throw new Problem('Request is not initialized, you must start the project running SimpleSite::run().');
        }
    }

    public static function router(): HttpRouteHandler
    {
        return self::container()->get(HttpRouteHandler::class);
    }

    public static function twigEnvironment(): Environment
    {
        return self::container()->get(Environment::class);
    }

    public static function twigExtension(): TwigExtension
    {
        return self::container()->get(TwigExtension::class);
    }

    public static function twigLoader(): LoaderInterface
    {
        return self::container()->get(LoaderInterface::class);
    }

    public static function yamlContext(): YamlContext
    {
        return self::container()->get(YamlContext::class);
    }

    private static function loadPhpFiles(): void
    {
        $loadPhpFile = static function (string $phpfile) {
            $obj = include_once $phpfile;

            if ($obj instanceof PhpCode) {
                $obj->init();
            }
        };

        if ('' !== ($pathPhp = self::config()->path_php)) {
            foreach (Utils::findPhpFiles($pathPhp) as $file) {
                $loadPhpFile($file);
            }
        }

        new Controllers\FallbackController()->init();
        new Controllers\ImagesController()->init();
        new Controllers\StaticController()->init();
    }

    private static function throwableHandler(Throwable $throwable): void
    {
        $text = '';
        while ($throwable) {
            $text .= '  class: ' . $throwable::class . "\n";
            $text .= 'message: ' . $throwable->getMessage() . "\n";
            $text .= '   file: ' . $throwable->getFile() . "\n";
            $text .= '   line: ' . $throwable->getLine() . "\n";
            if (0 !== $throwable->getCode()) {
                $text .= '   code: ' . $throwable->getCode() . "\n";
            }
            $text .= '  trace: ' . ltrim(
                implode(
                    "\n",
                    array_map(
                        static fn (string $line): string => '         ' . trim($line),
                        explode("\n", trim($throwable->getTraceAsString())),
                    ),
                ),
            ) . "\n";
            if (null !== ($throwable = $throwable->getPrevious())) {
                $text .= "\n";
            }
        }

        echo 'cli' === \PHP_SAPI ? $text : <<<HTML
            <pre style="color: red"><div class="alert alert-danger" role="alert">$text</div></pre>
            HTML;
    }

    private static function error(int $code, Throwable $exception): Response
    {
        $context = [
            'exception' => $exception,
            'code' => $code,
        ];

        if (
            $exception instanceof Problem
            || $exception instanceof \Twig\Error\LoaderError
        ) {
            $context['content'] = $exception->getMessage();
        }

        try {
            $html = self::twigEnvironment()->render("@templates/error.$code.twig", $context);
        } catch (Throwable) {
            $html = self::twigEnvironment()->render("@internal/error.$code.twig", $context);
        }

        return new HtmlResponse($html, $code);
    }
}
