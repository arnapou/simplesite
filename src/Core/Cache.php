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

use Arnapou\Ensure\Ensure;
use Arnapou\Psr\Psr16SimpleCache\Decorated\GcPrunableSimpleCache;
use Arnapou\Psr\Psr16SimpleCache\FileSimpleCache;
use Arnapou\Psr\Psr16SimpleCache\Utils\MultipleTrait;
use Closure;
use DateInterval;
use Psr\SimpleCache\CacheInterface;

final readonly class Cache implements CacheInterface
{
    use MultipleTrait;
    private CacheInterface $internal;

    public function __construct(Config $config)
    {
        $this->internal = new GcPrunableSimpleCache(
            new FileSimpleCache($config->pathCache('tmp'), 86400 * 30),
            1,
            1000,
        );
    }

    public function from(string $key, Closure $factory, ?int $ttl = null): string
    {
        $content = $this->get($key);

        if (!\is_string($content) || !$this->has($key)) {
            $content = Ensure::string($factory(), 'Utils::fromCache expect a string content');
            $this->set($key, $content, $ttl);
        }

        return $content;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->internal->get($key, $default);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->internal->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->internal->delete($key);
    }

    public function clear(): bool
    {
        return $this->internal->clear();
    }

    public function has(string $key): bool
    {
        return $this->internal->has($key);
    }
}
