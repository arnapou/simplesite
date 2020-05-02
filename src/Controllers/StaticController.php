<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Controllers;

use Arnapou\SimpleSite\Core\Controller;
use Arnapou\SimpleSite\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class StaticController extends Controller
{
    protected $extensions = ['twig', 'htm', 'html', 'tpl', 'html.twig', 'php'];

    public function routePriority(): int
    {
        return 100;
    }

    public function configure(): void
    {
        $this->addRoute('{path}/', [$this, 'routeStaticDir'], 'static_dir')->setRequirement('path', '.*');
        $this->addRoute('{path}', [$this, 'routeStaticPage'], 'static_page')->setRequirement('path', '.+');
    }

    public function routeStaticDir(string $path = '')
    {
        $pathPublic = $this->container()->Config()->path_public();

        if (is_dir($realpath = "$pathPublic/$path")) {
            foreach ($this->extensions as $extension) {
                if (is_file("$realpath/index.$extension")) {
                    return $this->render("$path/index.$extension");
                }
            }
        }
        throw new ResourceNotFoundException();
    }

    protected function render($view, $context = []): Response
    {
        if (substr($view, -4) === '.php') {
            throw new ResourceNotFoundException();
        }
        return parent::render($view, $context);
    }

    public function routeStaticPage(string $path = '')
    {
        $pathPublic = $this->container()->Config()->path_public();
        $basePath   = $this->container()->Request()->getBasePath() . '/';

        $extension = Utils::extension($path);
        if (\in_array($extension, $this->extensions)) {
            return $this->redirect($basePath . substr($path, 0, -\strlen($extension) - 1));
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
        throw new ResourceNotFoundException();
    }
}
