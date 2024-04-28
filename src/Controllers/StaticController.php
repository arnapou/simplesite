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
use Arnapou\SimpleSite\Core\Utils;
use Arnapou\SimpleSite\SimpleSite;

final class StaticController extends Controller
{
    /** @var list<string> */
    protected array $extensions = ['twig', 'htm', 'html', 'tpl', 'html.twig', 'php'];

    public function configure(): void
    {
        $this->addRoute('/', $this->routeStaticDir(...), 'static_home');
        $this->addRoute('{path}/', $this->routeStaticDir(...), 'static_dir')->setRequirement('path', '.+');
        $this->addRoute('{path}', $this->routeStaticPage(...), 'static_page')->setRequirement('path', '.+');
    }

    public function routeStaticDir(string $path = ''): Response
    {
        $pathPublic = SimpleSite::config()->path_public;

        if (is_dir($realpath = "$pathPublic/$path")) {
            foreach ($this->extensions as $extension) {
                if (is_file("$realpath/index.$extension")) {
                    return $this->render("$path/index.$extension");
                }
            }
        }
        throw new NoResponseFound();
    }

    public function routeStaticPage(string $path = ''): Response
    {
        $pathPublic = SimpleSite::config()->path_public;
        $basePath = SimpleSite::config()->base_path_url;

        $pathExtension = Utils::extension($path);
        if (\in_array($pathExtension, $this->extensions, true)) {
            return $this->redirect($basePath . substr($path, 0, -\strlen($pathExtension) - 1));
        }

        $realpath = "$pathPublic/$path";
        foreach ($this->extensions as $extension) {
            if (is_file("$realpath.$extension")) {
                return $this->render("$path.$extension");
            }
        }

        if (is_dir($realpath)) {
            return $this->redirect($basePath . "$path/", 301);
        }

        throw new NoResponseFound();
    }

    protected function render(string $view, array $context = []): Response
    {
        if (str_ends_with($view, '.php')) {
            throw new NoResponseFound();
        }

        return parent::render($view, $context);
    }

    public function routePriority(): int
    {
        return self::PRIORITY_LOWEST;
    }
}
