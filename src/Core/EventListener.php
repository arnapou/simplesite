<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Core;

class EventListener
{
    private array $listeners = [];

    public function dispatch(string $eventName, ?Event $event = null): void
    {
        $callables = $this->listeners[$eventName] ?? [];
        foreach ($callables as $callable) {
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
