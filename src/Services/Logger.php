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

namespace Arnapou\SimpleSite\Services;

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Arnapou\SimpleSite\Utils;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class Logger implements ServiceFactory, LoggerInterface
{
    use LoggerTrait;

    /**
     * @var ServiceContainer
     */
    private $container;
    /**
     * @var Monolog
     */
    private $logger;

    public function __construct(ServiceContainer $container, Monolog $logger)
    {
        $this->logger    = $logger;
        $this->container = $container;

        $logger->pushProcessor([$this, 'processor']);
    }

    public static function factory(ServiceContainer $container)
    {
        Utils::mkdir($logdir = $container->Config()->path_logs());

        $level = $container->Config()->log_level() ?: (Utils::in_phar() ? Config::LOG_NOTICE : Config::LOG_DEBUG);

        $handler = new RotatingFileHandler($logdir . '/site.log', $container->Config()->log_max_files(), $level);

        return new self($container, new Monolog('site', [$handler]));
    }

    public static function aliases(): array
    {
        return [];
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }

    public function processor($data)
    {
        try {
            $added = [
                'url' => $this->container->Request()->getPathInfo(),
                'ip'  => $_SERVER['REMOTE_ADDR'] ?? '?',
            ];
            if ($_SERVER['HTTP_REFERER'] ?? false) {
                $added['referer'] = $_SERVER['HTTP_REFERER'];
            }
            $data['context'] = array_merge($added, $data['context'] ?? []);
        } catch (\Throwable $exception) {
        }
        return $data;
    }
}
