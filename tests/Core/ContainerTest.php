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

use Arnapou\Encoder\Encoder;
use Arnapou\PFDB\Storage\StorageInterface;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\SimpleSite\Admin;
use Arnapou\SimpleSite\Core;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\LoaderInterface;

class ContainerTest extends TestCase
{
    use ConfigTestTrait;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        [, $config] = self::createConfigTest();
        $this->container = new Core\Container();
        $this->container->registerInstance(Core\Config::class, $config);
        $this->container->registerInstance(ServerRequestInterface::class, new ServerRequest('GET', '/'));
    }

    public static function dataServiceExist(): \Generator
    {
        yield [Admin\AdminConfig::class];
        yield [Admin\AdminLoginController::class];
        yield [Admin\AdminMainController::class];
        yield [Admin\AdminSession::class];
        yield [Core\Cache::class];
        yield [Core\Config::class];
        yield [Core\Container::class];
        yield [Core\DbStorage::class];
        yield [Core\Helper::class];
        yield [Core\Image::class];
        yield [Core\LogFormatter::class];
        yield [Core\Sitemap::class];
        yield [Core\TwigEnvironment::class];
        yield [Core\TwigExtension::class];
        yield [Core\TwigLoader::class];
        yield [Core\UrlEncoder::class];
        yield [CacheInterface::class];
        yield [ContainerInterface::class];
        yield [Encoder::class];
        yield [Environment::class];
        yield [ExtensionInterface::class];
        yield [LoaderInterface::class];
        yield [LoggerInterface::class];
        yield [StorageInterface::class];
        yield [ThrowableLogger::class];
        yield [ThrowableLogger::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('dataServiceExist')]
    public function testServiceExist(string $class): void
    {
        self::assertInstanceOf($class, $this->container->get($class));
        self::assertTrue($this->container->has($class), $class);
    }
}
