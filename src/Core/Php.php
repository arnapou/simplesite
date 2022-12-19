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

use const E_ALL;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

use ErrorException;

use function get_class;

use Phar;

use const PHP_SAPI;

use Throwable;

final class Php
{
    public static function setErrorReporting(): void
    {
        error_reporting(E_ALL & ~E_USER_DEPRECATED);
    }

    public static function getExceptionHandler(): callable
    {
        return static function (Throwable $exception): void {
            if ('cli' === PHP_SAPI) {
                self::throwableToText($exception);
            } else {
                self::throwableToHtml($exception);
            }
        };
    }

    public static function getErrorHandler(): callable
    {
        return static function (int $errno, string $errstr, string $errfile, int $errline): void {
            switch ($errno) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_CORE_WARNING:
                case E_COMPILE_ERROR:
                case E_COMPILE_WARNING:
                case E_USER_ERROR:
                    error_clear_last();
                    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                case E_WARNING:
                case E_NOTICE:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                case E_STRICT:
                case E_RECOVERABLE_ERROR:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                default:
                    // we ignore it
            }
        };
    }

    public static function getShutdownHandler(): callable
    {
        return static function (): void {
            $error = error_get_last();
            if (!empty($error['message'])) {
                throw new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            }
        };
    }

    public static function throwableToArray(Throwable $throwable): array
    {
        $array = [];
        $array['class'] = get_class($throwable);
        $array['message'] = $throwable->getMessage();
        $array['file'] = $throwable->getFile() . '(' . $throwable->getLine() . ')';
        if ($throwable->getCode()) {
            $array['code'] = $throwable->getCode();
        }
        $array['trace'] = explode("\n", $throwable->getTraceAsString());

        if ($throwable->getPrevious()) {
            $array['previous'] = self::throwableToArray($throwable->getPrevious());
        }

        return $array;
    }

    private static function throwableToText(Throwable $exception): void
    {
        while ($exception) {
            echo '  class: ' . get_class($exception) . "\n";
            echo 'message: ' . $exception->getMessage() . "\n";
            echo '   file: ' . $exception->getFile() . "\n";
            echo '   line: ' . $exception->getLine() . "\n";
            if ($exception->getCode()) {
                echo '   code: ' . $exception->getCode() . "\n";
            }
            echo '  trace: ' . ltrim(self::traceAsStringWithMarginLeft($exception, '         ')) . "\n";
            if ($exception = $exception->getPrevious()) {
                echo "\n";
            }
        }
    }

    private static function throwableToHtml(Throwable $exception): void
    {
        echo '<pre style="color: red">';
        echo '<div class="alert alert-danger" role="alert">';
        self::throwableToText($exception);
        echo '</div>';
        echo '</pre>';
    }

    private static function traceAsStringWithMarginLeft(Throwable $exception, string $margin): string
    {
        return implode(
            "\n",
            array_map(
                static fn (string $line): string => $margin . trim($line),
                explode("\n", trim($exception->getTraceAsString()))
            )
        );
    }

    public static function inPhar(): bool
    {
        return class_exists(Phar::class) && Phar::running();
    }
}
