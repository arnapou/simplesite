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

use Arnapou\Ensure\Expected;
use Arnapou\Psr\Psr3Logger\Utils\Psr3Level;

final readonly class Config
{
    /** @var non-empty-list<non-empty-string> */
    public const array PAGE_EXTENSIONS = ['twig', 'html'];
    public const array IMAGE_MIME_TYPES = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];

    /** @var non-empty-string */
    public string $path_public;
    /** @var non-empty-string */
    public string $path_pages;
    /** @var non-empty-string */
    public string $path_cache;

    /** @var non-empty-string|null */
    public ?string $path_data;
    /** @var non-empty-string|null */
    public ?string $path_templates;
    /** @var non-empty-string|null */
    public ?string $path_php;

    /** @var non-empty-string */
    public string $base_path_root;
    /** @var non-empty-string|null */
    public ?string $base_path_admin;

    /** @var non-empty-string */
    public string $log_path;
    public int $log_max_files;
    public Psr3Level $log_level;

    /**
     * @throws Problem
     */
    public function __construct(
        public string $name,
        string $path_public,
        string $path_pages,
        string $path_cache,
        string $path_data = '',
        string $path_templates = '',
        string $path_php = '',
        string $log_path = '',
        int $log_max_files = 7,
        string $log_level = 'notice',
        string $base_path_root = '',
        string $base_path_admin = '',
    ) {
        try {
            $this->path_public = $this->mustExist($path_public, 'path_public');
            $this->path_pages = $this->mustExist($path_pages, 'path_pages');
            $this->path_cache = $this->mustCreateIfNotExist($path_cache, '', 'path_cache');

            $this->path_data = $this->mustExistIfNotEmpty($path_data);
            $this->path_templates = $this->mustExistIfNotEmpty($path_templates);
            $this->path_php = $this->mustExistIfNotEmpty($path_php);

            $this->base_path_root = $this->basePath($base_path_root);
            $this->base_path_admin = $this->basePathIfNotEmpty($base_path_admin);

            $this->log_path = $this->mustCreateIfNotExist($log_path, "$this->path_cache/logs", 'log_path');
            $this->log_max_files = max($log_max_files, 0);
            $this->log_level = Psr3Level::tryFrom($log_level) ?? Psr3Level::Notice;
        } catch (Expected $e) {
            throw Problem::emptyVariable($e->getPropertyName());
        }
    }

    /**
     * @return non-empty-string
     */
    private function basePath(string $path): string
    {
        return '' === ($basePath = trim($path, '/')) ? '/' : "/$basePath/";
    }

    /**
     * @return non-empty-string|null
     */
    private function basePathIfNotEmpty(string $path): ?string
    {
        return '/' === ($basePath = $this->basePath($path)) ? null : $basePath;
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    public function pathCache(string $folder): string
    {
        return $this->mkdir("$this->path_cache/$folder");
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    private function mustCreateIfNotExist(string $path, string $default, string $what): string
    {
        $sanitized = $this->sanitizePath($path, false);

        return match (true) {
            '' !== $sanitized => $this->mkdir($sanitized),
            '' !== $default => $this->mkdir($default),
            default => throw Problem::emptyVariable($what),
        };
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    private function mustExist(string $path, string $what): string
    {
        $sanitized = $this->sanitizePath($path);

        return match (true) {
            '' === $sanitized => throw Problem::emptyVariable($what),
            is_dir($sanitized) => $sanitized,
            default => throw Problem::pathNotExists($path),
        };
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string|null
     */
    private function mustExistIfNotEmpty(string $path): ?string
    {
        $sanitized = $this->sanitizePath($path);

        return match (true) {
            '' === $sanitized => null,
            is_dir($sanitized) => $sanitized,
            default => throw Problem::pathNotExists($path),
        };
    }

    private function sanitizePath(string $path, bool $throw = true): string
    {
        // Remove trailing slash
        $value = trim(rtrim(trim($path), '/\\'));

        if ('' === $value) {
            return '';
        }

        if (\is_bool($real = realpath($path))) {
            return !$throw ? $path : throw Problem::pathNotExists($path);
        }

        return $real;
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    private function mkdir(string $path): string
    {
        if ('' === $path) {
            throw Problem::pathNotCreated($path);
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0o777, true) && !is_dir($path)) {
                throw Problem::pathNotCreated($path);
            }

            return $path;
        }

        if (!is_writable($path)) {
            throw Problem::pathNotWritable($path);
        }

        return $path;
    }
}
