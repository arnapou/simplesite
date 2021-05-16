<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Core;

use Arnapou\PFDB\Database;
use Arnapou\SimpleSite\Exception\UnkownServiceException;
use Arnapou\SimpleSite\Services\Compteur;
use Arnapou\SimpleSite\Services\Image;
use Arnapou\SimpleSite\Services\RouteCollections;
use Arnapou\SimpleSite\Services\TwigExtension;
use Arnapou\SimpleSite\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

/**
 * @method Compteur         Compteur()
 * @method Database         Database()
 * @method Image            Image()
 * @method LoggerInterface  Logger()
 * @method Request          Request()
 * @method RequestContext   RequestContext()
 * @method RouteCollections RouteCollections()
 * @method SessionInterface Session()
 * @method Environment      TwigEnvironment()
 * @method TwigExtension    TwigExtension()
 * @method LoaderInterface  TwigLoader()
 * @method UrlGenerator     UrlGenerator()
 * @method Kernel           Kernel()
 * @method Config           Config()
 */
class ServiceContainer
{
    private array $services = [];
    private array $classes = [];

    public function __construct(string $path, string $namespace)
    {
        foreach (Utils::findPhpFiles($path) as $file) {
            $baseclass = basename($file, '.php');
            $classname = "$namespace\\$baseclass";
            $aliases = array_map('strtolower', \call_user_func([$classname, 'aliases']));
            $aliases[] = strtolower($baseclass);
            foreach ($aliases as $alias) {
                $this->classes[$alias] = [$classname, $aliases];
            }
        }
    }

    public function add(string $name, object $instance): void
    {
        $this->services[strtolower($name)] = $instance;
    }

    public function __call(string $name, array $arguments = []): object
    {
        return $this->get($name);
    }

    public function get(string $id): object
    {
        $id = strtolower($id);
        if (!isset($this->services[$id])) {
            if (!isset($this->classes[$id])) {
                throw new UnkownServiceException($id);
            }
            $this->services[$id] = \call_user_func([$this->classes[$id][0], 'factory'], $this);
            foreach ($this->classes[$id][1] as $alias) {
                $this->services[strtolower($alias)] = $this->services[$id];
            }
        }

        return $this->services[$id];
    }
}
