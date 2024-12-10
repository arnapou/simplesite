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

use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class Pages
{
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * @return Generator<string, SplFileInfo>
     */
    public function list(): Generator
    {
        $allPages = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $pathPages = $this->config->path_pages,
                FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO,
            ),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $helper = new Helper();

        /** @var iterable<SplFileInfo> $allPages */
        foreach ($allPages as $item) {
            if ($item->isFile() && null !== ($ext = $helper->pageExtension($item->getBasename()))) {
                $url = substr($item->getPathname(), 1 + \strlen($pathPages));
                $url = substr($url, 0, -\strlen($ext) - 1);

                if ('index' === $url) {
                    $url = '';
                } elseif (str_ends_with($url, '/index')) {
                    $url = substr($url, 0, -5);
                }

                yield $url => $item;
            }
        }
    }
}
