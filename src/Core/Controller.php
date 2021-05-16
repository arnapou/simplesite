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

use Arnapou\SimpleSite\Exception\SimplesiteException;
use Arnapou\SimpleSite\Utils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Yaml\Yaml;

abstract class Controller implements PhpCode
{
    private ?ServiceContainer $container = null;

    public function init(ServiceContainer $container): void
    {
        $this->container = $container;
        $this->configure();
    }

    abstract public function configure(): void;

    public function routePriority(): int
    {
        return 10;
    }

    protected function addRoute(string $path, callable $controller, ?string $name = null): Route
    {
        return $this->container()->RouteCollections()->addRoute(
            $this,
            $path,
            $controller,
            $name ?: uniqid('_route', false)
        );
    }

    protected function render(string $view, array $context = []): Response
    {
        $context = array_merge($context, $this->yamlContext($view));

        return new Response($this->container()->TwigEnvironment()->render($view, $context));
    }

    protected function yamlContext(string $view): array
    {
        $yamlFile = substr($view, 0, -\strlen(Utils::extension($view))) . 'yaml';
        try {
            if ($this->container()->TwigLoader()->exists($yamlFile)) {
                $yaml = $this->container()->TwigLoader()->getSourceContext($yamlFile)->getCode();

                return Yaml::parse($yaml);
            }
        } catch (\Throwable $exception) {
            $context = ['yaml' => $yamlFile] + Utils::throwableToArray($exception);
            $this->container()->Logger()->error('Yaml parsing failed', $context);
        }

        return [];
    }

    public function container(): ServiceContainer
    {
        if (!$this->container) {
            throw new SimplesiteException('The container should be used out of constructor');
        }

        return $this->container;
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        $this->container()->Logger()->notice("Redirect -> $url");

        return new RedirectResponse($url, $status);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): Response
    {
        return new RedirectResponse($this->container()->UrlGenerator()->generate($route, $parameters), $status);
    }

    protected function asset(string $path): string
    {
        return $this->container()->TwigExtension()->asset($path);
    }
}
