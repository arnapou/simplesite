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

use Arnapou\PFDB\Storage\LockedStorage;
use Arnapou\PFDB\Storage\PhpFileStorage;
use Arnapou\PFDB\Table;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;

class Compteur implements ServiceFactory
{
    private Table $table;
    private int   $number;

    private function __construct(ServiceContainer $container)
    {
        $storage = new LockedStorage(new PhpFileStorage($container->Config()->path_data(), 'compteur'));
        $this->table = new Table($storage, 'ip', 'id');
        $this->number = $this->comptage();
        $storage->releaseLocks();
    }

    private function comptage(): int
    {
        $date = (int) date('Ymd');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($ip && !$this->table->get("IP.$ip.$date")) {
            $this->table->upsert(['date' => $date, 'ip' => $ip], "IP.$ip.$date");

            $this->incremente('COUNT');
            $this->incremente('MOIS.' . date('Y-m'));
            $this->incremente('YEAR.' . date('Y'));

            $this->table->deleteMultiple(
                $this->table->expr()->and(
                    $this->table->expr()->lt('date', $date),
                    $this->table->expr()->begins('id', 'IP.')
                )
            );
        }

        return $this->table->get('COUNT')['number'] ?? 1;
    }

    private function incremente(string $key): void
    {
        $value = (int) ($this->table->get($key)['number'] ?? 0);
        $this->table->upsert(['number' => $value + 1], $key);
    }

    public static function factory(ServiceContainer $container): self
    {
        return new self($container);
    }

    public static function aliases(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return number_format($this->number, 0, '.', '.');
    }
}
