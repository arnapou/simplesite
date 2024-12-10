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
    public readonly string $path;
    /** @var non-empty-string */
    public readonly string $rel;
    public readonly string $ext;
    public readonly bool $dir;
    private ?FileNode $node;

    private function __construct(public Config $config, string|AdminScope|null $scope, string $relative)
    {
        $this->scope = \is_string($scope) ? AdminScope::tryFrom($scope) : $scope;

        try {
            if (null === $this->scope) {
                $this->node = null;
                $this->root = '';
                $this->ext = '';
                $this->path = '';
                $this->rel = '/';
                $this->dir = true;
            } else {
                $this->node = new FileNode($this->scope->toPath($this->config), $relative);
                $this->root = $this->node->root;
                $this->ext = $this->node->extension() ?? '';
                $this->path = $this->node->fullPath;
                $this->rel = $this->node->relativePath;
                $this->dir = $this->node->isFolder;
            }
        } catch (DomainException $e) {
            throw new Problem($e->getMessage(), StatusClientError::BadRequest, $e);
        }
    }

    public function canDelete(): bool
    {
        return !$this->isForbidden() && !$this->isRoot() && $this->exists();
    }

    public function canDownload(): bool
    {
        return !$this->isForbidden() && $this->exists();
    }

    public function canEdit(): bool
    {
        return !$this->isForbidden() && !$this->isRoot() && $this->isText();
    }

    public function canRename(): bool
    {
        return !$this->isForbidden() && !$this->isRoot() && $this->exists();
    }

    public function canCreate(): bool
    {
        return $this->dir && null !== $this->scope;
    }

    public function isRoot(): bool
    {
        return null === $this->scope || '/' === $this->rel;
    }

    public function isForbidden(): bool
    {
        return AdminScope::public === $this->scope && '/index.php' === $this->rel;
    }

    public function isPicture(): bool
    {
        return !$this->dir && \in_array(strtolower($this->ext), ['gif', 'png', 'jpg', 'svg'], true);
    }

    public function isSound(): bool
    {
        return !$this->dir && \in_array(strtolower($this->ext), ['mp3', 'wav', 'ogg', 'aac', 'wma'], true);
    }

    public function isText(): bool
    {
        return !$this->dir && \in_array(strtolower($this->ext), ['txt', 'css', 'js', 'json', 'twig', 'html', 'md', 'yaml', 'yml', 'php'], true);
    }

    public function isVideo(): bool
    {
        return !$this->dir && \in_array(strtolower($this->ext), ['mp4', 'mpg', 'mov', 'avi', 'mkv'], true);
    }

    public function name(): string
    {
        return Enforce::nullableNonEmptyString(basename($this->rel)) ?? $this->scope?->toString() ?? '';
    }

    public function parent(): self
    {
        return new self($this->config, $this->scope, \dirname($this->rel) . '/');
    }

    public function publicUrl(): string
    {
        return match ($this->scope) {
            AdminScope::pages => match (true) {
                $this->dir => $this->rel,
                !\in_array($this->ext, Config::PAGE_EXTENSIONS, true) => '',
                default => substr($this->rel, 0, -\strlen($this->ext) - 1),
            },
            AdminScope::public => match (true) {
                str_ends_with($this->rel, '/index.php') => substr($this->rel, 0, -9),
                default => $this->rel,
            },
            default => '',
        };
    }

    public function size(): string
    {
        $size = $this->node?->filesize() ?? 0;

        return match (true) {
            $this->dir => '',
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
            $this->dir => 'icon-folder',
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
        return date('Y-m-d H:i', (int) filemtime($this->path));
    }

    /**
     * @return list<self>
     */
    public function list(): array
    {
        if (!$this->dir) {
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
        foreach (new DirectoryIterator($this->path) as $item) {
            if ($item->isDot() || $item->isLink()) {
                continue;
            }

            $node = new self($this->config, $this->scope, substr($item->getPathname(), \strlen($this->root) + 1));

            if ($node->dir) {
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

        if ('' === ($relative = ltrim($this->rel, '/'))) {
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
        return $this->node->exists ?? false;
    }

    public function rename(string $name): self
    {
        return match ($this->canRename()) {
            true => new self($this->config, $this->scope, \dirname($this->rel) . '/' . $name),
            false => throw new Problem("Cannot rename '$this'.", StatusClientError::BadRequest),
        };
    }

    public function create(string $name): self
    {
        return match ($this->canCreate()) {
            true => new self($this->config, $this->scope, $this->rel . '/' . $name),
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
        return null === $this->scope ? '' : $this->scope->toString() . $this->rel;
    }
}
