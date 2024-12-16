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

namespace Arnapou\SimpleSite\Admin;

use Arnapou\Encoder\Encoder;
use Arnapou\Ensure\Ensure;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr15HttpHandlers\Routing\Endpoint\EndpointInterface;
use Arnapou\Psr\Psr15HttpHandlers\Routing\PrefixDecorator\PrefixEndpoint;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusRedirect;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\Core;
use Psr\Http\Message\ServerRequestInterface;
use Twig\TwigFilter;

abstract class AdminController extends Controller
{
    protected readonly string $baseUrl;

    public function __construct(
        protected readonly Core\Config $config,
        protected readonly Core\TwigEnvironment $twig,
        protected readonly Core\Helper $helper,
        protected readonly Core\Container $container,
        protected readonly AdminConfig $admin,
        protected readonly AdminSession $session,
        protected readonly Encoder $encoder,
        protected readonly ThrowableLogger $logger,
        protected readonly HttpRouteHandler $router,
    ) {
        $this->baseUrl = rtrim(Ensure::string($this->config->base_path_admin), '/');
    }

    final public function configure(): void
    {
        $this->initOnce();

        foreach ($this->getEndpoints() as $endpoint) {
            $this->router->addEndpoint(new PrefixEndpoint("$this->baseUrl/", $endpoint));
        }
    }

    /**
     * @return array<EndpointInterface>
     */
    abstract protected function getEndpoints(): array;

    final protected function node(string $dir): AdminNode
    {
        return new AdminNode($this->encoder->decode($dir));
    }

    final protected function adminUrl(string|AdminNode $dir, string $name = AdminMainController::MAIN): string
    {
        return '' === ($str = (string) $dir)
            ? $this->helper->path(AdminMainController::HOME)
            : $this->helper->path($name, ['dir' => $this->encoder->encode($str)]);
    }

    final protected function firewall(\Closure $closure): mixed
    {
        try {
            return $this->session->isAuthenticated ? $closure() : $this->redirectToRoute(AdminLoginController::LOGIN);
        } catch (\Throwable $e) {
            $this->logger->throwable($e);
            $status = $e instanceof Core\Problem ? $e->getStatus()?->value : null;

            return $this->render('error.twig', ['error' => $e], $status ?? 200);
        }
    }

    /**
     * @return array<mixed>|null
     */
    final protected function requestParams(ServerRequestInterface $request): ?array
    {
        // Check the CSRF token which is mandatory here.
        $params = Ensure::array($request->getParsedBody() ?? []);
        if (!$this->session->isCsrfTokenOk($params['csrf_token'] ?? '')) {
            return null;
        }

        // The user made a change, we consider him active.
        // Thus, we refresh the time to postpone the token expiration.
        $this->session->csrfTime = time();

        return $params;
    }

    final protected function render(string $view, array $context = [], int $status = 200): Response
    {
        return parent::render("@internal/admin/$view", $this->session->context() + $context)->withStatus($status);
    }

    /**
     * @param array<mixed> $context
     */
    final protected function renderInvalidCsrf(string $view, array $context = []): Response
    {
        $this->session->flashMessage = 'Invalid CSRF token.';

        return $this->render($view, $context);
    }

    private function initOnce(): void
    {
        if (!$this->container->has(__METHOD__)) {
            $this->container->registerInstance(__METHOD__, new \stdClass());

            $this->session->start();

            $this->twig->addFilter(new TwigFilter('admin_url', $this->adminUrl(...)));
            $this->twig->addFilter(new TwigFilter('ini_get', ini_get(...)));
            $this->twig->addFilter(new TwigFilter('svg_symbol', $this->helper->svgSymbol(...), ['is_safe' => ['html']]));
            $this->twig->addFilter(new TwigFilter('svg_use', $this->helper->svgSymbolUse(...), ['is_safe' => ['html']]));

            $this->addRoute($this->baseUrl, fn () => $this->redirect("$this->baseUrl/", StatusRedirect::MovedPermanently->value));
        }
    }

    final public function routePriority(): int
    {
        return self::PRIORITY_LOW;
    }
}
