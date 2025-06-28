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

use Arnapou\PFDB\Storage\StorageInterface;

/**
 * This is a decorator which avoid any write operation into the data.
 * Used from the public twig files.
 */
final readonly class DbStoragePublic implements StorageInterface
{
    public function __construct(private StorageInterface $storage)
    {
    }

    public function load(string $name): array
    {
        return $this->storage->load($name);
    }

    public function save(string $name, array $data): void
    {
        // Nothing to do: that's the purpose, 100% quiet.
    }

    public function delete(string $name): void
    {
        // Nothing to do: that's the purpose, 100% quiet.
    }

    public function isReadonly(string $name): bool
    {
        // We don't want any write operation to raise exceptions.
        return false;
    }

    public function tableNames(): array
    {
        return $this->storage->tableNames();
    }
}
