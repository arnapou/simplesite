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

namespace Arnapou\SimpleSite\Services;

use Arnapou\SimpleSite\Core\ServiceContainer;
use Arnapou\SimpleSite\Core\ServiceFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class Session implements ServiceFactory
{
    public static function factory(ServiceContainer $container): SessionInterface
    {
        $request = $container->request();
        if (!$request->hasSession()) {
            $request->setSession(new \Symfony\Component\HttpFoundation\Session\Session());
            $request->getSession()->start();
        }

        return $request->getSession();
    }

    public static function aliases(): array
    {
        return [];
    }
}
