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

use Arnapou\SimpleSite\Exception\ConfigEmpty;
use Arnapou\SimpleSite\Exception\PathNotCreated;
use Arnapou\SimpleSite\Exception\PathNotExists;
use Arnapou\SimpleSite\Exception\PathNotWritable;
use Psr\Log\LogLevel;

final class Assert
{
    /**
     * @throws ConfigEmpty
     *
     * @return non-empty-string
     */
    public static function nonEmptyConfigPath(string $name, string $value): string
    {
        return '' !== $value ? $value : throw new ConfigEmpty($name);
    }

    /**
     * @param non-empty-string $path
     *
     * @throws PathNotExists
     */
    public static function pathExists(string $path): string
    {
        return is_dir($path) ? $path : throw new PathNotExists($path);
    }

    /**
     * @throws PathNotExists
     */
    public static function pathExistsIfNotEmpty(string $path): string
    {
        return '' === $path ? '' : self::pathExists($path);
    }

    /**
     * @param non-empty-string $path
     *
     * @throws PathNotCreated
     * @throws PathNotWritable
     */
    public static function pathExistsOrCreate(string $path): string
    {
        return is_dir($path) ? $path : Utils::mkdir($path);
    }

    /**
     * @return LogLevel::*
     */
    public static function validLogLevel(string $level): string
    {
        return match (strtolower(substr($level, 0, 3))) {
            'deb' => LogLevel::DEBUG,
            'inf' => LogLevel::INFO,
            'war' => LogLevel::WARNING,
            'err' => LogLevel::ERROR,
            'cri' => LogLevel::CRITICAL,
            'ale' => LogLevel::ALERT,
            'eme' => LogLevel::EMERGENCY,
            default => LogLevel::NOTICE,
        };
    }
}
