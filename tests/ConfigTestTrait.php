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

namespace Arnapou\SimpleSite\Tests;

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Container;
use Arnapou\SimpleSite\SimpleSite;

trait ConfigTestTrait
{
    protected static function resetContainer(): Container
    {
        $container = new Container();

        /**
         * Hack to reset the private static variable of the container.
         *
         * @phpstan-ignore staticClassAccess.privateProperty
         */
        (fn () => static::$container = $container)->call(new SimpleSite());

        return $container;
    }

    protected static function createConfigSite(string $baseUrl = '/'): Config
    {
        return new Config(
            'test',
            path_public: __DIR__ . '/../demo/public',
            path_pages: __DIR__ . '/../demo/pages',
            path_cache: __DIR__ . '/../demo/cache',
            path_data: __DIR__ . '/../demo/data',
            path_templates: __DIR__ . '/../demo/templates',
            path_php: __DIR__ . '/../demo/src',
            base_path_root: $baseUrl,
            base_path_admin: '/admin',
        );
    }

    /**
     * @return array{string, Config}
     */
    protected static function createConfigTest(): array
    {
        @mkdir($dir = '/tmp/' . uniqid('TEST-', true), recursive: true);
        @mkdir("$dir/public", recursive: true);
        @mkdir("$dir/pages", recursive: true);
        @mkdir("$dir/data", recursive: true);
        @mkdir("$dir/templates", recursive: true);
        @mkdir("$dir/php", recursive: true);

        return [
            $dir,
            new Config(
                name: 'name',
                path_public: "$dir/public",
                path_pages: "$dir/pages",
                path_cache: "$dir/cache",
                path_data: "$dir/data",
                path_templates: "$dir/templates",
                path_php: "$dir/php",
                log_path: "$dir/logs",
                log_max_files: -5,
                log_level: 'debug',
                base_path_root: '/base_path_root',
                base_path_admin: '/base_path_admin',
            ),
        ];
    }
}
