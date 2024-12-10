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

namespace Arnapou\SimpleSite\Core;

use Arnapou\Encoder\Encoder;
use Arnapou\PFDB\Database;
use Arnapou\PFDB\Storage\StorageInterface;
use Arnapou\Psr\Psr11Container\ServiceLocator;
use Arnapou\Psr\Psr14EventDispatcher\PhpHandlers;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr3Logger\Decorator\MinimumLevelLogger;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr3Logger\FileLogger;
use Arnapou\Psr\Psr3Logger\Utils\Rotation;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

/**
 * Allows to expose a PSR-11 Container with magic getters for Twig.
 *
 * `$container->NAME` is equivalent to `$container->get('NAME')`.
 */
final class Container extends ServiceLocator
{
    public function __construct()
    {
        // Named aliases
        $this->registerAlias('config', Config::class);
        $this->registerAlias('container', ContainerInterface::class);
        $this->registerAlias('db', Database::class);
        $this->registerAlias('database', Database::class);
        $this->registerAlias('img', Image::class);
        $this->registerAlias('image', Image::class);
        $this->registerAlias('logger', ThrowableLogger::class);
        $this->registerAlias('phpHandlers', PhpHandlers::class);
        $this->registerAlias('request', ServerRequestInterface::class);
        $this->registerAlias('router', HttpRouteHandler::class);
        $this->registerAlias('twig', Environment::class);
        $this->registerAlias('twigEnvironment', Environment::class);
        $this->registerAlias('twigExtension', TwigExtension::class);
        $this->registerAlias('twigLoader', LoaderInterface::class);

        // Class aliases
        $this->registerAlias(LoggerInterface::class, ThrowableLogger::class);
        $this->registerAlias(CacheInterface::class, Cache::class);
        $this->registerAlias(StorageInterface::class, DatabaseStorage::class);
        $this->registerAlias(Environment::class, TwigEnvironment::class);
        $this->registerAlias(LoaderInterface::class, TwigLoader::class);
        $this->registerAlias(Encoder::class, UrlEncoder::class);

        // Self
        $this->registerInstance(ContainerInterface::class, $this);

        // DI factories
        $this->registerFactory(ThrowableLogger::class, $this->factoryLogger(...));
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        // Nothing to do.
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
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
