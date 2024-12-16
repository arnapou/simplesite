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
use Arnapou\Psr\Psr11Container\Exception\ServiceNotFound;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Allows to expose a PSR-11 Container with magic getters for Twig.
 *
 * This container:
 * - exposes only a specific whitelist of classes to keep the others private.
 * - is 100% readonly to avoid twig messing with the internal real container.
 */
final class ContainerForTwig implements ContainerInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function get(string $id)
    {
        return match ($id) {
            'config' => $this->container->get(Config::class),
            'db' => $this->container->get(Database::class),
            'logger' => $this->container->get(ThrowableLogger::class),
            'request' => $this->container->get(ServerRequestInterface::class),
            default => throw ServiceNotFound::undefinedService($id),
        };
    }

    public function has(string $id): bool
    {
        return \in_array($id, [
            'config',
            'db',
            'logger',
            'request',
        ], true);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        throw new \RuntimeException('The container is Readonly.');
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }
}
