<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Core;

use Psr\Container\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Helper $helper,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'app' => new ContainerPublic($this->container),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', $this->helper->asset(...)),
            new TwigFunction('data', $this->helper->data(...)),
            new TwigFunction('path', $this->helper->path(...)),
            new TwigFunction('path_dir', $this->helper->pathDir(...)),
            new TwigFunction('path_page', $this->helper->pathPage(...)),
            new TwigFunction('thumbnail', $this->helper->thumbnail(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', basename(...)),
            new TwigFilter('camel', $this->helper->toCamelCase(...)),
            new TwigFilter('debug_type', get_debug_type(...)),
            new TwigFilter('dirname', dirname(...)),
            new TwigFilter('getenv', static fn (string $name) => getenv($name)),
            new TwigFilter('minify_html', $this->helper->minifyHtml(...), ['is_safe' => ['html']]),
            new TwigFilter('path_dir', $this->helper->pathDir(...)),
            new TwigFilter('path_page', $this->helper->pathPage(...)),
            new TwigFilter('slug', $this->helper->slugify(...)),
            new TwigFilter('snake', $this->helper->toSnakeCase(...)),
            new TwigFilter('thumbnail', $this->helper->thumbnail(...)),
            new TwigFilter('view', static fn (string $view) => new View($view)),
            new TwigFilter('yaml', $this->helper->yamlEncode(...)),
        ];
    }
}
