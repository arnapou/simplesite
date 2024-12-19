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

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError as Error;
use Arnapou\SimpleSite\SimpleSite;

final readonly class View implements \Stringable
{
    /** @var non-empty-string */
    public string $scope;
    /** @var non-empty-string */
    public string $path;
    /** @var non-empty-string */
    public string $real;
    /** @var non-empty-string */
    public string $base;
    /** @var non-empty-string */
    public string $name;
    public bool $isDir;
    public bool $isFile;
    public bool $exists;

    /**
     * @throw Problem
     */
    public function __construct(string $view)
    {
        if ((bool) preg_match('!^(@[^/]+)(|/.*)$!', $view, $m)) {
            $scope = Scope::from($m[1]);
            $path = $this->getLeadingSlash($m[2]);
        } else {
            $scope = Scope::default();
            $path = $this->getLeadingSlash($view);
        }

        $this->scope = $scope->toString();
        $this->base = $this->getRealpath($scope->toPath(SimpleSite::config()));

        if (!file_exists($full = $this->base . $path)) {
            $this->isDir = str_ends_with($full, '/');
            $this->isFile = !$this->isDir;
            $this->path = $this->getRelativePath($this->base, $this->getNonExistentRealpath($full));
            $this->exists = false;
        } elseif (!is_link($full)) {
            $this->isDir = is_dir($full);
            $this->isFile = is_file($full);
            $this->path = $this->getRelativePath($this->base, $this->getRealpath($full));
            $this->exists = true;
        } else {
            throw new Problem(\sprintf('This must be a folder or a file: "%s". But it is neither one nor the other.', $full), Error::BadRequest);
        }

        $this->real = $this->base . rtrim($this->path, '/');
        $this->name = $this->scope . rtrim($this->path, '/');
    }

    public function basename(): string
    {
        return pathinfo($this->real, \PATHINFO_BASENAME);
    }

    /**
     * @param int<1, max> $levels
     */
    public function dirname(int $levels = 1): self
    {
        return new self($this->scope . $this->getLeadingSlash(\dirname($this->path, $levels)));
    }

    public function extension(): string
    {
        return pathinfo($this->real, \PATHINFO_EXTENSION);
    }

    public function info(): ?\SplFileInfo
    {
        return $this->exists ? new \SplFileInfo($this->real) : null;
    }

    /**
     * @return list<self>
     */
    public function list(string|bool|null $type = null): array
    {
        if (!$this->isDir) {
            return [];
        }

        $typeDir = match ($type) {
            null, true, '' => true,
            false => false,
            default => str_starts_with($type, 'd'),
        };
        $typeFile = match ($type) {
            null, false, '' => true,
            true => false,
            default => str_starts_with($type, 'f'),
        };

        $dirs = $files = [];
        foreach (new \DirectoryIterator($this->real) as $item) {
            if ($item->isDot() || $item->isLink()) {
                continue;
            }

            $view = $this->relative($item->getBasename());
            if ($typeDir && $view->isDir) {
                $dirs[] = $view;
            } elseif ($typeFile && $view->isFile) {
                $files[] = $view;
            }
        }
        sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return [...$dirs, ...$files];
    }

    /**
     * Add a trailing slash if you want to get a non-existent "folder".
     */
    public function relative(string $relative): self
    {
        return $this->isDir
            ? new self($this->scope . $this->getLeadingSlash($this->path . '/' . $relative))
            : new self($this->scope . $this->getLeadingSlash(\dirname($this->path) . '/' . $relative));
    }

    public function root(): self
    {
        return new self($this->scope);
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public static function tryFrom(string $view): ?self
    {
        try {
            return new View($view);
        } catch (Problem) {
            // does not work for @internal views
            return null;
        }
    }

    /**
     * @return non-empty-string
     */
    private function getLeadingSlash(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    private function getNonExistentRealpath(string $path): string
    {
        $path = (string) preg_replace('!//+!', '/', $path);
        $path = (string) preg_replace('!/\.(/|$)!', '/', $path);

        $count = 20;
        do {
            $path = (string) preg_replace('!/[^/]+/+\.\.(/|$)!', '/', $path);
        } while (0 !== --$count && str_contains($path, '/..'));

        return !str_contains("/$path/", '/../') ? $path
            : throw new Problem('Path traversable is forbidden : /../.', Error::BadRequest);
    }

    /**
     * @return non-empty-string
     */
    private function getRealpath(string $path): string
    {
        $real = realpath($path);

        return false !== $real ? $real
            : throw new Problem(\sprintf('The root "%s" could not be resolved to a realpath.', $path), Error::BadRequest);
    }

    /**
     * @return non-empty-string
     */
    private function getRelativePath(string $root, string $full): string
    {
        return match (true) {
            $root === $full => '/',
            str_starts_with($full, "$root/") => '/' . trim(substr($full, \strlen($root)), '/'),
            default => throw new Problem('Unauthorized access outside root paths.', Error::BadRequest),
        };
    }
}
