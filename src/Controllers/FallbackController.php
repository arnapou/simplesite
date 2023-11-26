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

use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\Core\Utils;

final class FallbackController extends Controller
{
    public function configure(): void
    {
        $this->addRoute('favicon.ico', $this->routeFavicon(...), 'favicon');
        $this->addRoute('robots.txt', $this->routeRobotsTxt(...), 'robots_txt');
    }

    public function routeFavicon(): Response
    {
        $binary = base64_decode(
            'AAABAAEAEBACAAEAAQCwAAAAFgAAACgAAAAQAAAAIAAAAAEAAQAAAAAAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
            . 'AA////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
            . 'AD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA'
        );

        $startDayTimestamp = (int) (floor(time() / 86400) * 86400);
        $etag = base64_encode(hash('sha256', $binary, true));

        return Utils::cachedResponse($etag, 86400, $startDayTimestamp)
            ->withHeader('Content-Type', 'image/vnd.microsoft.icon')
            ->withHeader('Content-Length', (string) \strlen($binary))
            ->withBody($binary);
    }

    public function routeRobotsTxt(): Response
    {
        return (new Response())
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withBody("User-agent: *\nDisallow:\n");
    }

    public function routePriority(): int
    {
        return self::PRIORITY_HIGH;
    }
}
