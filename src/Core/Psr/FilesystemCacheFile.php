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

use function dirname;
use function is_string;

use RuntimeException;
use SplFileInfo;
use Throwable;

final class FilesystemCacheFile
{
    public readonly string $pathname;
    private ?int $expiredAt = null;
    private ?string $key = null;
    private ?bool $exists = null;

    public function __construct(SplFileInfo|string $file)
    {
        $this->pathname = is_string($file) ? $file : $file->getPathname();
    }

    public function exists(): bool
    {
        return $this->exists ??= is_file($this->pathname);
    }

    public function filesize(): ?int
    {
        return $this->exists() ? (int) filesize($this->pathname) : null;
    }

    public function filemtime(): ?int
    {
        return $this->exists() ? (int) filemtime($this->pathname) : null;
    }

    public function delete(): bool
    {
        $deleted = $this->exists() && @unlink($this->pathname);
        $this->expiredAt = null;
        $this->key = null;
        $this->exists = null;

        return $deleted;
    }

    public function isExpired(?int $time = null): bool
    {
        return !$this->exists() || ($time ?? time()) >= $this->getExpiredAt();
    }

    /**
     * Return the expiration timestamp of the file cached.
     */
    public function getExpiredAt(): ?int
    {
        return $this->getExpiredAtAndKey()[0] ?? null;
    }

    /**
     * Return the expiration timestamp of the file cached + the key.
     *
     * @return null|array{int, string} [ $expiresAt, $key ]
     */
    public function getExpiredAtAndKey(): ?array
    {
        if (null !== $this->expiredAt && null !== $this->key) {
            return [$this->expiredAt, $this->key];
        }

        if (!$this->exists() || !($handle = @fopen($this->pathname, 'r'))) {
            return null;
        }

        try {
            $this->expiredAt = (int) fgets($handle);
            $this->key = rawurldecode(rtrim((string) fgets($handle)));

            return [$this->expiredAt, $this->key];
        } catch (Throwable) {
            // nothing to do : should be silent to avoid wrong effects
            return null;
        } finally {
            @fclose($handle);
        }
    }

    /**
     * Get the content.
     */
    public function getContent(): ?string
    {
        if (!$this->exists() || !($handle = fopen($this->pathname, 'r'))) {
            return null;
        }

        $content = null;
        try {
            $this->expiredAt = (int) fgets($handle);
            $this->key = rawurldecode(rtrim((string) fgets($handle)));

            if (time() < $this->expiredAt) {
                $content = stream_get_contents($handle);
            }
        } finally {
            @fclose($handle);
        }

        if (is_string($content)) {
            return $content;
        }

        // expired file : we delete it
        $this->delete();

        return null;
    }

    /**
     * Write the file.
     */
    public function write(int $expiresAt, string $key, string $content): void
    {
        Utils::mkdir(dirname($this->pathname));

        if (!($handle = fopen($this->pathname, 'w'))) {
            throw new RuntimeException("Could not open file '$this->pathname' for writing");
        }

        try {
            $done = fwrite($handle, "$expiresAt\n" . rawurlencode($key) . "\n" . $content);
            if (false === $done) {
                throw new RuntimeException("Could not write file '$this->pathname'");
            }
        } finally {
            @fclose($handle);
        }

        $this->expiredAt = $expiresAt;
        $this->key = $key;
        $this->exists = true;
    }
}
