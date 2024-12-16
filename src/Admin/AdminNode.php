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

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Core\Scope;
use Arnapou\SimpleSite\Core\View;
use Arnapou\SimpleSite\SimpleSite;

final class AdminNode implements \Stringable
{
    public readonly ?View $view;
    public readonly string $path;
    public readonly bool $dir;
    public readonly string $ext;

    public function __construct(string|View|null $view)
    {
        if (null === $view || '' === $view) {
            $this->view = null;
            $this->path = '';
            $this->dir = true;
            $this->ext = '';
        } else {
            $this->view = \is_string($view) ? new View($view) : $view;
            $this->path = $this->view->real;
            $this->dir = $this->view->isDir;
            $this->ext = strtolower($this->view->extension());
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
        return $this->dir && null !== $this->view;
    }

    public function isRoot(): bool
    {
        return null === $this->view || '/' === $this->view->path;
    }

    public function isForbidden(): bool
    {
        return null !== $this->view && Scope::public->toString() === $this->view->scope && '/index.php' === $this->view->path;
    }

    public function isPicture(): bool
    {
        return !$this->dir && \in_array($this->ext, ['gif', 'png', 'jpg', 'svg'], true);
    }

    public function isSound(): bool
    {
        return !$this->dir && \in_array($this->ext, ['mp3', 'wav', 'ogg', 'aac', 'wma'], true);
    }

    public function isText(): bool
    {
        return !$this->dir && \in_array($this->ext, ['txt', 'css', 'js', 'json', 'twig', 'html', 'md', 'yaml', 'yml', 'php'], true);
    }

    public function isVideo(): bool
    {
        return !$this->dir && \in_array($this->ext, ['mp4', 'mpg', 'mov', 'avi', 'mkv'], true);
    }

    public function name(): string
    {
        return match (true) {
            null === $this->view => '',
            '/' === $this->view->path => $this->view->scope,
            default => $this->view->basename(),
        };
    }

    public function parent(): self
    {
        return new self($this->view?->dirname());
    }

    public function publicUrl(): string
    {
        return null !== $this->view
            ? match ($this->view->scope) {
                Scope::pages->toString() => match (true) {
                    $this->dir => $this->view->path,
                    !\in_array($this->ext, Config::PAGE_EXTENSIONS, true) => '',
                    default => substr($this->view->path, 0, -\strlen($this->ext) - 1),
                },
                Scope::public->toString() => match (true) {
                    str_ends_with($this->view->path, '/index.php') => substr($this->view->path, 0, -9),
                    default => $this->view->path,
                },
                default => '',
            }
        : '';
    }

    public function size(): string
    {
        $size = $this->view?->info()?->getSize() ?? 0;

        return match (true) {
            $this->dir, !\is_int($size) => '',
            $size < 1024 => $size . ' B',
            $size < 1048576 => number_format($size / 1024, 1) . ' KB',
            $size < 1073741824 => number_format($size / 1048576, 1) . ' MB',
            default => number_format($size / 1073741824, 1) . ' GB',
        };
    }

    public function symbol(): string
    {
        return match (true) {
            null === $this->view => 'icon-home',
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

        $list = [];

        if (null === $this->view) {
            foreach (Scope::cases() as $scope) {
                if (null !== $scope->toPath(SimpleSite::config(), false)) {
                    $list[] = new self(new View($scope->toString()));
                }
            }
        } else {
            foreach ($this->view->list() as $view) {
                $list[] = new self($view);
            }
        }

        return $list;
    }

    /**
     * @return non-empty-list<self>
     */
    public function breadcrumb(): array
    {
        if (null === $this->view) {
            return [$this];
        }

        $list = [
            new self(null),
            new self($this->view->root()),
        ];

        if ('' === ($relative = trim($this->view->path, '/'))) {
            return $list;
        }

        $dir = '';
        $explode = explode('/', $relative);
        foreach ($explode as $segment) {
            $list[] = new self($this->view->root()->dirname()->relative($dir .= "/$segment"));
        }

        return $list;
    }

    public function exists(): bool
    {
        return $this->view->exists ?? false;
    }

    public function rename(string $name): self
    {
        return match ($this->canRename()) {
            true => new self($this->view?->dirname()->relative($name)),
            false => throw new Problem("Cannot rename '$this'.", StatusClientError::BadRequest),
        };
    }

    public function create(string $name): self
    {
        return match ($this->canCreate()) {
            true => new self($this->view?->relative($name)),
            false => throw new Problem("Cannot create from '$this'.", StatusClientError::BadRequest),
        };
    }

    public function __toString(): string
    {
        return null === $this->view ? '' : $this->view->name;
    }
}
