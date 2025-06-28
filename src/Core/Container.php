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

namespace Arnapou\SimpleSite\Core;

use Arnapou\Encoder\Encoder;
use Arnapou\PFDB\Storage\StorageInterface;
use Arnapou\Psr\Psr11Container\ServiceLocator;
use Arnapou\Psr\Psr3Logger\Decorator\MinimumLevelLogger;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr3Logger\FileLogger;
use Arnapou\Psr\Psr3Logger\Utils\Rotation;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\LoaderInterface;

/**
 * Internal SimpleSite container.
 */
final class Container extends ServiceLocator
{
    public function __construct()
    {
        // Self
        $this->registerInstance(ContainerInterface::class, $this);
        $this->registerInstance(__CLASS__, $this);

        // DI factories
        $this->registerFactory(ThrowableLogger::class, $this->factoryLogger(...));

        // Class aliases
        $this->registerAlias(LoggerInterface::class, ThrowableLogger::class);
        $this->registerAlias(CacheInterface::class, Cache::class);
        $this->registerAlias(StorageInterface::class, DbStorage::class);
        $this->registerAlias(Environment::class, TwigEnvironment::class);
        $this->registerAlias(LoaderInterface::class, TwigLoader::class);
        $this->registerAlias(ExtensionInterface::class, TwigExtension::class);
        $this->registerAlias(Encoder::class, UrlEncoder::class);
    }

    private function factoryLogger(): ThrowableLogger
    {
        $config = $this->get(Config::class);

        return new ThrowableLogger(
            new MinimumLevelLogger(
                new FileLogger(
                    $config->log_path,
                    'site',
                    Rotation::EveryDay,
                    $config->log_max_files,
                    0o777,
                    logFormatter: new LogFormatter($this),
                ),
                $config->log_level,
            ),
        );
    }
}
