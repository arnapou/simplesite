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

namespace Arnapou\SimpleSite\Controllers;

use Arnapou\Psr\Psr15HttpHandlers\Exception\NoResponseFound;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Helper;

final class StaticController extends Controller
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function configure(): void
    {
        $this->addRoute('/', $this->routeStaticDir(...), 'static_home');
        $this->addRoute('{path}/', $this->routeStaticDir(...), 'static_dir')->setRequirement('path', '.+');
        $this->addRoute('{path}', $this->routeStaticPage(...), 'static_page')->setRequirement('path', '.+');
    }

    public function routeStaticDir(string $path = ''): Response
    {
        if (is_dir($realpath = $this->config->path_pages . "/$path")) {
            $extension = $this->findExtension("$realpath/index");
            if (null !== $extension) {
                return $this->render("$path/index.$extension");
            }
        }
        throw new NoResponseFound();
    }

    public function routeStaticPage(string $path = ''): Response
    {
        if (null !== ($extension = new Helper()->pageExtension($path))) {
            return $this->redirect($this->config->base_path_root . substr($path, 0, -\strlen($extension) - 1));
        }

        $extension = $this->findExtension($realpath = $this->config->path_pages . "/$path");
        if (null !== $extension) {
            return $this->render("$path.$extension");
        }

        if (is_dir($realpath)) {
            return $this->redirect($this->config->base_path_root . "$path/", 301);
        }

        throw new NoResponseFound();
    }

    private function findExtension(string $basePath): ?string
    {
        return array_find(
            Config::PAGE_EXTENSIONS,
            static fn ($extension) => is_file("$basePath.$extension"),
        );
    }

    public function routePriority(): int
    {
        return self::PRIORITY_LOWEST;
    }
}
