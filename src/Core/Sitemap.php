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

use Psr\Http\Message\ServerRequestInterface;

/**
 * @see https://en.wikipedia.org/wiki/Sitemaps
 */
final readonly class Sitemap
{
    public function __construct(
        private Cache $cache,
        private Config $config,
        private Helper $helper,
    ) {
    }

    public function xmlCached(ServerRequestInterface $request): string
    {
        return $this->cache->from('arnapou.simplesite.sitemap.xml', fn () => $this->xml($request), 60);
    }

    public function xml(ServerRequestInterface $request): string
    {
        $baseUrl = $this->helper->baseUrl($request);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($this->files() as $url => $item) {
            $day = date('Y-m-d', (int) $item->getMTime());
            $xml .= "<url><lastmod>$day</lastmod><loc>$baseUrl$url</loc></url>\n";
        }
        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * @return \Generator<string, \SplFileInfo>
     */
    public function files(): \Generator
    {
        $allPages = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $pathPages = $this->config->path_pages,
                \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO,
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var iterable<\SplFileInfo> $allPages */
        foreach ($allPages as $item) {
            if ($item->isFile() && null !== ($ext = $this->helper->pageExtension($item->getBasename()))) {
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
