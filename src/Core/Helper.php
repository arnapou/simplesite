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
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Yaml\Yaml;
use Twig\Source;

final readonly class Helper
{
    public function __construct(
        private Config $config,
        private HttpRouteHandler $router,
        private TwigLoader $twigLoader,
    ) {
    }

    public function asset(string $path): string
    {
        return $this->config->base_path_root . ltrim($path, '/');
    }

    public function baseUrl(ServerRequestInterface $request, bool $withScheme = true): string
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

        return $withScheme ? "$scheme://$domain/" : $domain;
    }

    /**
     * @return array<mixed>
     */
    public function data(string $view, bool $strict = false): array
    {
        if (!$strict && !$this->twigLoader->exists($view)) {
            return [];
        }

        $source = $this->view($view)->getCode();

        $result = match (strtolower($this->fileExtension($view))) {
            'yml', 'yaml' => $this->yamlDecode($source),
            'json' => json_decode($source, true),
            default => null,
        };

        return \is_array($result) ? $result : [];
    }

    public function fileExtension(string $filename): string
    {
        return pathinfo($filename, \PATHINFO_EXTENSION);
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

    /**
     * @return non-empty-string|null
     */
    public function pageExtension(string $filename): ?string
    {
        $ext = $this->fileExtension($filename);

        return \in_array($ext, Config::PAGE_EXTENSIONS, true) ? $ext : null;
    }

    /**
     * @param array<string,string|int|float> $parameters
     */
    public function path(string $name, array $parameters = []): string
    {
        $url = $this->router->generateUrl($name, $parameters);

        return '//' === $url ? '/' : $url;
    }

    public function pathDir(string $path): string
    {
        return $this->path('static_dir', ['path' => $path]);
    }

    public function pathPage(string $path): string
    {
        return $this->path('static_page', ['path' => $path]);
    }

    public function replaceExtension(string $filename, string $extension): string
    {
        return substr($filename, 0, -\strlen($this->fileExtension($filename))) . $extension;
    }

    public function slugify(string $text): string
    {
        return strtolower(new AsciiSlugger()->slug($text)->toString());
    }

    public function svgSymbol(string $view, ?string $name = null): string
    {
        $filename = $this->view($view)->getPath();
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

    public function throwableToText(\Throwable $throwable): string
    {
        $text = '';
        while ($throwable) {
            $text .= '  class: ' . $throwable::class . "\n";
            $text .= 'message: ' . $throwable->getMessage() . "\n";
            $text .= '   file: ' . $throwable->getFile() . '(' . $throwable->getLine() . ')' . "\n";
            if (0 !== $throwable->getCode()) {
                $text .= '   code: ' . $throwable->getCode() . "\n";
            }
            $text .= '  trace: ' . ltrim(
                implode(
                    "\n",
                    array_map(
                        static fn (string $line): string => '         ' . trim($line),
                        explode("\n", trim($throwable->getTraceAsString())),
                    ),
                ),
            ) . "\n";
            if (null !== ($throwable = $throwable->getPrevious())) {
                $text .= "\n";
            }
        }

        return "\n$text\n";
    }

    public function thumbnail(string $path, int $size = 200): string
    {
        if (\array_key_exists($ext = strtolower($this->fileExtension($path)), Config::IMAGE_MIME_TYPES)) {
            $path = substr($path, 0, -\strlen($ext)) . $size . '.' . substr($path, -\strlen($ext));
        }

        return $this->asset($path);
    }

    public function toCamelCase(string $str, bool $ucfirst = false): string
    {
        $str = (string) preg_replace('/([a-z])([A-Z])/', '\\1 \\2', $str);
        $str = str_replace('-', ' ', $this->slugify($str));
        $str = str_replace(' ', '', ucwords(strtolower($str)));

        return $ucfirst ? $str : lcfirst($str);
    }

    public function toSnakeCase(string $str): string
    {
        $str = (string) preg_replace('/([a-z])([A-Z])/', '\\1 \\2', $str);
        $str = str_replace('-', '_', $this->slugify($str));

        return strtolower($str);
    }

    public function view(string $view): Source
    {
        return $this->twigLoader->getSourceContext($view);
    }

    public function yamlDecode(string $source): mixed
    {
        return match (true) {
            \function_exists('yaml_parse') => yaml_parse($source),
            default => Yaml::parse($source),
        };
    }

    public function yamlEncode(mixed $data): string
    {
        return match (true) {
            \function_exists('yaml_emit') => yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK),
            default => Yaml::dump($data),
        };
    }

    public function yamlValidate(string $source): bool|string
    {
        set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) {
            throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });
        try {
            return \is_array($this->yamlDecode($source));
        } catch (\Throwable $e) {
            return $e->getMessage();
        } finally {
            restore_error_handler();
        }
    }
}
