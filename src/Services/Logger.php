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

use Arnapou\SimpleSite\Core\Psr\RotatingLogger;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

final class Logger implements ServiceFactory, LoggerInterface
{
    use LoggerTrait;

    private function __construct(
        private readonly ServiceContainer $container,
        private readonly RotatingLogger $logger
    ) {
    }

    public static function factory(ServiceContainer $container): self
    {
        $config = $container->config();

        return new self(
            $container,
            new RotatingLogger(
                $config->log_path,
                'site',
                $config->log_max_files,
                $config->log_level,
            )
        );
    }

    public static function aliases(): array
    {
        return ['log'];
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logger->log(
            $level,
            $message,
            $this->commonContext($context)
        );
    }

    private function commonContext(array $context): array
    {
        try {
            $context['url'] = $this->container->request()->getPathInfo();
            $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? '?';

            if (isset($_SERVER['HTTP_REFERER'])) {
                $context['referer'] = $_SERVER['HTTP_REFERER'];
            }
        } catch (\Throwable) {
        }

        return $context;
    }
}
