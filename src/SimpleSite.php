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
use Arnapou\PFDB\Exception\DirectoryNotFoundException;
use Arnapou\PFDB\Exception\InvalidTableNameException;
use Arnapou\PFDB\Storage\CachedFileStorage;
use Arnapou\PFDB\Storage\PhpFileStorage;
use Arnapou\PFDB\Storage\YamlFileStorage;
use Arnapou\Psr\Psr11Container\ServiceLocator;
use Arnapou\Psr\Psr14EventDispatcher\ClassEventDispatcher;
use Arnapou\Psr\Psr14EventDispatcher\PhpHandlers;
use Arnapou\Psr\Psr15HttpHandlers\Exception\NoResponseFound;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr16SimpleCache\Decorated\GcPrunableSimpleCache;
use Arnapou\Psr\Psr16SimpleCache\FileSimpleCache;
use Arnapou\Psr\Psr17HttpFactories\HttpFactory;
use Arnapou\Psr\Psr3Logger\Decorator\ContextLogger;
use Arnapou\Psr\Psr3Logger\Decorator\MinimumLevelLogger;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr3Logger\FileLogger;
use Arnapou\Psr\Psr3Logger\Formatter\DefaultLogFormatter;
use Arnapou\Psr\Psr3Logger\Utils\Rotation;
use Arnapou\Psr\Psr7HttpMessage\HtmlResponse;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\ContainerWithMagicGetters;
use Arnapou\SimpleSite\Core\Counter;
use Arnapou\SimpleSite\Core\Image;
use Arnapou\SimpleSite\Core\LogContextFormatter;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Core\TwigExtension;
use Arnapou\SimpleSite\Core\Utils;
use Arnapou\SimpleSite\Core\YamlContext;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

require __DIR__ . '/../vendor/autoload.php';

final class SimpleSite
{
    /**
     * @var array{
     *     config?: Config,
     *     container?: ServiceLocator,
     *     counter?: Counter,
     *     database?: Database,
     *     image?: Image,
     *     logger?: ThrowableLogger,
     *     phpHandlers?: PhpHandlers,
     *     request?: ServerRequestInterface,
     *     router?: HttpRouteHandler,
     *     twigEnvironment?: Environment,
     *     twigExtension?: TwigExtension,
     *     twigLoader?: LoaderInterface,
     *     yamlContext?: YamlContext,
     * }
     */
    private static array $instances = [];

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

    public static function config(): Config
    {
        return self::$instances['config']
            ?? throw new Problem('Config is not initialized, you must start the project running SimpleSite::run().');
    }

    public static function container(): ServiceLocator
    {
        return self::$instances['container'] ??= (static function () {
            $container = new ServiceLocator();
            $container->registerFactory('counter', self::counter(...));
            $container->registerFactory('db', self::database(...));
            $container->registerFactory('database', self::database(...));
            $container->registerFactory('img', self::image(...));
            $container->registerFactory('image', self::image(...));
            $container->registerFactory('logger', self::logger(...));
            $container->registerFactory('phpHandlers', self::phpHandlers(...));
            $container->registerFactory('request', self::request(...));
            $container->registerFactory('router', self::router(...));
            $container->registerFactory('twig', self::twigEnvironment(...));
            $container->registerFactory('twigEnvironment', self::twigEnvironment(...));
            $container->registerFactory('twigExtension', self::twigExtension(...));
            $container->registerFactory('twigLoader', self::twigLoader(...));
            $container->registerFactory('yamlContext', self::yamlContext(...));

            return $container;
        })();
    }

    public static function counter(): Counter
    {
        return self::$instances['counter'] ??= new Counter(
            new PhpFileStorage(
                self::config()->pathData(),
                'compteur',
            ),
        );
    }

    public static function database(): Database
    {
        return self::$instances['database'] ??= (static function () {
            try {
                return new Database(
                    new CachedFileStorage(
                        new YamlFileStorage(self::config()->pathData()),
                        self::config()->pathCache('database'),
                    ),
                );
            } catch (DirectoryNotFoundException|InvalidTableNameException $e) {
                throw new Problem($e->getMessage(), 0, $e);
            }
        })();
    }

    public static function image(): Image
    {
        return self::$instances['image'] ??= new Image(
            self::logger(),
            new GcPrunableSimpleCache(
                new FileSimpleCache(
                    self::config()->pathCache('images'),
                    86400 * 30,
                ),
                1,
                1000,
            ),
            self::config()->path_public,
        );
    }

    public static function logger(): ThrowableLogger
    {
        return self::$instances['logger'] ??= (static function () {
            $context = new ContextLogger(
                new FileLogger(
                    self::config()->log_path,
                    'site',
                    Rotation::EveryDay,
                    self::config()->log_max_files,
                    0o777,
                    logFormatter: new DefaultLogFormatter('Y-m-d H:i:s', new LogContextFormatter()),
                ),
            );
            try {
                $context->addContext(['url' => (string) self::request()->getUri()]);
                $context->addContext(['ip' => $_SERVER['REMOTE_ADDR'] ?? '?']);

                if (isset($_SERVER['HTTP_REFERER'])) {
                    $context->addContext(['referer' => $_SERVER['HTTP_REFERER']]);
                }
            } catch (Throwable) {
            }

            return new ThrowableLogger(new MinimumLevelLogger($context, self::config()->log_level));
        })();
    }

    public static function phpHandlers(): PhpHandlers
    {
        return self::$instances['phpHandlers'] ??= new PhpHandlers(self::logger());
    }

    public static function request(): ServerRequestInterface
    {
        return self::$instances['request'] ??= (new HttpFactory())->createServerRequestFromGlobals();
    }

    public static function router(): HttpRouteHandler
    {
        return self::$instances['router'] ??= new HttpRouteHandler(new ClassEventDispatcher(logger: self::logger()));
    }

    public static function twigEnvironment(): Environment
    {
        return self::$instances['twigEnvironment'] ??= (static function () {
            $environment = new Environment(
                self::twigLoader(),
                [
                    'debug' => true,
                    'charset' => 'UTF-8',
                    'strict_variables' => false,
                    'autoescape' => 'html',
                    'cache' => self::config()->pathCache('twig'),
                    'auto_reload' => true,
                    'optimizations' => -1,
                ],
            );
            $environment->addExtension(new DebugExtension());
            $environment->addExtension(self::twigExtension());

            return $environment;
        })();
    }

    public static function twigExtension(): TwigExtension
    {
        return self::$instances['twigExtension'] ??= new TwigExtension(new ContainerWithMagicGetters(self::container()));
    }

    public static function twigLoader(): LoaderInterface
    {
        return self::$instances['twigLoader'] ??= (static function () {
            $loader = new FilesystemLoader();

            /** @var array<string, string> $namespaces */
            $namespaces = [
                $loader::MAIN_NAMESPACE => self::config()->path_public,
                'internal' => __DIR__ . '/Views',
                'templates' => self::config()->path_templates,
                'data' => self::config()->path_data,
                'php' => self::config()->path_php,
                'public' => self::config()->path_public,
                'logs' => self::config()->log_path,
            ];

            foreach ($namespaces as $namespace => $path) {
                if ('' !== $path) {
                    $loader->addPath($path, $namespace);
                }
            }

            return $loader;
        })();
    }

    public static function yamlContext(): YamlContext
    {
        return self::$instances['yamlContext'] ??= new YamlContext();
    }

    public static function handle(Config $config, ?ServerRequestInterface $request = null): Response
    {
        self::container()->registerInstance('config', self::$instances['config'] = $config);
        self::phpHandlers()->registerAll();
        self::phpHandlers()->eventDispatcher->listen(self::throwableHandler(...));

        try {
            self::loadPhpFiles();

            return new Response(self::router()->handle($request ?? self::request()));
        } catch (NoResponseFound $e) {
            self::logger()->warning('404 Not Found');

            return self::error(404, $e);
        } catch (Throwable $e) {
            self::logger()->error('500 Internal Error', ['exception' => $e]);

            return self::error(500, $e);
        }
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

        (new Controllers\FallbackController())->init();
        (new Controllers\ImagesController())->init();
        (new Controllers\StaticController())->init();
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
