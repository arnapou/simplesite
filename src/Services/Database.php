<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Services;

use Arnapou\PFDB\Storage\CachedFileStorage;
use Arnapou\PFDB\Storage\YamlFileStorage;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Arnapou\SimpleSite\Utils;

class Database implements ServiceFactory
{
    public static function factory(ServiceContainer $container)
    {
        $pathData  = $container->Config()->path_data();
        $pathCache = $container->Config()->path_cache() . '/database';

        Utils::mkdir($pathCache);

        $database = new \Arnapou\PFDB\Database(
            new CachedFileStorage(
                new YamlFileStorage($pathData),
                $pathCache
            )
        );

        return $database;
    }

    public static function aliases(): array
    {
        return ['db'];
    }
}
