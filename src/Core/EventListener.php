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

class EventListener
{
    /**
     * @var array
     */
    private $listeners = [];

    public function dispatch($eventName, ?Event $event = null)
    {
        $callables = isset($this->listeners[$eventName]) ? $this->listeners[$eventName] : [];
        foreach ($callables as $callable) {
            $callable($event);
        }
    }

    public function addListener($eventName, callable $callable)
    {
        $this->listeners[$eventName][] = $callable;
    }

    public function clear()
    {
        $this->listeners = [];
    }
}
