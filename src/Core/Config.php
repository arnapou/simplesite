<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Core;

use Arnapou\SimpleSite\Exception\ConfigException;
use Arnapou\SimpleSite\Utils;
use Monolog\Logger;

class Config
{
    public const LOG_DEBUG = Logger::DEBUG;
    public const LOG_INFO = Logger::INFO;
    public const LOG_NOTICE = Logger::NOTICE;
    public const LOG_WARNING = Logger::WARNING;
    public const LOG_ERROR = Logger::ERROR;
    public const LOG_CRITICAL = Logger::CRITICAL;
    public const LOG_ALERT = Logger::ALERT;
    public const LOG_EMERGENCY = Logger::EMERGENCY;

    private const DEFAULT_LOG_MAX_FILES = 7;
    private const DEFAULT_LOG_LEVEL_DEV = self::LOG_INFO;
    private const DEFAULT_LOG_LEVEL_PROD = self::LOG_NOTICE;

    /**
     * @var array<string, int|string>
     */
    private array $config;

    public function __construct(array $config)
    {
        $this->config = array_merge(
            [
                'name' => '',
                'path_cache' => '',
                'path_logs' => '',
                'path_data' => '',
                'path_public' => '',
                'path_templates' => '',
                'path_php' => '',
                'log_max_files' => 14,
                'log_level' => 0,
            ],
            $config
        );

        if (!$this->config['path_logs']) {
            $this->config['path_logs'] = $this->config['path_cache'] ? $this->config['path_cache'] . '/logs' : '';
        }
    }

    public function name(): string
    {
        return (string) $this->config['name']
            ?: throw new ConfigException('Config "name" is not defined');
    }

    public function path_cache(): string
    {
        return Utils::mkdir((string) $this->config['path_cache'])
            ?: throw new ConfigException('Config "path_cache" is not defined');
    }

    public function path_logs(): string
    {
        return Utils::mkdir((string) $this->config['path_logs'])
            ?: throw new ConfigException('Config "path_logs" is not defined');
    }

    public function path_data(): string
    {
        return Utils::trimRightSlash((string) $this->config['path_data'])
            ?: throw new ConfigException('Config "path_data" is not defined');
    }

    public function path_public(): string
    {
        return Utils::trimRightSlash((string) $this->config['path_public'])
            ?: throw new ConfigException('Config "path_public" is not defined');
    }

    public function path_templates(): string
    {
        return Utils::trimRightSlash((string) $this->config['path_templates']);
    }

    public function path_php(): string
    {
        return Utils::trimRightSlash((string) $this->config['path_php']);
    }

    public function log_max_files(): int
    {
        return (int) $this->config['log_max_files'] ?: self::DEFAULT_LOG_MAX_FILES;
    }

    public function log_level(): int
    {
        if (Utils::inPhar()) {
            return (int) $this->config['log_level'] ?: self::DEFAULT_LOG_LEVEL_PROD;
        }

        return (int) $this->config['log_level'] ?: self::DEFAULT_LOG_LEVEL_DEV;
    }
}
