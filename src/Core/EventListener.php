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

final class EventListener
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    public function dispatch(string $eventName, ?Event $event = null): void
    {
        foreach ($this->listeners[$eventName] ?? [] as $callable) {
            $callable($event);
        }
    }

    public function addListener(string $eventName, callable $callable): void
    {
        $this->listeners[$eventName][] = $callable;
    }

    public function clear(): void
    {
        $this->listeners = [];
    }
}
