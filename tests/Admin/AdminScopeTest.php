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

namespace Arnapou\SimpleSite\Tests\Admin;

use Arnapou\SimpleSite\Admin\AdminScope;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminScopeTest extends TestCase
{
    use ConfigTestTrait;

    public function testNotExist(): void
    {
        self::assertNull(AdminScope::tryFrom('@foo'));

        $this->expectException(Problem::class);
        $this->expectExceptionMessage("Invalid scope '@foo'.");
        AdminScope::from('@foo');
    }

    public function testUndefinedPathTemplate(): void
    {
        [, $modele] = self::createConfigTest();
        $config = new Config($modele->name, $modele->path_public, $modele->path_pages, $modele->path_cache);

        $this->expectException(Problem::class);
        $this->expectExceptionMessage("Invalid scope '@templates'.");
        AdminScope::templates->toPath($config);
    }

    public static function dataEnumeration(): Generator
    {
        [, $config] = self::createConfigTest();

        yield [$config, AdminScope::pages, '@pages', $config->path_pages];
        yield [$config, AdminScope::public, '@public', $config->path_public];
        yield [$config, AdminScope::templates, '@templates', $config->path_templates];
    }

    #[DataProvider('dataEnumeration')]
    public function testEnumeration(Config $config, AdminScope $scope, string $name, string $path): void
    {
        self::assertNull(AdminScope::tryFrom('foo'));

        self::assertSame($scope, AdminScope::tryFrom($name));
        self::assertSame($scope, AdminScope::from($name));
        self::assertSame($name, $scope->toString());
        self::assertSame($path, $scope->toPath($config));
    }
}
