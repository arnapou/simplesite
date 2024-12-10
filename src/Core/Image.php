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

use Arnapou\Psr\Psr7HttpMessage\FileResponse;
use Arnapou\Psr\Psr7HttpMessage\Response;
use GdImage;
use Imagick;
use ImagickException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;

final class Image
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Cache $cache,
    ) {
    }

    /**
     * @throws Problem
     * @throws InvalidArgumentException
     * @throws ImagickException
     */
    public function thumbnail(string $filename, string $ext, ?int $size): ?Response
    {
        if (!is_file($filename)) {
            return null;
        }

        $filemtime = (int) filemtime($filename);
        if (null === $size) {
            $content = (string) file_get_contents($filename);
        } else {
            $content = $this->cache->from(
                'arnapou.simplesite.' . sha1($filename) . ".$size.$ext.$filemtime." . filesize($filename),
                function () use ($filename, $ext, $size) {
                    $content = $this->imgResize($filename, strtolower($ext), $size);
                    $this->logger->notice('Image resize', ['size' => $size]);

                    return $content;
                },
            );
        }

        return new FileResponse($content, Config::IMAGE_MIME_TYPES[strtolower($ext)]);
    }

    /**
     * @throws Problem
     * @throws ImagickException
     */
    private function imgResize(string $filename, string $ext, int $size): string
    {
        return $this->tryResizeImagick($filename, $size)
            ?? $this->tryResizeGD($filename, $ext, $size)
            ?? throw new Problem();
    }

    /**
     * @throws ImagickException
     */
    private function tryResizeImagick(string $filename, int $size): ?string
    {
        if (!\extension_loaded('imagick')) {
            return null;
        }

        $img = new Imagick($filename);
        [$w1, $h1] = [$img->getImageWidth(), $img->getImageHeight()];
        [$w2, $h2] = $this->newSize($w1, $h1, $size);
        $img->resizeImage($w2, $h2, Imagick::FILTER_LANCZOS, 1);

        return $img->getImageBlob();
    }

    /**
     * @throws Problem
     */
    private function tryResizeGD(string $filename, string $ext, int $size): ?string
    {
        if (!\extension_loaded('gd')) {
            return null;
        }

        $resize = function (false|GdImage $img) use ($size): GdImage {
            if (false === $img) {
                throw Problem::imageError();
            }

            [$w1, $h1] = [imagesx($img), imagesy($img)];
            [$w2, $h2] = $this->newSize($w1, $h1, $size);
            $dst = imagecreate(max(1, $w2), max(1, $h2));
            if (false === $dst) {
                throw Problem::imageError();
            }
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $w2, $h2, $w1, $h1);

            return $dst;
        };

        $binary = static function (callable $process): string {
            ob_start();
            $ok = $process();
            if (!$ok) {
                throw new Problem();
            }

            return (string) ob_get_clean();
        };

        return match ($ext) {
            'jpg' => $binary(fn () => imagejpeg($resize(imagecreatefromjpeg($filename)), null, 95)),
            'png' => $binary(fn () => imagepng($resize(imagecreatefrompng($filename)), null, 9)),
            'gif' => $binary(fn () => imagegif($resize(imagecreatefromgif($filename)), null)),
            default => throw new Problem(),
        };
    }

    /**
     * @return array{int, int}
     */
    private function newSize(int $width, int $height, int $size): array
    {
        return $width > $height
            ? [$size, (int) floor($size * $height / $width)]
            : [(int) floor($size * $width / $height), $size];
    }
}
