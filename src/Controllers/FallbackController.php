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

namespace Arnapou\SimpleSite\Controllers;

use Arnapou\Psr\Psr7HttpMessage\FileResponse;
use Arnapou\Psr\Psr7HttpMessage\Header\CacheControl;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\Core\Cache;
use Arnapou\SimpleSite\Core\Helper;
use Arnapou\SimpleSite\Core\Pages;
use Psr\Http\Message\ServerRequestInterface;

final class FallbackController extends Controller
{
    public function __construct(
        private readonly Cache $cache,
        private readonly Pages $pages,
    ) {
    }

    public function configure(): void
    {
        $this->addRoute('favicon.ico', $this->routeFavicon(...), 'favicon');
        $this->addRoute('robots.txt', $this->routeRobotsTxt(...), 'robots_txt');
        $this->addRoute('sitemap.xml', $this->routeSitemapXml(...), 'sitemap_xml');
    }

    public function routeFavicon(): Response
    {
        return new FileResponse(
            (string) base64_decode(
                'AAABAAEAEBACAAEAAQCwAAAAFgAAACgAAAAQAAAAIAAAAAEAAQAAAAAAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
                . 'AA////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
                . 'AD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA',
                true,
            ),
            'image/vnd.microsoft.icon',
        );
    }

    public function routeRobotsTxt(): Response
    {
        return new Response()
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader(new CacheControl()->setSharedMaxAge(300))
            ->withBody("User-agent: *\nDisallow:\n");
    }

    public function routeSitemapXml(ServerRequestInterface $request): Response
    {
        $xml = $this->cache->from('arnapou.simplesite.sitemap.xml', fn () => $this->getSitemapXmlContent($request), 60);

        return new Response()
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/xml')
            ->withHeader(new CacheControl()->setSharedMaxAge(300))
            ->withBody($xml);
    }

    private function getSitemapXmlContent(ServerRequestInterface $request): string
    {
        $baseUrl = new Helper()->getBaseUrl($request);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($this->pages->list() as $url => $item) {
            $day = date('Y-m-d', (int) $item->getMTime());
            $xml .= "<url><lastmod>$day</lastmod><loc>$baseUrl$url</loc></url>\n";
        }
        $xml .= "</urlset>\n";

        return $xml;
    }

    public function routePriority(): int
    {
        return self::PRIORITY_HIGH;
    }
}
