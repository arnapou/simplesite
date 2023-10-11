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

use Exception;

class Problem extends Exception
{
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
