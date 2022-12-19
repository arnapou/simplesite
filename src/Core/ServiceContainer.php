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

use Arnapou\PFDB\Database;
use Arnapou\SimpleSite\Exception\ServiceCouldNotBeLoaded;
use Arnapou\SimpleSite\Exception\ServiceHasNoFactory;
use Arnapou\SimpleSite\Exception\ServiceUnknown;
use Arnapou\SimpleSite\Services\Counter;
use Arnapou\SimpleSite\Services\Image;
use Arnapou\SimpleSite\Services\RouteCollections;
use Arnapou\SimpleSite\Services\TwigExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

/**
 * @method Counter          counter()
 * @method Database         database()
 * @method Image            image()
 * @method LoggerInterface  logger()
 * @method Request          request()
 * @method RequestContext   requestContext()
 * @method RouteCollections routeCollections()
 * @method SessionInterface session()
 * @method Environment      twigEnvironment()
 * @method TwigExtension    twigExtension()
 * @method LoaderInterface  twigLoader()
 * @method UrlGenerator     urlGenerator()
 * @method Kernel           kernel()
 * @method Config           config()
 */
final class ServiceContainer
{
    /** @var array<string, object> */
    private array $instances = [];
    /** @var array<string, array{class-string<ServiceFactory>, array<string>}> */
    private array $classes = [];

    /**
     * This Magic method can retrieve a service by its name or alias.
     *
     * @throws ServiceUnknown
     */
    public function __call(string $name, array $arguments = []): object
    {
        return $this->get($name);
    }

    /**
     * Like a PSR-4 loading mapping : namespace => path.
     *
     * @throws ServiceHasNoFactory
     * @throws ServiceCouldNotBeLoaded
     *
     * @return $this
     */
    public function loadPsr4(string $namespace, string $path): self
    {
        foreach (Utils::findPhpFiles($path) as $file) {
            $baseClass = basename($file, '.php');

            if (!class_exists($fqcnClass = "$namespace\\$baseClass")) {
                throw new ServiceCouldNotBeLoaded($baseClass);
            }

            if (!is_subclass_of($fqcnClass, ServiceFactory::class)) {
                throw new ServiceHasNoFactory($baseClass);
            }

            $aliases = [
                strtolower($baseClass),
                ...array_map('strtolower', ([$fqcnClass, 'aliases'])()),
            ];
            foreach ($aliases as $alias) {
                $this->classes[$alias] = [$fqcnClass, $aliases];
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function add(string $name, object $instance): self
    {
        $this->instances[strtolower($name)] = $instance;

        return $this;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id = strtolower($id)])) {
            return $this->instances[$id];
        }

        [$class, $aliases] = $this->classes[$id] ?? throw new ServiceUnknown($id);

        $object = ([$class, 'factory'])($this);
        foreach ($aliases as $alias) {
            $this->instances[$alias] = $object;
        }

        return $object;
    }
}
