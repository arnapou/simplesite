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

use Arnapou\SimpleSite\Exception\SimplesiteProblem;

use function is_object;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Throwable;

final class Kernel
{
    public const onRun = 'onRun';
    public const onRequest = 'onRequest';
    public const onResponse = 'onResponse';
    public const onError404 = 'onError404';
    public const onError500 = 'onError500';

    private readonly ServiceContainer $container;
    private bool $handled = false;

    public function __construct(
        private readonly Config $config,
        private readonly EventListener $eventListener = new EventListener()
    ) {
        $this->container = new ServiceContainer();
    }

    public function handle(Request $request): Response
    {
        if ($this->handled) {
            throw new SimplesiteProblem('The kernel cannot be handled more than once.');
        }

        $this->handled = true;
        $this->eventListener->clear();
        $this->container
            ->add('config', $this->config)
            ->add('kernel', $this)
            ->add('request', $request)
            ->loadPsr4(
                'Arnapou\\SimpleSite\\Services',
                __DIR__ . '/../Services'
            );

        $logger = $this->container->logger();

        try {
            $this->loadPhpFiles();

            $this->eventListener->dispatch(self::onRun, $this->createEvent(null));

            $urlMatcher = new UrlMatcher(
                $this->container->routeCollections()->merge(),
                $this->container->requestContext()
            );
            $pathInfo = $this->container->request()->getPathInfo();
            $parameters = $urlMatcher->match($pathInfo);
            $routeName = $parameters['_route'];
            $controller = $parameters['_controller'];
            unset($parameters['_controller'], $parameters['_route']);
            $logger->debug("Route $routeName", ['params' => $parameters]);

            $this->eventListener->dispatch(self::onRequest, $event = $this->createEvent(null));
            $response = $event->getResponse() ?? $controller(...$parameters);

            $this->eventListener->dispatch(self::onResponse, $event = $this->createEvent($response));
        } catch (ResourceNotFoundException $e) {
            $logger->warning('404 Not Found');
            $response = $this->error(404, $e);
            $this->eventListener->dispatch(self::onError404, $event = $this->createEvent($response));
        } catch (Throwable $e) {
            $logger->error('500 Internal Error', ['exception' => Php::throwableToArray($e)]);
            $response = $this->error(500, $e);
            $this->eventListener->dispatch(self::onError500, $event = $this->createEvent($response));
        }

        return $event->getResponse()
            ?? $this->error(500, new SimplesiteProblem('A theoretical impossible bug was thrown.'));
    }

    private function loadPhpFiles(): void
    {
        if ($pathPhp = $this->config->path_php) {
            foreach (Utils::findPhpFiles($pathPhp) as $file) {
                $this->loadPhpFile($file);
            }
        }

        foreach (Utils::findPhpFiles(__DIR__ . '/../Controllers') as $file) {
            $this->loadPhpFile2($file, 'Arnapou\\SimpleSite\\Controllers');
        }
    }

    private function loadPhpFile(string $phpfile): void
    {
        /** @psalm-suppress UnresolvableInclude */
        $obj = include_once $phpfile;

        if ($obj instanceof PhpCode) {
            $obj->init($this->container);
        }
    }

    private function loadPhpFile2(string $phpfile, string $namespace = ''): void
    {
        /** @psalm-suppress UnresolvableInclude */
        $obj = include_once $phpfile;

        if (!is_object($obj) && $namespace) {
            $class = "$namespace\\" . basename($phpfile, '.php');
            $obj = new $class();
        }

        if ($obj instanceof PhpCode) {
            $obj->init($this->container);
        }
    }

    private function error(int $code, Throwable $exception): Response
    {
        $context = [
            'exception' => $exception,
            'code' => $code,
        ];

        if (
            $exception instanceof SimplesiteProblem ||
            $exception instanceof \Twig\Error\LoaderError
        ) {
            $context['content'] = $exception->getMessage();
        }

        try {
            $html = $this->container->twigEnvironment()->render("@templates/error.$code.twig", $context);
        } catch (\Throwable) {
            $html = $this->container->twigEnvironment()->render("@internal/error.$code.twig", $context);
        }

        return new Response($html, $code);
    }

    public function createEvent(?Response $response): Event
    {
        return new Event($this->container, $response);
    }

    public function eventListener(): EventListener
    {
        return $this->eventListener;
    }
}
