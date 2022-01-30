<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Build;

final class BuildFilesIterator extends \IteratorIterator
{
    public function __construct()
    {
        $paths = array_map(static fn ($dir) => PROJECT_DIR . "/$dir", BUILD_INCLUDED_DIRS);

        parent::__construct($this->buildIterator($paths));
    }

    public function buildIterator(array $paths): \Iterator
    {
        $iterator = new \AppendIterator();

        foreach ($paths as $path) {
            $iterator->append(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $path,
                        \FilesystemIterator::KEY_AS_PATHNAME
                        | \FilesystemIterator::SKIP_DOTS
                        | \FilesystemIterator::CURRENT_AS_FILEINFO
                    ),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                )
            );
        }

        return new \CallbackFilterIterator(
            $iterator,
            static function (\SplFileInfo $file) {
                if ($file->isDir()) {
                    return false;
                }

                if (\in_array($file->getBasename(), BUILD_IGNORE_FILENAMES, true)) {
                    return false;
                }

                foreach (BUILD_IGNORE_PATHMATCH as $pattern) {
                    if (fnmatch($pattern, $file->getPathname())) {
                        return false;
                    }
                }

                return true;
            }
        );
    }
}
