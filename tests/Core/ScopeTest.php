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

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Core\Scope;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    use ConfigTestTrait;

    public function testNotExist(): void
    {
        self::assertNull(Scope::tryFrom('@foo'));

        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Invalid scope "@foo"');
        Scope::from('@foo');
    }

    public static function dataEnumeration(): \Generator
    {
        [, $config] = self::createConfigTest();

        yield [$config, Scope::pages, '@pages', $config->path_pages];
        yield [$config, Scope::public, '@public', $config->path_public];
        yield [$config, Scope::templates, '@templates', $config->path_templates];
    }

    #[DataProvider('dataEnumeration')]
    public function testEnumeration(Config $config, Scope $scope, string $name, string $path): void
    {
        self::assertNull(Scope::tryFrom('foo'));

        self::assertSame($scope, Scope::tryFrom($name));
        self::assertSame($scope, Scope::from($name));
        self::assertSame($name, $scope->toString());
        self::assertSame($path, $scope->toPath($config));
    }

    public function testUndefinedPathTemplate(): void
    {
        $config = new Config('/tmp', '/tmp', '/tmp');

        self::assertNull(Scope::templates->toPath($config, false));

        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Invalid scope "@templates".');
        Scope::templates->toPath($config);
    }
}
