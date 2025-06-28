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

namespace Arnapou\SimpleSite\Controllers;

use Arnapou\Psr\Psr15HttpHandlers\Exception\NoResponseFound;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Admin;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\Core;

final class StaticController extends Controller
{
    public function __construct(
        private readonly Core\Container $container,
        private readonly Core\Config $config,
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

        return $this->hardRedirect("$path/") ?? throw new NoResponseFound();
    }

    public function routeStaticPage(string $path = ''): Response
    {
        /** @var Core\Helper $helper */
        $helper = $this->container->get(Core\Helper::class);

        if (null !== ($ext = $helper->pageExtension($path))) {
            $relative = str_ends_with("/$path", "/index.$ext")
                ? substr($path, 0, -\strlen("index.$ext"))
                : substr($path, 0, -\strlen(".$ext"));

            return $this->redirect($this->config->base_path_root . $relative);
        }

        if (null !== ($ext = $this->findExtension($realpath = $this->config->path_pages . "/$path"))) {
            return $this->render("$path.$ext");
        }

        if (is_dir($realpath)) {
            return $this->redirect($this->config->base_path_root . "$path/", 301);
        }

        return $this->hardRedirect($path) ?? throw new NoResponseFound();
    }

    public function hardRedirect(string $path): ?Response
    {
        /** @var Admin\AdminConfig $adminConfig */
        $adminConfig = $this->container->get(Admin\AdminConfig::class);

        foreach ($adminConfig->getRedirects() as $data) {
            $strict = str_ends_with($data['from'], '/');

            if ($strict && $data['from'] === $path) {
                return $this->redirect($data['link'], 301);
            }

            if (!$strict && rtrim($data['from'], '/') === rtrim($path, '/')) {
                return $this->redirect($data['link'], 301);
            }
        }

        return null;
    }

    private function findExtension(string $basePath): ?string
    {
        return array_find(
            Core\Config::PAGE_EXTENSIONS,
            static fn ($extension) => is_file("$basePath.$extension"),
        );
    }

    public function routePriority(): int
    {
        return self::PRIORITY_LOWEST - 2;
    }
}
