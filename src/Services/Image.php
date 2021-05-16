<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Services;

use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Exception\SimplesiteException;
use Arnapou\SimpleSite\Utils;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;

class Image
{
    public const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    private ServiceContainer  $container;
    private int               $tnExpire;
    private FilesystemAdapter $cache;
    private string            $pathPublic;

    private function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        $this->tnExpire = 86400 * 30;
        $this->pathPublic = $container->Config()->path_public();

        Utils::mkdir($directory = $container->Config()->path_cache() . '/images');
        $this->cache = new FilesystemAdapter('', $this->tnExpire, $directory);
    }

    public static function factory(ServiceContainer $container): self
    {
        return new self($container);
    }

    public static function aliases(): array
    {
        return [];
    }

    public function __destruct()
    {
        try {
            // 1 chance sur 1000 pour declencher le prune automatiquement
            if (0 === random_int(0, 1000)) {
                $this->cache->prune();
            }
        } catch (\Throwable) {
        }
    }

    public function thumbnail(string $path, string $ext, int $size): ?Response
    {
        $filename = $this->pathPublic . "/$path.$ext";
        if (is_file($filename)) {
            $filemtime = filemtime($filename);
            $filesize = filesize($filename);
            $key = md5($path) . ".$size.$ext.$filemtime.$filesize";
            $item = $this->cache->getItem($key);
            if (!$item->isHit()) {
                $this->container->Logger()->notice('Image resize', ['size' => $size]);
                $item->set($this->imgResize($filename, strtolower($ext), $size));
                $item->expiresAfter($this->tnExpire);
                $this->cache->save($item);
            }

            return $this->fileResponse($item->get(), strtolower($ext), $filemtime);
        }

        return null;
    }

    private function imgResize(string $filename, string $ext, int $size): string
    {
        if (class_exists('Imagick')) {
            return $this->imgResizeImagick($filename, $ext, $size);
        }

        if (\function_exists('imagecreatefromjpeg')) {
            return $this->imgResizeGD($filename, $ext, $size);
        }

        throw new SimplesiteException();
    }

    private function imgResizeImagick(string $filename, string $ext, int $size): string
    {
        $img = new \Imagick($filename);
        $w1 = $img->getImageWidth();
        $h1 = $img->getImageHeight();
        [$w2, $h2] = $this->newSize($w1, $h1, $size);
        $img->resizeImage($w2, $h2, \Imagick::FILTER_LANCZOS, 1);

        return $img->getImageBlob();
    }

    private function newSize(int $width, int $height, int $size): array
    {
        return $width > $height
            ? [$size, (int) floor($size * $height / $width)]
            : [(int) floor($size * $width / $height), $size];
    }

    private function imgResizeGD(string $filename, string $ext, int $size): string
    {
        $resize = function (mixed $img) use ($size): mixed {
            $w1 = imagesx($img);
            $h1 = imagesy($img);
            [$w2, $h2] = $this->newSize($w1, $h1, $size);
            $w2 = (int) $w2;
            $h2 = (int) $h2;
            $dst = imagecreate($w2, $h2);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $w2, $h2, $w1, $h1);

            return $dst;
        };

        $binary = static function (callable $process): string {
            ob_start();
            $ok = $process();
            if (!$ok) {
                throw new SimplesiteException();
            }

            return ob_get_clean();
        };

        return match ($ext) {
            'jpg' => $binary(fn () => imagejpeg($resize(imagecreatefromjpeg($filename)), null, 95)),
            'png' => $binary(fn () => imagepng($resize(imagecreatefrompng($filename)), null, 9)),
            'gif' => $binary(fn () => imagegif($resize(imagecreatefromgif($filename)), null)),
            default => throw new SimplesiteException()
        };
    }

    private function fileResponse(string $content, string $ext, int $filemtime): Response
    {
        $response = new Response($content);
        $response->headers->set('Content-Type', self::MIME_TYPES[$ext]);
        $response->setCache(
            [
                'etag' => base64_encode(hash('sha256', $content, true)),
                'last_modified' => \DateTime::createFromFormat('U', (string) $filemtime),
                'max_age' => 864000,
                's_maxage' => 864000,
                'public' => true,
            ]
        );

        return $response;
    }
}
