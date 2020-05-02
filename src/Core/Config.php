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

use Arnapou\SimpleSite\Exception\ConfigException;
use Arnapou\SimpleSite\Utils;
use Monolog\Logger;

class Config
{
    const LOG_DEBUG = Logger::DEBUG;
    const LOG_INFO = Logger::INFO;
    const LOG_NOTICE = Logger::NOTICE;
    const LOG_WARNING = Logger::WARNING;
    const LOG_ERROR = Logger::ERROR;
    const LOG_CRITICAL = Logger::CRITICAL;
    const LOG_ALERT = Logger::ALERT;
    const LOG_EMERGENCY = Logger::EMERGENCY;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = array_merge(
            [
                'name'           => '',
                'path_cache'     => '',
                'path_logs'      => '',
                'path_data'      => '',
                'path_public'    => '',
                'path_templates' => '',
                'path_php'       => '',
                'log_max_files'  => 14,
                'log_level'      => Config::LOG_INFO,
            ],
            $config
        );

        $this->config['path_logs'] = $this->config['path_logs'] ?: ($this->config['path_cache'] ? $this->config['path_cache'] . '/logs' : '');
    }

    public function name(): string
    {
        if (!$this->config['name']) {
            throw new ConfigException('Config "name" is not defined');
        }
        return $this->config['name'];
    }

    public function path_cache(): string
    {
        if (!$this->config['path_cache']) {
            throw new ConfigException('Config "path_cache" is not defined');
        }
        Utils::mkdir($path = Utils::no_slash($this->config['path_cache']));
        return $path;
    }

    public function path_logs(): string
    {
        if (!$this->config['path_logs']) {
            throw new ConfigException('Config "path_logs" is not defined');
        }
        Utils::mkdir($path = Utils::no_slash($this->config['path_logs']));
        return $path;
    }

    public function path_data(): string
    {
        if (!$this->config['path_data']) {
            throw new ConfigException('Config "path_data" is not defined');
        }
        return Utils::no_slash($this->config['path_data']);
    }

    public function path_public(): string
    {
        if (!$this->config['path_public']) {
            throw new ConfigException('Config "path_public" is not defined');
        }
        return Utils::no_slash($this->config['path_public']);
    }

    public function path_templates(): string
    {
        return Utils::no_slash($this->config['path_templates']);
    }

    public function path_php(): string
    {
        return Utils::no_slash($this->config['path_php']);
    }

    public function log_max_files(): int
    {
        return \intval($this->config['log_max_files']) ?: 7;
    }

    public function log_level()
    {
        return $this->config['log_level'];
    }
}
