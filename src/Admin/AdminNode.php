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

namespace Arnapou\SimpleSite\Admin;

use Arnapou\Ensure\Enforce;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\FileNode;
use Arnapou\SimpleSite\Core\Problem;
use DirectoryIterator;
use DomainException;
use Stringable;

/**
 * Works only for existing files !
 */
final class AdminNode implements Stringable
{
    public readonly string $root;
    public readonly ?AdminScope $scope;
    public readonly string $pathname;
    /** @var non-empty-string */
    public readonly string $relative;
    public readonly string $ext;
    public readonly bool $isDir;

    private function __construct(public Config $config, string|AdminScope|null $scope, string $relative)
    {
        $this->scope = \is_string($scope) ? AdminScope::tryFrom($scope) : $scope;

        try {
            if (null === $this->scope) {
                $this->root = '';
                $this->ext = '';
                $this->pathname = '';
                $this->relative = '/';
                $this->isDir = true;
            } else {
                $node = new FileNode($this->scope->toPath($this->config), $relative);
                $this->root = $node->root;
                $this->ext = $node->extension() ?? '';
                $this->pathname = $node->fullPath;
                $this->relative = $node->relativePath;
                $this->isDir = $node->isFolder;
            }
        } catch (DomainException $e) {
            throw new Problem($e->getMessage(), StatusClientError::BadRequest, $e);
        }
    }

    public function canDelete(): bool
    {
        return !$this->isForbidden() && '/' !== $this->relative;
    }

    public function canDownload(): bool
    {
        return !$this->isForbidden() && $this->exists();
    }

    public function canEdit(): bool
    {
        return !$this->isForbidden() && $this->isText();
    }

    public function canRename(): bool
    {
        return !$this->isForbidden() && $this->exists();
    }

    public function canCreate(): bool
    {
        return !$this->isForbidden() && $this->isDir && null !== $this->scope;
    }

    public function isForbidden(): bool
    {
        return AdminScope::public === $this->scope && '/index.php' === $this->relative;
    }

    public function isPicture(): bool
    {
        return !$this->isDir && \in_array(strtolower($this->ext), ['gif', 'png', 'jpg', 'svg'], true);
    }

    public function isSound(): bool
    {
        return !$this->isDir && \in_array(strtolower($this->ext), ['mp3', 'wav', 'ogg', 'aac', 'wma'], true);
    }

    public function isText(): bool
    {
        return !$this->isDir && \in_array(strtolower($this->ext), ['txt', 'css', 'js', 'json', 'twig', 'html', 'md', 'yaml', 'yml', 'php'], true);
    }

    public function isVideo(): bool
    {
        return !$this->isDir && \in_array(strtolower($this->ext), ['mp4', 'mpg', 'mov', 'avi', 'mkv'], true);
    }

    public function name(): string
    {
        return Enforce::nullableNonEmptyString(basename($this->relative)) ?? $this->scope?->toString() ?? '';
    }

    public function parent(): self
    {
        return new self($this->config, $this->scope, \dirname($this->relative) . '/');
    }

    public function publicUrl(): string
    {
        return match ($this->scope) {
            AdminScope::pages => match (true) {
                $this->isDir => $this->relative,
                !\in_array($this->ext, Config::PAGE_EXTENSIONS, true) => '',
                default => substr($this->relative, 0, -\strlen($this->ext) - 1),
            },
            AdminScope::public => match (true) {
                str_ends_with($this->relative, '/index.php') => substr($this->relative, 0, -9),
                default => $this->relative,
            },
            default => '',
        };
    }

    public function size(): string
    {
        $size = filesize($this->pathname);

        return match (true) {
            $this->isDir => '',
            $size < 1024 => $size . ' B',
            $size < 1048576 => number_format($size / 1024, 1) . ' KB',
            $size < 1073741824 => number_format($size / 1048576, 1) . ' MB',
            default => number_format($size / 1073741824, 1) . ' GB',
        };
    }

    public function symbol(): string
    {
        return match (true) {
            null === $this->scope => 'icon-home',
            $this->isDir => 'icon-folder',
            $this->isForbidden() => 'file-forbidden',
            $this->isPicture() => 'file-picture',
            $this->isSound() => 'file-sound',
            $this->isVideo() => 'file-video',
            $this->isText() => 'file-text',
            default => 'file-generic',
        };
    }

    public function time(): string
    {
        return date('Y-m-d H:i', (int) filemtime($this->pathname));
    }

    /**
     * @return list<self>
     */
    public function list(): array
    {
        if (!$this->isDir) {
            return [];
        }

        if (null === $this->scope) {
            return [
                new self($this->config, AdminScope::pages, ''),
                new self($this->config, AdminScope::public, ''),
                ...(null !== $this->config->path_templates ? [new self($this->config, AdminScope::templates, '')] : []),
            ];
        }

        $dirs = $files = [];
        foreach (new DirectoryIterator($this->pathname) as $item) {
            if ($item->isDot() || $item->isLink()) {
                continue;
            }

            $node = new self($this->config, $this->scope, substr($item->getPathname(), \strlen($this->root) + 1));

            if ($node->isDir) {
                $dirs[] = $node;
            } else {
                $files[] = $node;
            }
        }
        sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return [...$dirs, ...$files];
    }

    /**
     * @return non-empty-list<self>
     */
    public function breadcrumb(): array
    {
        if (null === $this->scope) {
            return [$this];
        }

        $list = [
            new self($this->config, null, ''),
            new self($this->config, $this->scope, ''),
        ];

        if ('' === ($relative = ltrim($this->relative, '/'))) {
            return $list;
        }

        $dir = '';
        $explode = explode('/', $relative);
        foreach ($explode as $segment) {
            $list[] = new self($this->config, $this->scope, $dir .= "/$segment");
        }

        return $list;
    }

    public function exists(): bool
    {
        return file_exists($this->pathname);
    }

    public function rename(string $name): self
    {
        return match ($this->canRename()) {
            true => new self($this->config, $this->scope, \dirname($this->relative) . '/' . $name),
            false => throw new Problem("Cannot rename '$this'.", StatusClientError::BadRequest),
        };
    }

    public function create(string $name): self
    {
        return match ($this->canCreate()) {
            true => new self($this->config, $this->scope, $this->relative . '/' . $name),
            false => throw new Problem("Cannot create from '$this'.", StatusClientError::BadRequest),
        };
    }

    public static function from(Config $config, string $path): self
    {
        if ('' === $path) {
            return new self($config, null, '');
        }

        if ((bool) preg_match('!^(@\w+)/(.*)$!', $path, $matches)) {
            return new self($config, $matches[1], $matches[2]);
        }

        throw new Problem("Invalid path '$path'.", StatusClientError::BadRequest);
    }

    public function __toString(): string
    {
        return null === $this->scope ? '' : $this->scope->toString() . $this->relative;
    }
}
