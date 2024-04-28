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
use Arnapou\Psr\Psr7HttpMessage\Response;

abstract class Controller implements PhpCode
{
    final public const int PRIORITY_LOWEST = -10;
    final public const int PRIORITY_LOW = 0;
    final public const int PRIORITY_DEFAULT = 10;
    final public const int PRIORITY_HIGH = 20;
    final public const int PRIORITY_HIGHEST = 30;

    public function init(): void
    {
        $this->configure();
    }

    abstract public function configure(): void;

    public function routePriority(): int
    {
        return self::PRIORITY_DEFAULT;
    }

    protected function addRoute(string $path, callable $controller, ?string $name = null): Route
    {
        return SimpleSite::router()->addRoute($path, $controller, $name, $this->routePriority());
    }

    /**
     * @param array<mixed> $context
     */
    protected function render(string $view, array $context = []): Response
    {
        $context = SimpleSite::yamlContext()->getContext($view, $context);

        return new HtmlResponse(SimpleSite::twigEnvironment()->render($view, $context));
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        SimpleSite::logger()->notice("Redirect -> $url");

        return new RedirectResponse($url, $status);
    }

    /**
     * @param array<string, string|int|float> $parameters
     */
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
