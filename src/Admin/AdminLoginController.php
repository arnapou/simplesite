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

use Arnapou\Ensure\Enforce;
use Arnapou\Psr\Psr15HttpHandlers\Routing\Endpoint\Endpoint;
use Arnapou\Psr\Psr15HttpHandlers\Routing\Route;
use Arnapou\Psr\Psr7HttpMessage\FileResponse;
use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Core\Problem;
use Psr\Http\Message\ServerRequestInterface;

final class AdminLoginController extends AdminController
{
    public const string LOGIN = 'admin_login';

    protected function getEndpoints(): array
    {
        return [
            new Endpoint(new Route('login', self::LOGIN)->setMethods('GET', 'POST'), $this->routeLogin(...)),
            new Endpoint('logout', $this->routeLogout(...), 'admin_logout'),
            new Endpoint('lock.svg', fn () => FileResponse::fromFilename(__DIR__ . '/../Views/svg/icon-lock.svg'), 'admin_favicon_unauthenticated'),
            new Endpoint('user.svg', fn () => FileResponse::fromFilename(__DIR__ . '/../Views/svg/icon-user.svg'), 'admin_favicon_authenticated'),
        ];
    }

    private function routeLogin(ServerRequestInterface $request): Response
    {
        return match ($request->getMethod()) {
            'GET' => match (true) {
                $this->isAuthenticated => $this->redirectToRoute(AdminMainController::HOME),
                '' === $this->passwordHash => $this->render('form-login.twig', ['init' => true]),
                default => $this->render('form-login.twig'),
            },
            'POST' => match ($params = $this->csrfRequestParams($request)) {
                null => $this->renderInvalidCsrf('form-login.twig'),
                default => match (true) {
                    '' === $this->passwordHash => $this->doInit($params),
                    default => $this->doLogin($params),
                },
            },
            default => throw Problem::fromStatus(StatusClientError::MethodNotAllowed),
        };
    }

    private function routeLogout(): Response
    {
        $_SESSION = [];
        session_write_close();

        return $this->redirectToRoute(AdminMainController::HOME);
    }

    /**
     * @param array<mixed> $params
     */
    private function doInit(array $params): Response
    {
        $password = Enforce::string($params['password'] ?? '');
        if (\strlen($password) < 8) {
            $this->flashMessage = 'The minimum length of the password is 8.';

            return $this->render('form-login.twig', ['init' => true]);
        }

        $this->passwordHash = $this->hash($password);

        return $this->redirectToRoute(self::LOGIN);
    }

    /**
     * @param array<mixed> $params
     */
    private function doLogin(array $params): Response
    {
        $password = Enforce::string($params['password'] ?? '');
        if (!$this->hashVerify($password, $this->passwordHash)) {
            $this->flashMessage = 'Wrong password provided.';

            return $this->render('form-login.twig');
        }

        $this->isAuthenticated = true;

        return $this->redirectToRoute(AdminMainController::HOME);
    }
}
