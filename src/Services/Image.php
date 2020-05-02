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

namespace Arnapou\SimpleSite\Services;

use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Utils;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;

class Image
{
    /**
     * @var ServiceContainer
     */
    private $container;
    /**
     * @var int
     */
    private $tnExpire;
    /**
     * @var FilesystemAdapter
     */
    private $cache;
    /**
     * @var string
     */
    private $pathPublic;
    /**
     * @var array
     */
    private $mimeTypes = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    public function __construct(ServiceContainer $container)
    {
        $this->container  = $container;
        $this->tnExpire   = 86400 * 30;
        $this->pathPublic = $container->Config()->path_public();

        Utils::mkdir($directory = $container->Config()->path_cache() . '/images');
        $this->cache = new FilesystemAdapter('', $this->tnExpire, $directory);
    }

    public static function factory(ServiceContainer $container)
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
            // 1 chance sur 100 pour declencher le prune automatiquement
            if (mt_rand(0, 1000) === 0) {
                $this->cache->prune();
            }
        } catch (\Throwable $exception) {
        }
    }

    public function thumbnail(string $path, string $ext, int $size): ?Response
    {
        $filename = $this->pathPublic . "/$path.$ext";
        if (is_file($filename)) {
            $filemtime = filemtime($filename);
            $filesize  = filesize($filename);
            $key       = md5($path) . ".$size.$ext.$filemtime.$filesize";
            $item      = $this->cache->getItem($key);
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
        } elseif (\function_exists('imagecreatefromjpeg')) {
            return $this->imgResizeGD($filename, $ext, $size);
        }
        throw new \RuntimeException();
    }

    private function imgResizeImagick(string $filename, string $ext, int $size): string
    {
        $img = new \Imagick($filename);
        $w1  = $img->getImageWidth();
        $h1  = $img->getImageHeight();
        [$w2, $h2] = $this->newSize($w1, $h1, $size);
        $img->resizeImage($w2, $h2, \Imagick::FILTER_LANCZOS, 1);
        return $img->getImageBlob();
    }

    private function newSize($width, $height, $size): array
    {
        return $width > $height
            ? [$size, floor($size * $height / $width)]
            : [floor($size * $width / $height), $size];
    }

    private function imgResizeGD(string $filename, string $ext, int $size): string
    {
        $resize = function ($img) use ($size) {
            $w1 = imagesx($img);
            $h1 = imagesy($img);
            [$w2, $h2] = $this->newSize($w1, $h1, $size);
            $dst = imagecreate($w2, $h2);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $w2, $h2, $w1, $h1);
            return $dst;
        };
        switch ($ext) {
            case 'jpg':
                ob_start();
                imagejpeg($resize(imagecreatefromjpeg($filename)), null, 95);
                return ob_get_clean();
            case 'png':
                ob_start();
                imagepng($resize(imagecreatefrompng($filename)), null, 9);
                return ob_get_clean();
            case 'gif':
                ob_start();
                imagegif($resize(imagecreatefromgif($filename)), null);
                return ob_get_clean();
        }
        throw new \RuntimeException();
    }

    private function fileResponse($content, $ext, $filemtime): Response
    {
        $response = new Response($content);
        $response->headers->set('Content-Type', $this->mimeTypes[$ext]);
        $response->setCache(
            [
                'etag'          => base64_encode(hash('sha256', $content, true)),
                'last_modified' => \DateTime::createFromFormat('U', $filemtime),
                'max_age'       => 864000,
                's_maxage'      => 864000,
                'public'        => true,
            ]
        );
        return $response;
    }
}
