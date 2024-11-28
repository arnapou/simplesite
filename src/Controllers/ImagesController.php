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

use Arnapou\Psr\Psr15HttpHandlers\Exception\NoResponseFound;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\SimpleSite;

final class ImagesController extends Controller
{
    public function configure(): void
    {
        $this->addRoute('{path}.{size}.{ext}', $this->routeImage(...), 'images')
            ->setRequirement('path', '.*')
            ->setRequirement('size', '[0-9]{2,4}')
            ->setRequirement('ext', '[jJ][pP][gG]|[pP][nN][gG]|[gG][iI][fF]');
    }

    public function routeImage(string $path, string $size, string $ext): Response
    {
        if (!ctype_digit($size)) {
            throw new NoResponseFound();
        }

        $intsize = (int) $size;
        if ($intsize < 16 || $intsize > 2000) {
            throw new NoResponseFound();
        }

        $response = SimpleSite::image()->thumbnail($path, $ext, $intsize);
        if (null === $response) {
            throw new NoResponseFound();
        }

        return $response;
    }

    public function routePriority(): int
    {
        return self::PRIORITY_HIGH;
    }
}
