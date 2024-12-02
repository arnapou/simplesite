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

use Arnapou\PFDB\Database;
use Arnapou\PFDB\Exception\DirectoryNotFoundException;
use Arnapou\PFDB\Exception\InvalidTableNameException;
use Arnapou\PFDB\Storage\CachedFileStorage;
use Arnapou\PFDB\Storage\PhpFileStorage;
use Arnapou\PFDB\Storage\StorageInterface;
use Arnapou\PFDB\Storage\YamlFileStorage;
use Arnapou\Psr\Psr11Container\ServiceLocator;
use Arnapou\Psr\Psr14EventDispatcher\PhpHandlers;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr16SimpleCache\Decorated\GcPrunableSimpleCache;
use Arnapou\Psr\Psr16SimpleCache\FileSimpleCache;
use Arnapou\Psr\Psr3Logger\Decorator\MinimumLevelLogger;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr3Logger\FileLogger;
use Arnapou\Psr\Psr3Logger\Utils\Rotation;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Loader\FilesystemLoader;
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
        $this->registerAlias('counter', Counter::class);
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

        // Self
        $this->registerInstance(ContainerInterface::class, $this);

        // DI factories
        $this->registerFactory(PhpFileStorage::class, $this->factoryStoragePhp(...));
        $this->registerFactory(StorageInterface::class, $this->factoryStorageInterface(...));
        $this->registerFactory(ThrowableLogger::class, $this->factoryThrowableLogger(...));
        $this->registerFactory(CacheInterface::class, $this->factoryCache(...));
        $this->registerFactory(Image::class, $this->factoryImage(...));
        $this->registerFactory(LoaderInterface::class, $this->factoryTwigLoader(...));
        $this->registerFactory(Environment::class, $this->factoryTwigEnvironment(...));
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

    private function factoryTwigLoader(): LoaderInterface
    {
        $loader = new FilesystemLoader();
        $config = $this->get(Config::class);

        /** @var array<string, string> $namespaces */
        $namespaces = [
            $loader::MAIN_NAMESPACE => $config->path_public,
            'internal' => __DIR__ . '/../Views',
            'templates' => $config->path_templates,
            'data' => $config->path_data,
            'php' => $config->path_php,
            'public' => $config->path_public,
            'logs' => $config->log_path,
        ];

        foreach ($namespaces as $namespace => $path) {
            if ('' !== $path) {
                $loader->addPath($path, $namespace);
            }
        }

        return $loader;
    }

    private function factoryTwigEnvironment(): Environment
    {
        $environment = new Environment($this->get(LoaderInterface::class));
        $environment->addRuntimeLoader(new TwigRuntimeLoader());
        $environment->addExtension(new DebugExtension());
        $environment->addExtension(new MarkdownExtension());
        $environment->addExtension($this->get(TwigExtension::class));

        return $environment;
    }

    private function factoryImage(): Image
    {
        return new Image(
            $this->get(LoggerInterface::class),
            $this->get(CacheInterface::class),
            $this->get(Config::class)->path_public,
        );
    }

    private function factoryCache(): GcPrunableSimpleCache
    {
        return new GcPrunableSimpleCache(
            new FileSimpleCache(
                $this->get(Config::class)->pathCache('images'),
                86400 * 30,
            ),
            1,
            1000,
        );
    }

    private function factoryThrowableLogger(): ThrowableLogger
    {
        $fileLogger = new FileLogger(
            $this->get(Config::class)->log_path,
            'site',
            Rotation::EveryDay,
            $this->get(Config::class)->log_max_files,
            0o777,
            logFormatter: new LogFormatter(),
        );

        return new ThrowableLogger(new MinimumLevelLogger($fileLogger, $this->get(Config::class)->log_level));
    }

    private function factoryStoragePhp(): PhpFileStorage
    {
        return new PhpFileStorage(
            $this->get(Config::class)->pathData(),
            'compteur',
        );
    }

    private function factoryStorageInterface(): StorageInterface
    {
        try {
            return new CachedFileStorage(
                new YamlFileStorage($this->get(Config::class)->pathData()),
                $this->get(Config::class)->pathCache('database'),
            );
        } catch (DirectoryNotFoundException|InvalidTableNameException $e) {
            throw new Problem($e->getMessage(), 0, $e);
        }
    }
}
