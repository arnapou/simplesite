<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Services;

use Arnapou\SimpleSite\Core\Controller;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollections implements ServiceFactory
{
    /**
     * @var RouteCollection[]
     */
    private $collections = [];
    /**
     * @var RouteCollection
     */
    private $main;

    public function __construct()
    {
        $this->main = new RouteCollection();
    }

    public static function factory(ServiceContainer $container)
    {
        return new self();
    }

    public static function aliases(): array
    {
        return [];
    }

    public function addRoute(Controller $controller, string $path, callable $callable, string $name): Route
    {
        $path   = '/' . ltrim($path, '/');
        $route  = new class($path, ['_controller' => $callable]) extends Route {
            public function setDefaults(array $defaults)
            {
                $defaults = array_merge($this->getDefaults(), $defaults);
                return parent::setDefaults($defaults);
            }
        };
        $prefix = substr(\get_class($controller), 0, 4) === 'Cms\\' ? 'cms_' : '';
        return $this->add($prefix . $name, $route, $controller->routePriority());
    }

    public function add($name, Route $route, int $priority): Route
    {
        if (!\array_key_exists($priority, $this->collections)) {
            $this->collections[$priority] = new RouteCollection();
        }
        $this->collections[$priority]->add($name, $route);
        return $route;
    }

    public function merge(): RouteCollection
    {
        ksort($this->collections);
        foreach ($this->collections as $collection) {
            $this->main->addCollection($collection);
        }
        return $this->main;
    }

    public function get(): RouteCollection
    {
        return $this->main;
    }
}
