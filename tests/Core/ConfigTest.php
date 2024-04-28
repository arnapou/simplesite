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
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testFailurePathPublic(): void
    {
        $this->expectExceptionObject(Problem::emptyVariable('path_public'));
        new Config(name: 'name', path_public: '', path_cache: '');
    }

    public function testFailurePathPublicNotExists(): void
    {
        $this->expectExceptionObject(Problem::pathNotExists('foo'));
        new Config(name: 'name', path_public: 'foo', path_cache: '');
    }

    public function testFailurePathCache(): void
    {
        $this->expectExceptionObject(Problem::emptyVariable('path_cache'));
        new Config(name: 'name', path_public: '/tmp', path_cache: '');
    }

    public function testFailurePathDataNotExists(): void
    {
        $this->expectExceptionObject(Problem::pathNotExists('foo'));
        new Config(name: 'name', path_public: '/tmp', path_cache: '/tmp', path_data: 'foo');
    }

    public function testFailurePathTemplatesNotExists(): void
    {
        $this->expectExceptionObject(Problem::pathNotExists('foo'));
        new Config(name: 'name', path_public: '/tmp', path_cache: '/tmp', path_templates: 'foo');
    }

    public function testFailurePathPhpNotExists(): void
    {
        $this->expectExceptionObject(Problem::pathNotExists('foo'));
        new Config(name: 'name', path_public: '/tmp', path_cache: '/tmp', path_php: 'foo');
    }

    public function testSuccess(): void
    {
        @mkdir($dir = '/tmp/TEST-SITE', recursive: true);
        @mkdir("$dir/public", recursive: true);
        @mkdir("$dir/data", recursive: true);
        @mkdir("$dir/templates", recursive: true);
        @mkdir("$dir/php", recursive: true);

        $config = new Config(
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

        self::assertSame('name', $config->name);
        self::assertSame("$dir/public", $config->path_public);
        self::assertSame("$dir/cache", $config->path_cache);
        self::assertSame("$dir/data", $config->path_data);
        self::assertSame("$dir/templates", $config->path_templates);
        self::assertSame("$dir/php", $config->path_php);
        self::assertSame("$dir/logs", $config->log_path);
        self::assertSame(0, $config->log_max_files);
        self::assertSame(Psr3Level::Debug, $config->log_level);
        self::assertSame('/foo', $config->base_path_url);

        self::assertSame("$dir/data", $config->pathData());
        self::assertSame("$dir/cache/foo", $config->pathCache('foo'));
    }
}
