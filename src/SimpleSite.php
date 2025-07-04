<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite;

use Arnapou\Psr\Psr14EventDispatcher\PhpHandlers;
use Arnapou\Psr\Psr15HttpHandlers\Exception\NoResponseFound;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr17HttpFactories\HttpFactory;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr7HttpMessage\HtmlResponse;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

require_once __DIR__ . '/../vendor/autoload.php';

final class SimpleSite
{
    private static Core\Container $container;

    /**
     * @throws Core\Problem
     */
    public static function run(string $path_public, string $path_pages, string $path_cache, string $path_data = '', string $path_templates = '', string $path_php = '', string $log_path = '', int $log_max_files = 7, string $log_level = 'notice', string $base_path_root = '', string $base_path_admin = ''): void
    {
        self::handle(new Core\Config($path_public, $path_pages, $path_cache, $path_data, $path_templates, $path_php, $log_path, $log_max_files, $log_level, $base_path_root, $base_path_admin))->send();
    }

    public static function handle(Core\Config $config, ?ServerRequestInterface $request = null): Response
    {
        $request ??= new HttpFactory()->createServerRequestFromGlobals();

        self::container()->registerInstance(Core\Config::class, $config, allowOverride: true);
        self::container()->registerInstance(ServerRequestInterface::class, $request, allowOverride: true);

        self::phpHandlers()->registerAll();

        try {
            self::loadPhpFiles();

            return new Response(self::router()->handle($request));
        } catch (\Throwable $e) {
            if ($e instanceof NoResponseFound) {
                self::logger()->warning('404 Not Found');

                return self::error(404, $e, 'Not Found');
            }

            [$code, $text] = match (true) {
                $e instanceof Core\Problem => [$e->getStatus()->value ?? 500, $e->getStatus()->name ?? 'Internal Server Error'],
                default => [500, 'Internal Server Error'],
            };

            self::logger()->error("$code $text", ['exception' => $e]);

            return self::error($code, $e, $text);
        }
    }

    public static function cache(): Core\Cache
    {
        return self::container()->get(Core\Cache::class);
    }

    public static function config(): Core\Config
    {
        try {
            return self::container()->get(Core\Config::class);
        } catch (NotFoundExceptionInterface) {
            throw new Core\Problem('Config is not initialized, you must start the project running SimpleSite::run().');
        }
    }

    public static function container(): Core\Container
    {
        return self::$container ??= new Core\Container();
    }

    public static function db(): Core\Db
    {
        return self::container()->get(Core\Db::class);
    }

    public static function helper(): Core\Helper
    {
        return self::container()->get(Core\Helper::class);
    }

    public static function logger(): ThrowableLogger
    {
        return self::container()->get(ThrowableLogger::class);
    }

    public static function phpHandlers(): PhpHandlers
    {
        return self::container()->get(PhpHandlers::class);
    }

    public static function router(): HttpRouteHandler
    {
        return self::container()->get(HttpRouteHandler::class);
    }

    public static function twig(): Environment
    {
        return self::container()->get(Environment::class);
    }

    public static function version(): string
    {
        $version = trim(is_file($file = __DIR__ . '/VERSION') ? (string) file_get_contents($file) : '');

        return '' !== $version ? $version : '?';
    }

    private static function loadPhpFiles(): void
    {
        $config = self::config();
        $container = self::container();
        $phpLoader = static function (string $phpfile) {
            $obj = include_once $phpfile;

            if ($obj instanceof PhpCode) {
                $obj->init();
            }
        };

        if (null !== ($pathPhp = $config->path_php)) {
            foreach (self::findPhpFiles($pathPhp) as $file) {
                $phpLoader($file);
            }
        }

        $container->get(Controllers\FallbackController::class)->init();
        $container->get(Controllers\ImagesController::class)->init();
        $container->get(Controllers\StaticController::class)->init();

        if (null !== $config->base_path_admin) {
            $container->get(Admin\AdminLoginController::class)->init();
            $container->get(Admin\AdminMainController::class)->init();
        }
    }

    private static function error(int $code, \Throwable $e, string $text): Response
    {
        $view = match (true) {
            400 <= $code && $code < 500 => 'error.40x.twig',
            default => 'error.50x.twig',
        };

        $context = [
            'exception' => $e,
            'code' => $code,
            'text' => ucfirst(strtr(self::helper()->toSnakeCase($text), ['_' => ' '])),
            'content' => $e instanceof Core\Problem || $e instanceof \Twig\Error\LoaderError ? $e->getMessage() : null,
            'detail' => self::helper()->throwableToText($e),
        ];

        try {
            $html = self::twig()->render("@templates/$view", $context);
        } catch (\Throwable) {
            $html = self::twig()->render("@internal/$view", $context);
        }

        return new HtmlResponse($html, $code);
    }

    /**
     * @return array<string>
     */
    private static function findPhpFiles(string $path): array
    {
        // mandatory to use opendir family functions inside a Phar
        $files = [];
        if (\is_resource($dh = opendir($path))) {
            while ('' !== ($file = (string) readdir($dh))) {
                if (str_ends_with($file, '.php')) {
                    $files[] = $path . '/' . $file;
                }
            }
            closedir($dh);
        }
        sort($files);

        return $files;
    }
}
