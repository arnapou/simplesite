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

namespace Arnapou\SimpleSite;

use Arnapou\Psr\Psr15HttpHandlers\Routing\Route;
use Arnapou\Psr\Psr3Logger\Decorator\ContextLogger;
use Arnapou\Psr\Psr7HttpMessage\HtmlResponse;
use Arnapou\Psr\Psr7HttpMessage\RedirectResponse;
use Arnapou\SimpleSite;
use Arnapou\SimpleSite\Core\Utils;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

abstract class Controller implements PhpCode
{
    public function init(): void
    {
        $this->configure();
    }

    abstract public function configure(): void;

    public function routePriority(): int
    {
        return 10;
    }

    protected function addRoute(string $path, callable $controller, string $name = null): Route
    {
        return SimpleSite::router()->addRoute($path, $controller, $name, $this->routePriority());
    }

    protected function render(string $view, array $context = []): ResponseInterface
    {
        $context = array_merge($context, $this->yamlContext($view));

        return new HtmlResponse(SimpleSite::twigEnvironment()->render($view, $context));
    }

    protected function yamlContext(string $view): array
    {
        $yamlFile = substr($view, 0, -\strlen(Utils::extension($view))) . 'yaml';
        try {
            if (SimpleSite::twigLoader()->exists($yamlFile)) {
                $yaml = SimpleSite::twigLoader()->getSourceContext($yamlFile)->getCode();
                $parsed = Yaml::parse($yaml);

                return \is_array($parsed) ? $parsed : [];
            }
        } catch (Throwable $exception) {
            $context = ['yaml' => $yamlFile, 'throwable' => $exception];
            SimpleSite::logger()->error('Yaml parsing failed', $context);
        }

        return [];
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        SimpleSite::logger()->notice("Redirect -> $url");

        return new RedirectResponse($url, $status);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse(SimpleSite::router()->generateUrl($route, $parameters), $status);
    }

    protected function asset(string $path): string
    {
        return SimpleSite::twigExtension()->asset($path);
    }

    protected function logger(): ContextLogger
    {
        return new ContextLogger(SimpleSite::logger());
    }
}
