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

use Arnapou\Psr\Psr3Logger\Utils\Psr3Level;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Closure;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    use ConfigTestTrait;

    private const string EXIST = '/tmp/test';

    protected function setUp(): void
    {
        @mkdir(self::EXIST, recursive: true);
    }

    public static function dataEmptyProperty(): Generator
    {
        // mandatory properties
        yield ['path_public', fn () => new Config(name: 'name', path_public: '', path_pages: self::EXIST, path_cache: self::EXIST)];
        yield ['path_pages', fn () => new Config(name: 'name', path_public: self::EXIST, path_pages: '', path_cache: self::EXIST)];
        yield ['path_cache', fn () => new Config(name: 'name', path_public: self::EXIST, path_pages: self::EXIST, path_cache: '')];
    }

    #[DataProvider('dataEmptyProperty')]
    public function testEmptyProperty(string $name, Closure $closure): void
    {
        $this->expectExceptionObject(Problem::emptyVariable($name));
        $closure();
    }

    public static function dataPathDoesNotExists(): Generator
    {
        // mandatory properties
        yield [$tmp = uniqid('/tmp/', true), fn () => new Config(name: 'name', path_public: $tmp, path_pages: self::EXIST, path_cache: self::EXIST)];
        yield [$tmp = uniqid('/tmp/', true), fn () => new Config(name: 'name', path_public: self::EXIST, path_pages: $tmp, path_cache: self::EXIST)];

        // optional properties
        yield [$tmp = uniqid('/tmp/', true), fn () => new Config('name', self::EXIST, self::EXIST, self::EXIST, path_data: $tmp)];
        yield [$tmp = uniqid('/tmp/', true), fn () => new Config('name', self::EXIST, self::EXIST, self::EXIST, path_templates: $tmp)];
        yield [$tmp = uniqid('/tmp/', true), fn () => new Config('name', self::EXIST, self::EXIST, self::EXIST, path_php: $tmp)];
    }

    #[DataProvider('dataPathDoesNotExists')]
    public function testPathDoesNotExists(string $path, Closure $closure): void
    {
        $this->expectExceptionObject(Problem::pathNotExists($path));
        $closure();
    }

    public static function dataBasePathUrl(): Generator
    {
        yield ['/', ''];
        yield ['/', '/'];
        yield ['/foo/', '/foo'];
        yield ['/foo/', '/foo/'];
        yield ['/foo/', 'foo'];
        yield ['/foo/', 'foo/'];
    }

    #[DataProvider('dataBasePathUrl')]
    public function testBasePathUrl(string $expected, string $basePath): void
    {
        $config = new Config('name', self::EXIST, self::EXIST, self::EXIST, base_path_root: $basePath);

        self::assertSame($expected, $config->base_path_root);
    }

    public static function dataBasePathAdmin(): Generator
    {
        yield [null, ''];
        yield [null, '/'];
        yield ['/foo/', '/foo'];
        yield ['/foo/', '/foo/'];
        yield ['/foo/', 'foo'];
        yield ['/foo/', 'foo/'];
    }

    #[DataProvider('dataBasePathAdmin')]
    public function testBasePathAdmin(?string $expected, string $basePath): void
    {
        $config = new Config('name', self::EXIST, self::EXIST, self::EXIST, base_path_admin: $basePath);

        self::assertSame($expected, $config->base_path_admin);
    }

    public function testGlobalSuccess(): void
    {
        [$dir, $config] = self::createConfigTest();

        self::assertSame('name', $config->name);
        self::assertSame("$dir/public", $config->path_public);
        self::assertSame("$dir/pages", $config->path_pages);
        self::assertSame("$dir/cache", $config->path_cache);
        self::assertSame("$dir/data", $config->path_data);
        self::assertSame("$dir/templates", $config->path_templates);
        self::assertSame("$dir/php", $config->path_php);
        self::assertSame("$dir/log", $config->log_path);
        self::assertSame(0, $config->log_max_files);
        self::assertSame(Psr3Level::Debug, $config->log_level);
        self::assertSame('/base_path_root/', $config->base_path_root);
        self::assertSame('/base_path_admin/', $config->base_path_admin);

        self::assertSame("$dir/cache/foo", $config->pathCache('foo'));
    }
}
