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

use Arnapou\SimpleSite\Core\Psr\FilesystemCache;
use Arnapou\SimpleSite\Core\Psr\Probability;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Arnapou\SimpleSite\Exception\ImageError;
use Arnapou\SimpleSite\Exception\SimplesiteProblem;
use DateTime;

use function extension_loaded;

use GdImage;
use Imagick;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class Image implements ServiceFactory
{
    public const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    private function __construct(
        private readonly LoggerInterface $logger,
        private readonly FilesystemCache $cache,
        private readonly string $pathPublic,
    ) {
    }

    public static function factory(ServiceContainer $container): self
    {
        $config = $container->config();

        return new self(
            $container->logger(),
            new FilesystemCache(
                $config->path_cache . '/images',
                86400 * 30,
                new Probability(1, 1000)
            ),
            $config->path_public
        );
    }

    public static function aliases(): array
    {
        return ['img'];
    }

    public function thumbnail(string $path, string $ext, int $size): ?Response
    {
        if (!is_file($filename = $this->pathPublic . "/$path.$ext")) {
            return null;
        }

        $filemtime = (int) filemtime($filename);
        $filesize = filesize($filename);
        $key = md5($path) . ".$size.$ext.$filemtime.$filesize";

        $content = $this->cache->get($key);
        if (null === $content || !$this->cache->has($key)) {
            $content = $this->imgResize($filename, strtolower($ext), $size);
            $this->logger->notice('Image resize', ['size' => $size]);
            $this->cache->set($key, $content);
        }

        return $this->fileResponse($content, strtolower($ext), $filemtime);
    }

    private function imgResize(string $filename, string $ext, int $size): string
    {
        return $this->tryResizeImagick($filename, $ext, $size)
            ?? $this->tryResizeGD($filename, $ext, $size)
            ?? throw new SimplesiteProblem();
    }

    private function tryResizeImagick(string $filename, string $ext, int $size): ?string
    {
        if (!extension_loaded('imagick')) {
            return null;
        }

        $img = new Imagick($filename);
        [$w1, $h1] = [$img->getImageWidth(), $img->getImageHeight()];
        [$w2, $h2] = $this->newSize($w1, $h1, $size);
        $img->resizeImage($w2, $h2, Imagick::FILTER_LANCZOS, 1);

        return $img->getImageBlob();
    }

    private function tryResizeGD(string $filename, string $ext, int $size): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $resize = function (false|GdImage $img) use ($size): GdImage {
            if (false === $img) {
                throw new ImageError();
            }

            [$w1, $h1] = [imagesx($img), imagesy($img)];
            [$w2, $h2] = $this->newSize($w1, $h1, $size);
            $dst = imagecreate($w2, $h2) ?: throw new ImageError();
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $w2, $h2, $w1, $h1);

            return $dst;
        };

        $binary = static function (callable $process): string {
            ob_start();
            $ok = $process();
            if (!$ok) {
                throw new SimplesiteProblem();
            }

            return (string) ob_get_clean();
        };

        return match ($ext) {
            'jpg' => $binary(fn () => imagejpeg($resize(imagecreatefromjpeg($filename)), null, 95)),
            'png' => $binary(fn () => imagepng($resize(imagecreatefrompng($filename)), null, 9)),
            'gif' => $binary(fn () => imagegif($resize(imagecreatefromgif($filename)), null)),
            default => throw new SimplesiteProblem()
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
        $response = new Response($content);
        $response->headers->set('Content-Type', self::MIME_TYPES[$ext]);
        $response->setCache(
            [
                'etag' => base64_encode(hash('sha256', $content, true)),
                'last_modified' => DateTime::createFromFormat('U', (string) $filemtime),
                'max_age' => 864000,
                's_maxage' => 864000,
                'public' => true,
            ]
        );

        return $response;
    }
}
