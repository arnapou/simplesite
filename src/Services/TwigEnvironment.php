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
use Arnapou\SimpleSite\Core\Utils;
use Twig\Environment;
use Twig\Extension\DebugExtension;

final class TwigEnvironment implements ServiceFactory
{
    public static function factory(ServiceContainer $container): Environment
    {
        $environment = new Environment(
            $container->twigLoader(),
            [
                'debug' => true,
                'charset' => 'UTF-8',
                'strict_variables' => false,
                'autoescape' => 'html',
                'cache' => Utils::mkdir($container->config()->path_cache . '/twig'),
                'auto_reload' => true,
                'optimizations' => -1,
            ]
        );
        $environment->addExtension(new DebugExtension());
        $environment->addExtension($container->twigExtension());

        return $environment;
    }

    public static function aliases(): array
    {
        return ['twig'];
    }
}
