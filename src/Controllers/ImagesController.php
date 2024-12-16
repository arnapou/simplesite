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
use Arnapou\SimpleSite\Core;

final class ImagesController extends Controller
{
    private const string REGEX_EXT = '[jJ][pP][gG]|[pP][nN][gG]|[gG][iI][fF]';

    public function __construct(
        private readonly Core\Container $container,
        private readonly Core\Config $config,
    ) {
    }

    public function configure(): void
    {
        $this->addRoute('{path}.{size}.{ext}', $this->routeImageResize(...), 'images')
            ->setRequirement('path', '.*')
            ->setRequirement('size', '\d+')
            ->setRequirement('ext', self::REGEX_EXT);
    }

    public function routeImageResize(string $path, string $ext, string $size): Response
    {
        $filename = $this->findFile($path, $ext) ?? throw new NoResponseFound();
        $intsize = $this->getSize($size) ?? throw new NoResponseFound();

        /** @var Core\Image $image */
        $image = $this->container->get(Core\Image::class);

        return $image->thumbnail($filename, $ext, $intsize) ?? throw new NoResponseFound();
    }

    private function findFile(string $path, string $ext): ?string
    {
        if (is_file($filename = $this->config->path_public . "/$path.$ext")) {
            return $filename;
        }
        if (is_file($filename = $this->config->path_pages . "/$path.$ext")) {
            return $filename;
        }

        return null;
    }

    private function getSize(string $size): ?int
    {
        return !ctype_digit($size) || (int) $size < 16 || (int) $size > 2000 ? null : (int) $size;
    }

    public function routePriority(): int
    {
        return self::PRIORITY_HIGH;
    }
}
