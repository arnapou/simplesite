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

use Closure;
use FilesystemIterator;
use FilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @extends FilterIterator<int, SplFileInfo, \Traversable<int, SplFileInfo>>
 */
final class RecursiveFilesIterator extends FilterIterator
{
    protected readonly ?Closure $acceptCallback;

    public function __construct(string $path, ?callable $acceptCallback = null)
    {
        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::SKIP_DOTS
            | FilesystemIterator::CURRENT_AS_FILEINFO
        );

        $iterator = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $this->acceptCallback = null === $acceptCallback ? null : $acceptCallback(...);

        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        if (null !== $this->acceptCallback) {
            return (bool) ($this->acceptCallback)($this->current());
        }

        return true;
    }
}
