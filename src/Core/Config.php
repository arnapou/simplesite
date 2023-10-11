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

use Arnapou\Psr\Psr3Logger\Utils\Psr3Level;

final readonly class Config
{
    public string $name;

    /** @var non-empty-string */
    public string $path_public;

    /** @var non-empty-string */
    public string $log_path;
    public int $log_max_files;
    public Psr3Level $log_level;

    public string $path_cache;
    public string $path_data;
    public string $path_templates;
    public string $path_php;
    public string $base_path_url;

    /**
     * @throws Problem
     */
    public function __construct(
        string $name,
        string $path_public,
        string $path_cache,
        string $path_data = '',
        string $path_templates = '',
        string $path_php = '',
        string $log_path = '',
        int $log_max_files = 7,
        string $log_level = 'notice',
        string $base_path_url = '/'
    ) {
        $this->name = $name;

        $this->path_public = $this->mustExist($path_public ?: throw Problem::emptyVariable('path_public'))
            ?: throw Problem::emptyVariable('path_public');
        $this->path_cache = $this->createIfNotExist($path_cache ?: throw Problem::emptyVariable('path_cache'));

        $this->path_data = $this->mustExistIfNotEmpty($path_data);
        $this->path_templates = $this->mustExistIfNotEmpty($path_templates);
        $this->path_php = $this->mustExistIfNotEmpty($path_php);

        $this->base_path_url = '/' . ltrim($base_path_url, '/');

        $this->log_path = $this->createIfNotExist($this->noSlash($log_path) ?: "$this->path_cache/logs")
            ?: throw Problem::emptyVariable('log_path');
        $this->log_max_files = max($log_max_files, 0);
        $this->log_level = Psr3Level::tryFrom($log_level) ?? Psr3Level::Notice;
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    public function pathData(): string
    {
        return $this->path_data ?: throw Problem::emptyVariable('path_data');
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    public function pathCache(string $folder): string
    {
        return Utils::mkdir("$this->path_cache/$folder");
    }

    /**
     * @param non-empty-string $path
     *
     * @throws Problem
     */
    private function createIfNotExist(string $path): string
    {
        $path = $this->noSlash($path);

        return is_dir($path) ? $path : Utils::mkdir($path);
    }

    /**
     * @param non-empty-string $path
     *
     * @throws Problem
     */
    public function mustExist(string $path): string
    {
        $path = $this->noSlash($path);

        return is_dir($path) ? $path : throw Problem::pathNotExists($path);
    }

    /**
     * @throws Problem
     */
    public function mustExistIfNotEmpty(string $path): string
    {
        $path = $this->noSlash($path);

        return '' === $path ? '' : $this->noSlash($this->mustExist($path));
    }

    public function noSlash(string $path): string
    {
        return trim(rtrim(trim($path), '/\\'));
    }
}
