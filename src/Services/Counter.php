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

use Arnapou\PFDB\Core\TableInterface;
use Arnapou\PFDB\Database as PFDB;
use Arnapou\PFDB\Factory\NoPKTableFactory;
use Arnapou\PFDB\Storage\LockedStorage;
use Arnapou\PFDB\Storage\PhpFileStorage;
use Arnapou\SimpleSite\Core\Assert;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Throwable;

final class Counter implements ServiceFactory
{
    private const COUNT = 'COUNT';
    private const STATS = 'STATS';
    private const VALUE = 'value';
    private readonly PFDB $db;
    private readonly int $number;

    private function __construct(private readonly ServiceContainer $container)
    {
        $config = $container->config();
        $storage = new LockedStorage(
            new PhpFileStorage(
                Assert::nonEmptyConfigPath('path_data', $config->path_data),
                'compteur'
            )
        );
        $this->db = new PFDB($storage, new NoPKTableFactory());
        $this->number = $this->comptage();
        $storage->releaseLocks();
    }

    private function comptage(): int
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $tableToday = $this->db->getTable(date('Y-m-d'));
        $tableTotal = $this->db->getTable(self::COUNT);

        if ($ip && !$tableToday->get("IP.$ip")) {
            $tableToday->upsert([], "IP.$ip");

            $this->incremente($tableTotal, self::COUNT);

            $tableStats = $this->db->getTable(self::STATS);
            $this->incremente($tableStats, 'DAY.' . date('Y-m-d'));
            $this->incremente($tableStats, 'YEAR.' . date('Y'));
            $this->incremente($tableStats, 'MONTH.' . date('Y-m'));

            $this->cleanupOldDayTables($tableToday);
        }

        return $tableTotal->get(self::COUNT)[self::VALUE] ?? 1;
    }

    private function cleanupOldDayTables(TableInterface $tableToday): void
    {
        try {
            if ($tableToday->get('CLEANUP_DONE')) {
                return;
            }
            $tableToday->upsert(['time' => time()], 'CLEANUP_DONE');

            $now = time();
            for ($i = 7; $i < 30; ++$i) {
                $this->db->getStorage()->delete(date('Y-m-d', $now - $i * 86400));
            }
        } catch (Throwable $e) {
            $this->container->logger()->error($e->getMessage());
        }
    }

    private function incremente(TableInterface $table, string $key): void
    {
        $value = (int) ($table->get($key)[self::VALUE] ?? 0);
        $table->upsert([self::VALUE => $value + 1], $key);
    }

    public static function factory(ServiceContainer $container): self
    {
        return new self($container);
    }

    public static function aliases(): array
    {
        return ['Compteur'];
    }

    public function __toString(): string
    {
        return number_format($this->number, 0, '.', '.');
    }
}
