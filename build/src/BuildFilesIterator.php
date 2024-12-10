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

namespace Arnapou\SimpleSite\Build;

use AppendIterator;
use CallbackFilterIterator;
use FilesystemIterator;
use Iterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Traversable;

/**
 * @extends \IteratorIterator<int, SplFileInfo, \Traversable<int, SplFileInfo>>
 */
final class BuildFilesIterator extends IteratorIterator
{
    public function __construct(private readonly BuildConfig $config)
    {
        parent::__construct($this->buildIterator());
    }

    /**
     * @return Traversable<int, SplFileInfo>
     */
    private function buildIterator(): Traversable
    {
        $paths = array_map(
            fn ($dir) => $this->config->projectRootDir . "/$dir",
            $this->config->includedDirectories,
        );

        /** @var AppendIterator<int, SplFileInfo, Iterator<int, SplFileInfo>> $iterator */
        $iterator = new AppendIterator();
        foreach ($paths as $path) {
            $iterator->append(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $path,
                        FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO,
                    ),
                    RecursiveIteratorIterator::LEAVES_ONLY,
                ),
            );
        }

        return new CallbackFilterIterator($iterator, $this->filter(...));
    }

    private function filter(SplFileInfo $file): bool
    {
        if ($file->isDir()) {
            return false;
        }

        if (\in_array($file->getBasename(), $this->config->ignoredFilenames, true)) {
            return false;
        }

        foreach ($this->config->ignoredPathMatch as $pattern) {
            if (fnmatch($pattern, $file->getPathname())) {
                return false;
            }
        }

        return true;
    }
}
