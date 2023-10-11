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
use Arnapou\Psr\Psr3Logger\Formatter\JsonContextFormatter;
use JsonSerializable;
use Stringable;
use Throwable;
use Traversable;

final class LogContextFormatter implements ContextFormatter
{
    private JsonContextFormatter $json;

    public function __construct()
    {
        $this->json = new JsonContextFormatter();
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
                $value instanceof JsonSerializable,
                $value instanceof Traversable => $this->json->formatContext([$key => $value]),
                $value instanceof Throwable => $value->getMessage() . ' ' . $this->json->formatContext([$key => $value]),
                $value instanceof Stringable,
                \is_object($value) && method_exists($value, '__toString') => (string) $value,
                default => 'type: ' . get_debug_type($value),
            };
        }

        $string = implode(', ', $elements);

        return '' === $string ? '' : "($string)";
    }
}
