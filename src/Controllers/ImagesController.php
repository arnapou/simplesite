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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ImagesController extends Controller
{
    public function configure(): void
    {
        $this->addRoute('{path}.{size}.{ext}', [$this, 'routeImage'], 'images')
            ->setRequirement('path', '.*')
            ->setRequirement('size', '[0-9]{1,4}')
            ->setRequirement('ext', '[jJ][pP][gG]|[pP][nN][gG]|[gG][iI][fF]');
    }

    public function routeImage(string $path, int $size, string $ext)
    {
        if ($size > 16 && $size <= 1500) {
            if ($response = $this->container()->Image()->thumbnail($path, $ext, $size)) {
                return $response;
            }
        }
        throw new ResourceNotFoundException();
    }
    public function routePriority(): int
    {
        return 100;
    }
}
