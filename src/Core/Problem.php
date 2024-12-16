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

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusServerError;

class Problem extends \Exception
{
    private StatusClientError|StatusServerError|null $status;

    public function __construct(string $message = '', int|StatusClientError|StatusServerError $code = 0, ?\Throwable $previous = null)
    {
        if (\is_int($code)) {
            $this->status = null;
            parent::__construct($message, $code, $previous);
        } else {
            $this->status = $code;
            parent::__construct($message, $code->value, $previous);
        }
    }

    public function getStatus(): StatusServerError|StatusClientError|null
    {
        return $this->status;
    }

    public static function fromStatus(StatusClientError|StatusServerError $status): self
    {
        return new self("$status->name.", $status);
    }

    public static function emptyVariable(string $name): self
    {
        return new self("Config variable '$name' cannot be empty");
    }

    public static function pathNotCreated(string $path): self
    {
        return new self("The path '$path' could not be created.");
    }

    public static function pathNotExists(string $path): self
    {
        return new self("The path '$path' does not exists.");
    }

    public static function pathNotWritable(string $path): self
    {
        return new self("The path '$path' is not writable.");
    }

    public static function imageError(): self
    {
        return new self('Image processing error.');
    }
}
