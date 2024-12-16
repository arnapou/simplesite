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

use Arnapou\SimpleSite\Admin\AdminConfig;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Container;
use Arnapou\SimpleSite\Core\Sitemap;
use Arnapou\SimpleSite\Core\TwigExtension;
use Arnapou\SimpleSite\SimpleSite;

trait ConfigTestTrait
{
    private static function resetContainer(): Container
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

    private static function createConfigDemo(string $baseUrl = '/'): Config
    {
        return new Config(
            path_public: __DIR__ . '/../demo/public',
            path_pages: __DIR__ . '/../demo/pages',
            path_cache: '/tmp/simplesite',
            path_data: __DIR__ . '/../demo/data',
            path_templates: __DIR__ . '/../demo/templates',
            path_php: __DIR__ . '/../demo/src',
            log_path: __DIR__ . '/../demo/log',
            base_path_root: $baseUrl,
            base_path_admin: '/admin',
        );
    }

    /**
     * @return array{string, Config}
     */
    private static function createConfigTest(): array
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
                path_public: "$dir/public",
                path_pages: "$dir/pages",
                path_cache: "$dir/cache",
                path_data: "$dir/data",
                path_templates: "$dir/templates",
                path_php: "$dir/php",
                log_path: "$dir/log",
                log_max_files: -5,
                log_level: 'debug',
                base_path_root: '/base_path_root',
                base_path_admin: '/base_path_admin',
            ),
        ];
    }

    private function getSitemap(): Sitemap
    {
        return SimpleSite::container()->get(Sitemap::class);
    }

    private function getTwigExtension(): TwigExtension
    {
        return SimpleSite::container()->get(TwigExtension::class);
    }

    private function getAdminConfig(): AdminConfig
    {
        return SimpleSite::container()->get(AdminConfig::class);
    }
}
