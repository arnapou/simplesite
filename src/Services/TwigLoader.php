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
use Twig\Loader\FilesystemLoader;

final class TwigLoader implements ServiceFactory
{
    public static function factory(ServiceContainer $container): FilesystemLoader
    {
        $loader = new FilesystemLoader();
        $config = $container->config();

        /** @var array<string, string> $namespaces */
        $namespaces = [
            $loader::MAIN_NAMESPACE => $config->path_public,
            'internal' => __DIR__ . '/../Views',
            'templates' => $config->path_templates,
            'data' => $config->path_data,
            'php' => $config->path_php,
            'public' => $config->path_public,
            'logs' => $config->log_path,
        ];

        foreach ($namespaces as $namespace => $path) {
            if ('' !== $path) {
                $loader->addPath($path, $namespace);
            }
        }

        return $loader;
    }

    public static function aliases(): array
    {
        return [];
    }
}
