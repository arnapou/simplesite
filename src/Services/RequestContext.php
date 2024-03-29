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

final class RequestContext implements ServiceFactory
{
    public static function factory(ServiceContainer $container): \Symfony\Component\Routing\RequestContext
    {
        $context = new \Symfony\Component\Routing\RequestContext();
        $context->fromRequest($container->request());

        return $context;
    }

    public static function aliases(): array
    {
        return [];
    }
}
