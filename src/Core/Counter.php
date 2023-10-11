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

use Arnapou\PFDB\Core\TableInterface;
use Arnapou\PFDB\Database;
use Arnapou\PFDB\Factory\NoPKTableFactory;
use Arnapou\PFDB\Storage\LockedStorage;
use Arnapou\PFDB\Storage\PhpFileStorage;
use Arnapou\SimpleSite;
use Throwable;

final class Counter
{
    private const COUNT = 'COUNT';
    private const STATS = 'STATS';
    private const VALUE = 'value';
    private readonly Database $db;
    private readonly int $number;

    public function __construct(PhpFileStorage $phpFileStorage)
    {
        $storage = new LockedStorage($phpFileStorage);
        $this->db = new Database($storage, new NoPKTableFactory());
        $this->number = $this->process();
        $storage->releaseLocks();
    }

    private function process(): int
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $tableToday = $this->db->getTable(date('Y-m-d'));
        $tableTotal = $this->db->getTable(self::COUNT);

        if ($ip && !$tableToday->get("IP.$ip")) {
            $tableToday->upsert([], "IP.$ip");

            $this->increment($tableTotal, self::COUNT);

            $tableStats = $this->db->getTable(self::STATS);
            $this->increment($tableStats, 'DAY.' . date('Y-m-d'));
            $this->increment($tableStats, 'YEAR.' . date('Y'));
            $this->increment($tableStats, 'MONTH.' . date('Y-m'));

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
            SimpleSite::logger()->error($e->getMessage());
        }
    }

    private function increment(TableInterface $table, string $key): void
    {
        $value = (int) ($table->get($key)[self::VALUE] ?? 0);
        $table->upsert([self::VALUE => $value + 1], $key);
    }

    public function __toString(): string
    {
        return number_format($this->number, 0, '.', '.');
    }
}
