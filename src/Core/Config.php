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

use Psr\Log\LogLevel;

final readonly class Config
{
    public string $name;
    public string $path_public;
    public string $path_cache;
    public string $path_data;
    public string $path_templates;
    public string $path_php;
    public string $log_path;
    public int $log_max_files;
    /** @var LogLevel::* */
    public string $log_level;

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
    ) {
        $this->name = $name;

        $path_public = Assert::nonEmptyConfigPath('path_public', Utils::noSlash($path_public));
        $this->path_public = Assert::pathExists($path_public);

        $path_cache = Assert::nonEmptyConfigPath('path_cache', Utils::noSlash($path_cache));
        $this->path_cache = Assert::pathExistsOrCreate($path_cache);

        $this->path_data = Assert::pathExistsIfNotEmpty(Utils::noSlash($path_data));
        $this->path_templates = Assert::pathExistsIfNotEmpty(Utils::noSlash($path_templates));
        $this->path_php = Assert::pathExistsIfNotEmpty(Utils::noSlash($path_php));

        $this->log_path = Assert::pathExistsOrCreate(Utils::noSlash($log_path) ?: $path_cache . '/logs');
        $this->log_max_files = $log_max_files < 0 ? 0 : $log_max_files;
        $this->log_level = Assert::validLogLevel($log_level);
    }
}
