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

namespace Arnapou\SimpleSite\Core\Psr;

use Arnapou\SimpleSite\Core\Utils;
use Arnapou\SimpleSite\Exception\CacheException;
use DateInterval;
use Generator;

use function is_int;
use function is_string;

use IteratorAggregate;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * @template-implements IteratorAggregate<string, FilesystemCacheFile>
 */
final class FilesystemCache implements CacheInterface, IteratorAggregate
{
    public readonly string $path;

    /**
     * @param non-empty-string $path
     */
    public function __construct(
        string $path,
        public readonly int $defaultTtl = 86400,
        public readonly Probability $pruneProbability = new Probability()
    ) {
        $this->path = Utils::mkdir(Utils::noSlash($path));
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if (null === $value) {
            return false;
        }

        if (!is_string($value)) {
            throw new CacheException('The value should be a string');
        }

        $file = new FilesystemCacheFile($this->filename($key));
        $file->write($this->ttlToExpireAt($ttl), $key, $value);

        return true;
    }

    public function get(string $key, mixed $default = null): string|null
    {
        $file = new FilesystemCacheFile($this->filename($key));

        if (null === ($content = $file->getContent())) {
            return is_string($default) ? $default : null;
        }

        return $content;
    }

    public function delete(string $key): bool
    {
        return (new FilesystemCacheFile($this->filename($key)))->delete();
    }

    public function clear(): bool
    {
        foreach ($this as $file) {
            $file->delete();
        }

        return true;
    }

    public function has(string $key): bool
    {
        return !(new FilesystemCacheFile($this->filename($key)))->isExpired();
    }

    public function getMultiple(iterable $keys, mixed $default = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            $done = $this->set($key, $value, $ttl);
            $ok = $ok && $done;
        }

        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            $done = $this->delete($key);
            $ok = $ok && $done;
        }

        return $ok;
    }

    /**
     * @return Generator<string, FilesystemCacheFile>
     */
    public function getIterator(): Generator
    {
        foreach (new RecursiveFilesIterator($this->path) as $file) {
            yield $file->getPathname() => new FilesystemCacheFile($file);
        }
    }

    /**
     * @return array{int, int}  [ $count, $size ]
     */
    public function prune(): array
    {
        [$count, $size] = [0, 0];

        $time = time();
        foreach ($this as $file) {
            if ($file->isExpired($time)) {
                ++$count;
                $size += (int) $file->filesize();
                $file->delete();
            }
        }

        return [$count, $size];
    }

    public function __destruct()
    {
        if ($this->pruneProbability->isTriggered()) {
            try {
                $this->prune();
            } catch (Throwable $e) {
                // nothing to do : should be silent to avoid wrong effects
            }
        }
    }

    /**
     * Get the real filename associated  with this key.
     */
    public function filename(string $key): string
    {
        $hash = md5($key);

        return $this->path . '/' . $hash[0] . '/' . $hash[1] . '/' . $hash[2] . '/' . substr($hash, 3);
    }

    private function ttlToExpireAt(null|int|DateInterval $ttl): int
    {
        return match (true) {
            $ttl instanceof DateInterval => time() + $ttl->days * 86400 + $ttl->h * 3600 + $ttl->i * 60 + $ttl->s,
            is_int($ttl) => $ttl > 1_000_000 ? $ttl : $ttl + time(),
            default => time() + $this->defaultTtl,
        };
    }
}
