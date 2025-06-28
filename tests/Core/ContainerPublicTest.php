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

namespace Arnapou\SimpleSite\Tests\Core;

use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Container;
use Arnapou\SimpleSite\Core\ContainerPublic;
use Arnapou\SimpleSite\Core\DbPublic;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ContainerPublicTest extends TestCase
{
    use ConfigTestTrait;

    private ContainerPublic $container;

    protected function setUp(): void
    {
        [, $config] = self::createConfigTest();
        $this->container = new ContainerPublic($container = new Container());
        $container->registerInstance(Config::class, $config);
        $container->registerInstance(ServerRequestInterface::class, new ServerRequest('GET', '/'));
    }

    public static function dataServiceExist(): \Generator
    {
        yield ['config', Config::class];
        yield ['db', DbPublic::class];
        yield ['logger', ThrowableLogger::class];
        yield ['request', ServerRequestInterface::class];
        yield ['version', 'string'];
    }

    #[DataProvider('dataServiceExist')]
    public function testServiceExist(string $name, string $class): void
    {
        $checkType = static fn (mixed $value) => \is_object($value)
            ? $value instanceof $class
            : $class === get_debug_type($value);

        self::assertTrue($checkType($this->container->get($name)));
        self::assertTrue($this->container->has($name));

        self::assertTrue($checkType($this->container->$name)); // @phpstan-ignore property.dynamicName
        self::assertTrue(isset($this->container->$name)); // @phpstan-ignore property.dynamicName
    }

    public function testReadOnly(): void
    {
        $this->expectExceptionObject(new \RuntimeException('The container is Readonly.'));
        $this->container->foo_bar = new \stdClass(); // @phpstan-ignore property.notFound
    }
}
