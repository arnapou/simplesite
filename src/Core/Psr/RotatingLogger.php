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

namespace Arnapou\SimpleSite\Core\Psr;

use Arnapou\SimpleSite\Core\Php;
use Arnapou\SimpleSite\Exception\SimplesiteProblem;

use function array_slice;
use function gettype;
use function is_string;

use JsonSerializable;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use Traversable;

final class RotatingLogger implements LoggerInterface
{
    use LoggerTrait;

    private readonly int $minLogLevelRfc5424;
    /** @var resource|null */
    private mixed $fileHandle = null;

    /**
     * @param LogLevel::* $minLogLevel
     */
    public function __construct(
        private readonly string $directory,
        private readonly string $name,
        private readonly int $maxFiles,
        private readonly string $minLogLevel,
    ) {
        $this->minLogLevelRfc5424 = $this->levelToRfc5424($this->minLogLevel);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        if (
            !is_string($level)
            || '' === ($stringMessage = (string) $message)
            || $this->levelToRfc5424($level) > $this->minLogLevelRfc5424
        ) {
            return;
        }

        $this->write(
            strtoupper($level),
            $stringMessage,
            [] === $context ? '' : (string) json_encode($this->sanitizeContext($context))
        );
    }

    private function write(string $level, string $message, string $context): void
    {
        if (null === $this->fileHandle) {
            $filename = $this->getFilename();
            $this->fileHandle = @fopen($filename, 'a')
                ?: throw new SimplesiteProblem("Unable to write to log '$filename'.");
        }

        $now = date('Y-m-d H:i:s');

        fwrite($this->fileHandle, "[$now] $level $message $context\n");
    }

    public function __destruct()
    {
        if (null !== $this->fileHandle) {
            @fclose($this->fileHandle);
            $this->fileHandle = null;
        }

        $this->rotate();
    }

    /**
     * @see https://www.rfc-editor.org/rfc/rfc5424
     *
     * @param string|LogLevel::* $level
     */
    private function levelToRfc5424(string $level): int
    {
        return match ($level) {
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT => 1,
            LogLevel::CRITICAL => 2,
            LogLevel::ERROR => 3,
            LogLevel::WARNING => 4,
            LogLevel::NOTICE => 5,
            LogLevel::INFO => 6,
            default => 7,
        };
    }

    /**
     * @return array<bool|int|float|string|array|null>
     */
    private function sanitizeContext(array $context): array
    {
        $output = [];
        foreach ($context as $key => $value) {
            $output[$key] = match ($type = gettype($value)) {
                'boolean', 'integer', 'double', 'string', 'NULL' => $value,
                'array' => $this->sanitizeContext($value),
                'object' => match (true) {
                    $value instanceof JsonSerializable => $value,
                    $value instanceof Traversable => iterator_to_array($value),
                    $value instanceof Stringable, method_exists($value, '__toString') => (string) $value,
                    $value instanceof Throwable => Php::throwableToArray($value),
                    default => get_debug_type($value),
                },
                default => "type: $type",
            };
        }

        return $output;
    }

    private function getFilename(): string
    {
        return $this->directory . '/' . $this->name . '-' . date('Y-m-d') . '.log';
    }

    private function rotate(): void
    {
        $files = glob($this->directory . '/' . $this->name . '-*.log') ?: [];

        usort($files, static fn ($a, $b) => strcmp($b, $a));

        foreach (array_slice($files, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                set_error_handler(static fn (int $errno, string $errstr, string $errfile, int $errline): bool => false);
                unlink($file);
                restore_error_handler();
            }
        }
    }
}
