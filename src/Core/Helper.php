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

use Arnapou\Ensure\Enforce;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Yaml\Yaml;

final readonly class Helper
{
    /**
     * @return non-empty-string|null
     */
    public function pageExtension(string $filename): ?string
    {
        $ext = $this->fileExtension($filename);

        return \in_array($ext, Config::PAGE_EXTENSIONS, true) ? $ext : null;
    }

    public function fileExtension(string $filename): string
    {
        return pathinfo($filename, \PATHINFO_EXTENSION);
    }

    public function slugify(string $text): string
    {
        return strtolower(new AsciiSlugger()->slug($text)->toString());
    }

    /**
     * @return array<mixed>
     */
    public function yamlParse(string $yaml): array
    {
        $result = \function_exists('yaml_parse') ? yaml_parse($yaml) : Yaml::parse($yaml);

        return \is_array($result) ? $result : [];
    }

    public function minifyHtml(string $source): string
    {
        // protection
        $blocks = [];
        $protection = static function (array $matches) use (&$blocks): string {
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
        $source = (string) preg_replace('#<!--.*?-->#s', '', $source);
        $source = (string) preg_replace('#<\?xml .*?\?>#s', '', $source);
        $source = str_replace(["\t", "\n", "\r"], '', $source);

        // restoration before return
        return strtr($source, $blocks);
    }

    public function getBaseUrl(ServerRequestInterface $request): string
    {
        $parsed = parse_url((string) $request->getUri());
        $scheme = Enforce::string($parsed['scheme'] ?? 'http');
        $host = Enforce::string($parsed['host'] ?? 'localhost');
        $port = Enforce::string($parsed['port'] ?? '80');

        $domain = match ($scheme) {
            'http' => \in_array($port, ['80', ''], true) ? $host : "$host:$port",
            'https' => \in_array($port, ['443', ''], true) ? $host : "$host:$port",
            default => "$host:$port",
        };

        return "$scheme://$domain/";
    }

    public function toSnakeCase(string $str): string
    {
        $str = (string) preg_replace('/([a-z])([A-Z])/', '\\1 \\2', $str);
        $str = str_replace('-', '_', $this->slugify($str));

        return strtolower($str);
    }

    public function toCamelCase(string $str, bool $ucfirst = false): string
    {
        $str = (string) preg_replace('/([a-z])([A-Z])/', '\\1 \\2', $str);
        $str = str_replace('-', ' ', $this->slugify($str));
        $str = str_replace(' ', '', ucwords(strtolower($str)));

        return $ucfirst ? $str : lcfirst($str);
    }

    public function svgSymbol(string $filename, ?string $name = null): string
    {
        $name ??= $this->slugify(basename($filename, '.' . $this->fileExtension($filename)));

        return $this->minifyHtml(
            (string) preg_replace(
                '!<svg .*? (viewbox=".*?")>(.*)</svg>$!i',
                '<symbol id="' . $name . '" $1>$2</symbol>',
                (string) file_get_contents($filename),
            ),
        );
    }

    public function svgSymbolUse(string $name): string
    {
        return '<svg version="2.0">' . ('' === $name ? '' : '<use href="#' . htmlentities($name) . '" />') . '</svg>';
    }
}
