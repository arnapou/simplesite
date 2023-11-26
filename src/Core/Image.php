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

use Arnapou\Psr\Psr7HttpMessage\Response;
use GdImage;
use Imagick;
use ImagickException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

final class Image
{
    public const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly string $pathPublic,
    ) {
    }

    /**
     * @throws Problem
     * @throws InvalidArgumentException
     * @throws ImagickException
     */
    public function thumbnail(string $path, string $ext, int $size): ?Response
    {
        if (!is_file($filename = $this->pathPublic . "/$path.$ext")) {
            return null;
        }

        $filemtime = (int) filemtime($filename);
        $filesize = filesize($filename);
        $key = md5($path) . ".$size.$ext.$filemtime.$filesize";

        $content = $this->cache->get($key);
        if (!\is_string($content) || !$this->cache->has($key)) {
            $content = $this->imgResize($filename, strtolower($ext), $size);
            $this->logger->notice('Image resize', ['size' => $size]);
            $this->cache->set($key, $content);
        }

        return $this->fileResponse($content, strtolower($ext), $filemtime);
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
            $dst = imagecreate($w2, $h2) ?: throw Problem::imageError();
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
            default => throw new Problem()
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

    private function fileResponse(string $content, string $ext, int $filemtime): Response
    {
        $etag = base64_encode(hash('sha256', $content, true));

        return Utils::cachedResponse($etag, 864000, $filemtime)
            ->withHeader('Content-Type', self::MIME_TYPES[$ext])
            ->withBody($content);
    }
}
