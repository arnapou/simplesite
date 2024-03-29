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
use Arnapou\SimpleSite\Core\ServiceFactory;
use Arnapou\SimpleSite\Core\Utils;

use function array_key_exists;
use function strlen;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension implements GlobalsInterface, ServiceFactory
{
    private function __construct(private ServiceContainer $container)
    {
    }

    public static function factory(ServiceContainer $container): self
    {
        return new self($container);
    }

    public static function aliases(): array
    {
        return [];
    }

    public function getGlobals(): array
    {
        return [
            'app' => $this->container,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('minifyHtml', Utils::minifyHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('thumbnail', $this->thumbnail(...)),
            new TwigFunction('asset', $this->asset(...)),
            new TwigFunction('path', $this->path(...)),
            new TwigFunction('path_dir', $this->path_dir(...)),
            new TwigFunction('path_page', $this->path_page(...)),
            new TwigFunction('emojis', Utils::emojis(...), ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('minifyHtml', Utils::minifyHtml(...), ['is_safe' => ['html']]),
            new TwigFilter('thumbnail', $this->thumbnail(...)),
            new TwigFilter('chunk', $this->chunk(...)),
            new TwigFilter('thumbnail', $this->thumbnail(...)),
            new TwigFilter('path_dir', $this->path_dir(...)),
            new TwigFilter('path_page', $this->path_page(...)),
            new TwigFilter('repeat', $this->repeat(...), ['is_safe' => ['html']]),
            new TwigFilter('getclass', $this->getclass(...)),
            new TwigFilter('emojis', Utils::emojis(...), ['is_safe' => ['html']]),
            new TwigFilter('slugify', Utils::slugify(...)),
            new TwigFilter('slug', Utils::slugify(...)),
        ];
    }

    public function chunk(array $array, int $size): array
    {
        return array_chunk($array, max($size, 1));
    }

    public function getclass(mixed $object): string
    {
        return get_debug_type($object);
    }

    public function repeat(string $string, int $n = 1): string
    {
        return str_repeat($string, $n);
    }

    public function thumbnail(string $path, int $size = 200): string
    {
        if (array_key_exists($ext = strtolower(Utils::extension($path)), Image::MIME_TYPES)) {
            $path = substr($path, 0, -strlen($ext)) . $size . '.' . substr($path, -strlen($ext));
        }

        return $this->asset($path);
    }

    public function asset(string $path): string
    {
        return $this->container->request()->getBasePath() . '/' . ltrim($path, '/');
    }

    public function path_dir(string $path): string
    {
        return $this->path('static_dir', ['path' => $path]);
    }

    public function path(string $name, array $parameters = []): string
    {
        $url = $this->container->urlGenerator()->generate($name, $parameters);

        return '//' === $url ? '/' : $url;
    }

    public function path_page(string $path): string
    {
        return $this->path('static_page', ['path' => $path]);
    }
}
