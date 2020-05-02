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
use Arnapou\SimpleSite\Utils;
use Twig\Environment;
use Twig\Extension\DebugExtension;

class TwigEnvironment implements ServiceFactory
{
    public static function factory(ServiceContainer $container)
    {
        Utils::mkdir($cache = $container->Config()->path_cache() . '/twig');

        $environment = new Environment(
            $container->TwigLoader(),
            [
                'debug'            => true,
                'charset'          => 'UTF-8',
                'strict_variables' => false,
                'autoescape'       => 'html',
                'cache'            => $cache,
                'auto_reload'      => true,
                'optimizations'    => -1,
            ]
        );
        $environment->addExtension(new DebugExtension());
        $environment->addExtension($container->TwigExtension());

        return $environment;
    }

    public static function aliases(): array
    {
        return ['twig'];
    }
}
