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

use Arnapou\Psr\Psr11Container\ServiceLocator;
use Psr\Container\ContainerInterface;

/**
 * Allows to expose a PSR-11 Container with magic getters for Twig.
 */
final readonly class LazyGetterContainer implements ContainerInterface
{
    public function __construct(private ServiceLocator $services)
    {
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

    public function get(string $id): object
    {
        return $this->services->get($id);
    }

    public function has(string $id): bool
    {
        return $this->services->has($id);
    }
}
