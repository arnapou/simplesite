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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class Kernel
{
    public const onRun = 'onRun';
    public const onRequest = 'onRequest';
    public const onResponse = 'onResponse';
    public const onError404 = 'onError404';
    public const onError500 = 'onError500';

    private Config        $config;
    private EventListener $eventListener;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->eventListener = new EventListener();
    }

    public function handle(Request $request): Response
    {
        $this->eventListener->clear();
        $container = new ServiceContainer(__DIR__ . '/../Services', 'Arnapou\\SimpleSite\\Services');

        try {
            $container->add('Config', $this->config);
            $container->add('Kernel', $this);
            $container->add('Request', $request);
            $this->loadPhpFiles($container);

            $this->eventListener->dispatch(self::onRun, $event = new Event($container, null));

            $urlMatcher = new UrlMatcher($container->RouteCollections()->merge(), $container->RequestContext());
            $pathInfo = $container->Request()->getPathInfo();
            $parameters = $urlMatcher->match($pathInfo);
            $routeName = $parameters['_route'];
            $controller = $parameters['_controller'];
            unset($parameters['_controller'], $parameters['_route']);
            $container->Logger()->debug("Route $routeName", ['params' => $parameters]);

            $this->eventListener->dispatch(self::onRequest, $event = new Event($container, null));
            $response = $event->getResponse() ?: \call_user_func_array($controller, $parameters);

            $this->eventListener->dispatch(self::onResponse, $event = new Event($container, $response));
        } catch (ResourceNotFoundException $exception) {
            $container->Logger()->warning('404 Not Found');
            $response = $this->error($container, 404, $exception);
            $this->eventListener->dispatch(self::onError404, $event = new Event($container, $response));
        } catch (\Throwable $exception) {
            $container->Logger()->error('500 Internal Error', ['exception' => Utils::throwableToArray($exception)]);
            $response = $this->error($container, 500, $exception);
            $this->eventListener->dispatch(self::onError500, $event = new Event($container, $response));
        }

        return $event->getResponse()
            ?? $this->error($container, 500, new SimplesiteException('A theoretical impossible bug was thrown.'));
    }

    private function loadPhpFiles(ServiceContainer $container): void
    {
        if ($pathPhp = $this->config->path_php()) {
            if (!is_dir($pathPhp)) {
                throw new SimplesiteException("path $pathPhp not found");
            }
            foreach (Utils::findPhpFiles($pathPhp) as $file) {
                $this->loadPhpFile($container, $file);
            }
        }
        foreach (Utils::findPhpFiles(__DIR__ . '/../Controllers') as $file) {
            $this->loadPhpFile($container, $file, 'Arnapou\\SimpleSite\\Controllers');
        }
    }

    private function loadPhpFile(ServiceContainer $container, string $phpfile, string $namespace = ''): void
    {
        /** @psalm-suppress UnresolvableInclude */
        $obj = include_once $phpfile;

        if (!\is_object($obj) && $namespace) {
            $class = "$namespace\\" . basename($phpfile, '.php');
            $obj = new $class();
        }

        if ($obj instanceof PhpCode) {
            $obj->init($container);
        }
    }

    private function error(ServiceContainer $container, int $code, \Throwable $exception): Response
    {
        $context = [
            'exception' => $exception,
            'code' => $code,
        ];

        try {
            $html = $container->TwigEnvironment()->render("@templates/error.$code.twig", $context);
        } catch (\Throwable) {
            $html = $container->TwigEnvironment()->render("@internal/error.$code.twig", $context);
        }

        return new Response($html, $code);
    }

    public function eventListener(): EventListener
    {
        return $this->eventListener;
    }
}
