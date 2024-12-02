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

use Arnapou\Psr\Psr7HttpMessage\Header\CacheControl;
use Arnapou\Psr\Psr7HttpMessage\Header\LastModified;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Utils
{
    private static ?AsciiSlugger $asciiSlugger = null;

    /**
     * @return array<string>
     */
    public static function findPhpFiles(string $path): array
    {
        // mandatory to use opendir family functions inside a Phar
        $files = [];
        if (\is_resource($dh = opendir($path))) {
            while ('' !== ($file = (string) readdir($dh))) {
                if (str_ends_with($file, '.php')) {
                    $files[] = $path . '/' . $file;
                }
            }
            closedir($dh);
        }
        sort($files);

        return $files;
    }

    /**
     * @throws Problem
     *
     * @return non-empty-string
     */
    public static function mkdir(string $path): string
    {
        if ('' === $path) {
            throw Problem::pathNotCreated($path);
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0o777, true) && !is_dir($path)) {
                throw Problem::pathNotCreated($path);
            }

            return $path;
        }

        if (!is_writable($path)) {
            throw Problem::pathNotWritable($path);
        }

        return $path;
    }

    public static function extension(string $filename): string
    {
        return str_ends_with($filename, '.html.twig')
            ? 'html.twig'
            : pathinfo($filename, \PATHINFO_EXTENSION);
    }

    public static function minifyHtml(string $source): string
    {
        $blocks = [];

        // protection
        $protection = static function (array $matches) use ($blocks): string {
            $num = \count($blocks);
            $key = "@@PROTECTED:$num:@@";
            $blocks[$key] = $matches[0];

            return $key;
        };

        $source = (string) preg_replace_callback('!<script[^>]*?>.*?</script>!si', $protection, $source);
        $source = (string) preg_replace_callback('!<pre[^>]*?>.*?</pre>!is', $protection, $source);
        $source = (string) preg_replace_callback('!<textarea[^>]*?>.*?</textarea>!is', $protection, $source);

        // minify
        $source = trim((string) preg_replace('/((?<!\?>)\n)\s+/m', '\1', $source));
        $source = (string) preg_replace('#<!---.*?--->#s', '', $source);
        $source = str_replace(["\t", "\n", "\r"], '', $source);

        // restoration before return
        return strtr($source, $blocks);
    }

    public static function slugify(string $text): string
    {
        $slugger = self::$asciiSlugger ?? (self::$asciiSlugger = new AsciiSlugger());

        return strtolower($slugger->slug($text)->toString());
    }

    public static function cacheControlResponse(string $etag, int $maxAge, int $lastModified): Response
    {
        return new Response()
            ->withStatus(200)
            ->withHeader('ETag', $etag)
            ->withHeader(new CacheControl()->setSharedMaxAge($maxAge))
            ->withHeader(new LastModified($lastModified));
    }
}
