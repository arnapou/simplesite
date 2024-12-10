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

use Twig\Loader\FilesystemLoader;

final class TwigLoader extends FilesystemLoader
{
    public function __construct(
        Config $config,
    ) {
        parent::__construct();

        /** @var array<string, string> $namespaces */
        $namespaces = [
            self::MAIN_NAMESPACE => $config->path_pages,
            'internal' => __DIR__ . '/../Views',
            'data' => $config->path_data,
            'logs' => $config->log_path,
            'pages' => $config->path_pages,
            'public' => $config->path_public,
            'php' => $config->path_php,
            'templates' => $config->path_templates,
        ];

        foreach ($namespaces as $namespace => $path) {
            if ('' !== $path) {
                $this->addPath($path, $namespace);
            }
        }
    }
}
