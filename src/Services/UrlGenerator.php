<?php

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

class UrlGenerator implements ServiceFactory
{
    public static function factory(ServiceContainer $container)
    {
        return new \Symfony\Component\Routing\Generator\UrlGenerator(
            $container->RouteCollections()->get(),
            $container->RequestContext(),
            $container->Logger()
        );
    }

    public static function aliases(): array
    {
        return [];
    }
}
