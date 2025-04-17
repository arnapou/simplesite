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

use Arnapou\Psr\Psr3Logger\Formatter\ContextFormatter;
use Arnapou\Psr\Psr3Logger\Formatter\DefaultLogFormatter;
use Arnapou\Psr\Psr3Logger\Formatter\JsonContextFormatter;
use Arnapou\Psr\Psr3Logger\Utils\Psr3Level;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LogFormatter implements \Arnapou\Psr\Psr3Logger\Formatter\LogFormatter, ContextFormatter
{
    private DefaultLogFormatter $logFormatter;
    private JsonContextFormatter $contextFormatter;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        $this->logFormatter = new DefaultLogFormatter('Y-m-d H:i:s', $this);
        $this->contextFormatter = new JsonContextFormatter(JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT);
    }

    public function formatLine(\DateTimeImmutable $date, Psr3Level $level, string $message, array $context): string
    {
        return $this->logFormatter->formatLine($date, $level, $message, $this->commonContext() + $context);
    }

    public function formatContext(array $context): string
    {
        $elements = [];
        foreach ($context as $key => $value) {
            $elements[] = "$key=" . match (true) {
                \is_bool($value),
                \is_int($value),
                \is_float($value),
                \is_string($value) => $value,
                null === $value => 'NULL',
                \is_array($value),
                $value instanceof \JsonSerializable,
                $value instanceof \Traversable => $this->contextFormatter->formatContext([$key => $value]),
                $value instanceof \Throwable => $value->getMessage() . ' ' . $this->contextFormatter->formatContext([$key => $value]),
                $value instanceof \Stringable,
                \is_object($value) && method_exists($value, '__toString') => (string) $value,
                default => 'type: ' . get_debug_type($value),
            };
        }

        $string = implode(', ', $elements);

        return '' === $string ? '' : "($string)";
    }

    /**
     * @return array<mixed>
     */
    private function commonContext(): array
    {
        $common = [];
        try {
            /** @var ServerRequestInterface $serverRequest */
            $serverRequest = $this->container->get(ServerRequestInterface::class);
            $serverParams = $serverRequest->getServerParams();

            $common['url'] = (string) $serverRequest->getUri();
            $common['ip'] = $serverParams['HTTP_X_FORWARDED_FOR'] ?? $serverParams['REMOTE_ADDR'] ?? '?';

            if (isset($serverParams['HTTP_REFERER'])) {
                $common['referer'] = $serverParams['HTTP_REFERER'];
            }
        } catch (\Throwable) {
        }

        return $common;
    }
}
