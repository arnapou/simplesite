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

use Arnapou\SimpleSite\Core\Controller;
use DateTime;

use function strlen;

use Symfony\Component\HttpFoundation\Response;

class FallbackController extends Controller
{
    public function configure(): void
    {
        $this->addRoute('favicon.ico', [$this, 'routeFavicon'], 'favicon');
        $this->addRoute('robots.txt', [$this, 'routeRobotsTxt'], 'robots_txt');
    }

    public function routeFavicon(): Response
    {
        $binary = base64_decode(
            'AAABAAEAEBACAAEAAQCwAAAAFgAAACgAAAAQAAAAIAAAAAEAAQAAAAAAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
            . 'AA////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
            . 'AD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA'
        );

        $response = new Response(
            $binary,
            200,
            [
                'Content-Type' => 'image/vnd.microsoft.icon',
                'Content-Length' => strlen($binary),
            ]
        );

        $startDayTimestamp = (int) (floor(time() / 86400) * 86400);

        $response->setCache(
            [
                'etag' => base64_encode(hash('sha256', $binary, true)),
                'last_modified' => DateTime::createFromFormat('U', (string) $startDayTimestamp),
                'max_age' => 86400,
                's_maxage' => 86400,
                'public' => true,
            ]
        );

        return $response;
    }

    public function routeRobotsTxt(): Response
    {
        return new Response(
            "User-agent: *\nDisallow:\n",
            200,
            [
                'content-type' => 'text/plain',
            ]
        );
    }

    public function routePriority(): int
    {
        return 0;
    }
}
