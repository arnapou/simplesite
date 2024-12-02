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

namespace Arnapou\SimpleSite\Tests\Core;

use Arnapou\PFDB\Database;
use Arnapou\Psr\Psr14EventDispatcher\PhpHandlers;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Container;
use Arnapou\SimpleSite\Core\Counter;
use Arnapou\SimpleSite\Core\Image;
use Arnapou\SimpleSite\Core\TwigExtension;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class ContainerTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        @mkdir($dir = '/tmp/TEST-SITE', recursive: true);
        @mkdir("$dir/public", recursive: true);
        @mkdir("$dir/data", recursive: true);
        @mkdir("$dir/templates", recursive: true);
        @mkdir("$dir/php", recursive: true);

        $this->config = new Config(
            name: 'name',
            path_public: "$dir/public",
            path_cache: "$dir/cache",
            path_data: "$dir/data",
            path_templates: "$dir/templates",
            path_php: "$dir/php",
            log_path: "$dir/logs",
            log_max_files: -5,
            log_level: 'debug',
            base_path_url: '/foo',
        );
    }

    public function testServiceExist(): void
    {
        $container = new Container();
        $container->registerInstance(Config::class, $this->config);
        $container->registerInstance(ServerRequestInterface::class, new ServerRequest('GET', '/'));

        self::assertInstanceOf(Config::class, $container->get('config'));
        self::assertInstanceOf(ContainerInterface::class, $container->get('container'));
        self::assertInstanceOf(Counter::class, $container->get('counter'));
        self::assertInstanceOf(Database::class, $container->get('db'));
        self::assertInstanceOf(Database::class, $container->get('database'));
        self::assertInstanceOf(Image::class, $container->get('img'));
        self::assertInstanceOf(Image::class, $container->get('image'));
        self::assertInstanceOf(ThrowableLogger::class, $container->get('logger'));
        self::assertInstanceOf(PhpHandlers::class, $container->get('phpHandlers'));
        self::assertInstanceOf(ServerRequestInterface::class, $container->get('request'));
        self::assertInstanceOf(HttpRouteHandler::class, $container->get('router'));
        self::assertInstanceOf(Environment::class, $container->get('twig'));
        self::assertInstanceOf(Environment::class, $container->get('twigEnvironment'));
        self::assertInstanceOf(TwigExtension::class, $container->get('twigExtension'));
        self::assertInstanceOf(LoaderInterface::class, $container->get('twigLoader'));
    }
}
