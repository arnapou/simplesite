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

use DomainException;

final readonly class FileNode
{
    /** @var non-empty-string */
    public string $root;
    /** @var non-empty-string */
    public string $relativePath;
    /** @var non-empty-string */
    public string $fullPath;
    public bool $exists;
    public bool $isFolder;
    public bool $isFile;

    /**
     * @throw \DomainException
     */
    public function __construct(string $root, string $relativePath)
    {
        if (!is_dir($root)) {
            throw new DomainException(\sprintf('The root "%s" is not a folder.', $root));
        }
        $this->root = $this->getRealpath($root);

        $fullPath = $root . '/' . ltrim($relativePath, '/');
        if (!file_exists($fullPath)) {
            $this->isFolder = str_ends_with($fullPath, '/');
            $this->isFile = !$this->isFolder;
            $this->relativePath = $this->getRelativePath($this->root, $this->getNonExistentRealpath($fullPath));
            $this->exists = false;
        } elseif (is_dir($fullPath)) {
            $this->isFolder = true;
            $this->isFile = false;
            $this->relativePath = $this->getRelativePath($this->root, $this->getRealpath($fullPath));
            $this->exists = true;
        } elseif (is_file($fullPath)) {
            $this->isFolder = false;
            $this->isFile = true;
            $this->relativePath = $this->getRelativePath($this->root, $this->getRealpath($fullPath));
            $this->exists = true;
        } else {
            throw new DomainException(\sprintf('This must be a folder or a file: "%s". But it is neither one nor the other.', $fullPath));
        }

        $this->fullPath = $this->root . rtrim($this->relativePath, '/');
    }

    public function filesize(): ?int
    {
        $filesize = $this->exists && $this->isFile ? filesize($this->fullPath) : null;

        return false !== $filesize ? $filesize : throw new DomainException(\sprintf('Error while retrieving the filesize of "%s".', $this->fullPath));
    }

    public function extension(): ?string
    {
        return match (true) {
            $this->isFile => pathinfo($this->fullPath, \PATHINFO_EXTENSION),
            default => null,
        };
    }

    public function basename(): string
    {
        return pathinfo($this->fullPath, \PATHINFO_BASENAME);
    }

    /**
     * Add a trailing slash if you want to get a non-existent "folder".
     */
    public function newRelative(string $relative): self
    {
        return new self($this->root, \dirname($this->relativePath) . '/' . $relative);
    }

    /**
     * @return non-empty-string
     */
    private function getRealpath(string $path): string
    {
        $real = realpath($path);

        return false !== $real ? $real
            : throw new DomainException(\sprintf('The root "%s" could not be resolved to a realpath.', $path));
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
            : throw new DomainException('Path traversable is forbidden : /../.');
    }

    /**
     * @return non-empty-string
     */
    private function getRelativePath(string $root, string $realpath): string
    {
        return match (true) {
            $root === $realpath => '/',
            str_starts_with($realpath, "$root/") => '/' . trim(substr($realpath, \strlen($root)), '/'),
            default => throw new DomainException('Unauthorized access outside root paths.'),
        };
    }
}
