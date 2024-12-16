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
use Arnapou\SimpleSite\Core;
use Psr\Http\Message\ServerRequestInterface;

final class FallbackController extends Controller
{
    public function __construct(
        private readonly Core\Container $container,
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

    /**
     * @see https://robots-txt.com/
     */
    public function routeRobotsTxt(): Response
    {
        return new Response()
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader(new CacheControl()->setSharedMaxAge(300))
            ->withBody("User-agent: *\nDisallow:\n");
    }

    /**
     * @see https://en.wikipedia.org/wiki/Sitemaps
     */
    public function routeSitemapXml(ServerRequestInterface $request): Response
    {
        /** @var Core\Sitemap $sitemap */
        $sitemap = $this->container->get(Core\Sitemap::class);

        return new Response()
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/xml')
            ->withHeader(new CacheControl()->setSharedMaxAge(300))
            ->withBody($sitemap->xmlCached($request));
    }

    public function routePriority(): int
    {
        return self::PRIORITY_HIGH;
    }
}
