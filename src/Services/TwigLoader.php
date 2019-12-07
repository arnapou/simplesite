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
use Arnapou\SimpleSite\Exception\ConfigException;
use Twig\Loader\FilesystemLoader;

class TwigLoader implements ServiceFactory
{
    public static function factory(ServiceContainer $container)
    {
        $loader = new FilesystemLoader();
        $loader->addPath($container->Config()->path_public());
        $loader->addPath(__DIR__ . '/../Views', 'internal');

        $registerNamespace = function (string $namespace, string $configName) use ($container, $loader) {
            try {
                if ($path = $container->Config()->$configName()) {
                    $loader->addPath($path, $namespace);
                }
            } catch (ConfigException $exception) {
            }
        };

        $registerNamespace('templates', 'path_templates');
        $registerNamespace('data', 'path_data');
        $registerNamespace('logs', 'path_logs');
        $registerNamespace('php', 'path_php');
        $registerNamespace('public', 'path_public');

        return $loader;
    }

    public static function aliases(): array
    {
        return [];
    }
}
