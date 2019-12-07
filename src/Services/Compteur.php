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
    private const COUNT = 'COUNT';
    /**
     * @var Table
     */
    private $table;
    /**
     * @var int
     */
    private $number;

    public function __construct(ServiceContainer $container)
    {
        $storage      = new LockedStorage(new PhpFileStorage($container->Config()->path_data(), 'compteur'));
        $this->table  = new Table($storage, 'ip', 'id');
        $this->number = $this->comptage();
        $storage->releaseLocks();
    }

    private function comptage()
    {
        $date  = (int)date('Ymd');
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
        $table = $this->table;
        if ($ip && !$table->get("IP.$ip.$date")) {
            $table->upsert(['date' => $date, 'ip' => $ip], "IP.$ip.$date");
            $this->incremente(self::COUNT);
            $this->incremente('MOIS.' . date('Y-m'));
            $this->incremente('YEAR.' . date('Y'));
            $table->deleteMultiple(
                $table->expr()->and(
                    $table->expr()->lt('date', $date),
                    $table->expr()->begins('id', 'IP.')
                )
            );
        }
        return $table->get(self::COUNT)['number'] ?? 1;
    }

    private function incremente($key)
    {
        $this->table->upsert(['number' => ($this->table->get($key)['number'] ?: 0) + 1], $key);
    }

    public static function factory(ServiceContainer $container)
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
