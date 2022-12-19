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

namespace Arnapou\SimpleSite\Services;

use Arnapou\PFDB\Exception\DirectoryNotFoundException;
use Arnapou\PFDB\Exception\InvalidTableNameException;
use Arnapou\PFDB\Storage\CachedFileStorage;
use Arnapou\PFDB\Storage\YamlFileStorage;
use Arnapou\SimpleSite\Core\Assert;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Arnapou\SimpleSite\Core\Utils;
use Arnapou\SimpleSite\Exception\SimplesiteProblem;

final class Database implements ServiceFactory
{
    public static function factory(ServiceContainer $container): \Arnapou\PFDB\Database
    {
        $config = $container->config();

        try {
            return new \Arnapou\PFDB\Database(
                new CachedFileStorage(
                    new YamlFileStorage(
                        Assert::nonEmptyConfigPath('path_data', $config->path_data)
                    ),
                    Utils::mkdir($config->path_cache . '/database')
                )
            );
        } catch (DirectoryNotFoundException|InvalidTableNameException $e) {
            throw new SimplesiteProblem($e->getMessage(), 0, $e);
        }
    }

    public static function aliases(): array
    {
        return ['db'];
    }
}
