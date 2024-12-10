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
use Arnapou\Ensure\Enforce;
use Arnapou\Ensure\Ensure;
use Arnapou\PFDB\Database;
use Arnapou\Psr\Psr15HttpHandlers\HttpRouteHandler;
use Arnapou\Psr\Psr15HttpHandlers\Routing\Endpoint\EndpointInterface;
use Arnapou\Psr\Psr15HttpHandlers\Routing\PrefixDecorator\PrefixEndpoint;
use Arnapou\Psr\Psr3Logger\Decorator\ThrowableLogger;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusRedirect;
use Arnapou\SimpleSite\Controller;
use Arnapou\SimpleSite\Core;
use Arnapou\SimpleSite\SimpleSite;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Random\Randomizer;
use stdClass;
use Throwable;
use Twig\TwigFilter;

abstract class AdminController extends Controller
{
    private const int CSRF_TTL = 1800; // 30 minutes

    protected readonly string $baseUrl;
    protected bool $isAuthenticated = false {
        get => Enforce::bool($_SESSION['authenticated'] ?? false);
        set => $_SESSION['authenticated'] = $value;
    }
    protected string $passwordHash = '' {
        get => Enforce::string($this->getAdminConfig('password_hash') ?? '');
        set => $this->setAdminConfig('password_hash', $value);
    }
    protected string $flashMessage = '' {
        get {
            $value = Enforce::string($_SESSION['flash_message'] ?? '');
            $_SESSION['flash_message'] = '';

            return $value;
        }
        set => $_SESSION['flash_message'] = $value;
    }
    protected int $csrfTime = 0 {
        get => Enforce::int($_SESSION['csrf_time'] ?? 0);
        set => $_SESSION['csrf_time'] = $value;
    }

    public function __construct(
        protected readonly Core\Config $config,
        protected readonly Database $database,
        protected readonly Encoder $encoder,
        protected readonly Core\TwigEnvironment $twigEnvironment,
        protected readonly Core\TwigExtension $twigExtension,
        protected readonly ThrowableLogger $logger,
        protected readonly HttpRouteHandler $httpRouteHandler,
    ) {
        $this->baseUrl = rtrim(Ensure::string($this->config->base_path_admin), '/');
    }

    final public function configure(): void
    {
        $this->initOnce();

        foreach ($this->getEndpoints() as $endpoint) {
            $this->httpRouteHandler->addEndpoint(new PrefixEndpoint("$this->baseUrl/", $endpoint));
        }
    }

    /**
     * @return array<EndpointInterface>
     */
    abstract protected function getEndpoints(): array;

    final protected function getNode(string $dir): AdminNode
    {
        return AdminNode::from($this->config, $this->encoder->decode($dir));
    }

    /**
     * The token lives 1 hour before being refreshed.
     *
     * @return non-empty-string
     */
    final protected function getCsrfToken(): string
    {
        $token = Enforce::string($_SESSION['csrf_token'] ?? '');

        if ('' === $token || time() - $this->csrfTime > self::CSRF_TTL) {
            $token = $_SESSION['csrf_token'] = sha1(new Randomizer()->getBytes(5));
            $this->csrfTime = time();
        }

        return $token;
    }

    /**
     * @return non-empty-string
     */
    private function getAdminSecretKey(): string
    {
        $value = Enforce::string($this->getAdminConfig('secret_key') ?? '');

        if ('' === $value) {
            $value = sha1(new Randomizer()->getBytes(5));
            $this->setAdminConfig('secret_key', $value);
        }

        return $value;
    }

    final protected function getAdminUrl(string|AdminNode $dir, string $name = AdminMainController::MAIN): string
    {
        return '' === ($str = (string) $dir)
            ? $this->twigExtension->path(AdminMainController::HOME)
            : $this->twigExtension->path($name, ['dir' => $this->encoder->encode($str)]);
    }

    final protected function getAdminConfig(string $name): int|float|string|bool|null
    {
        $value = $this->database->getTable('admin.config')->get($name);

        return null === $value ? null : Ensure::nullableScalar($value['value'] ?? null, $name);
    }

    /**
     * @template T of int|float|string|bool|null
     *
     * @param T $value
     *
     * @return T
     */
    final protected function setAdminConfig(string $name, int|float|string|bool|null $value): int|float|string|bool|null
    {
        $table = $this->database->getTable('admin.config');
        $table->upsert(['id' => $name, 'value' => $value]);
        $table->flush();

        return $value;
    }

    final protected function firewall(Closure $closure): mixed
    {
        try {
            return $this->isAuthenticated ? $closure() : $this->redirectToRoute(AdminLoginController::LOGIN);
        } catch (Throwable $e) {
            $this->logger->throwable($e);
            $status = $e instanceof Core\Problem ? $e->getStatus()?->value : null;

            return $this->render('error.twig', ['error' => $e], $status ?? 200);
        }
    }

    /**
     * @return array<mixed>|null
     */
    final protected function csrfRequestParams(ServerRequestInterface $request): ?array
    {
        // This random wait avoids brute-force attacks by time-gating them.
        // It is not bothersome for humans.
        usleep(random_int(100_000, 200_000)); // 100 to 200 ms

        // Check the CSRF token which is mandatory here.
        $params = Ensure::array($request->getParsedBody() ?? []);
        if ($this->getCsrfToken() !== Enforce::string($params['csrf_token'] ?? '')) {
            return null;
        }

        // The user made a change, we consider him active.
        // Thus, we refresh the time to postpone the token expiration.
        $this->csrfTime = time();

        return $params;
    }

    final protected function hash(string $string): string
    {
        return hash_hmac('sha1', $string, $this->getAdminSecretKey());
    }

    final protected function hashVerify(string $string, string $expected): bool
    {
        return $expected === $this->hash($string);
    }

    final protected function render(string $view, array $context = [], int $status = 200): Response
    {
        return parent::render(
            "@internal/admin/$view",
            [
                'flash_message' => $this->flashMessage,
                'authenticated' => $this->isAuthenticated,
                'csrf_token' => $this->getCsrfToken(),
            ] + $context,
        )->withStatus($status);
    }

    /**
     * @param array<mixed> $context
     */
    final protected function renderInvalidCsrf(string $view, array $context = []): Response
    {
        $this->flashMessage = 'Invalid CSRF token.';

        return $this->render($view, $context);
    }

    private function initOnce(): void
    {
        if (!SimpleSite::container()->has($id = '.AdminControllerInitOnce')) {
            SimpleSite::container()->registerInstance($id, new stdClass());

            if ('' === (string) session_id()) {
                session_start();
            }

            $this->twigEnvironment->addFilter(new TwigFilter('adminUrl', $this->getAdminUrl(...)));
            $this->twigEnvironment->addFilter(new TwigFilter('ini_get', ini_get(...)));

            $this->addRoute($this->baseUrl, fn () => $this->redirect("$this->baseUrl/", StatusRedirect::MovedPermanently->value));
        }
    }

    final public function routePriority(): int
    {
        return self::PRIORITY_LOW;
    }
}
