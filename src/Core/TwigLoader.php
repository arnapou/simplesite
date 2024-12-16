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
    public function __construct(Config $config)
    {
        parent::__construct();

        $namespaces = [
            self::MAIN_NAMESPACE => Scope::default()->toPath($config, false),
            'pages' => Scope::pages->toPath($config, false),
            'public' => Scope::public->toPath($config, false),
            'templates' => Scope::templates->toPath($config, false),
            'data' => $config->path_data,
            'logs' => $config->log_path,
            'php' => $config->path_php,
            'internal' => __DIR__ . '/../Views',
        ];

        foreach ($namespaces as $namespace => $path) {
            if (\is_string($path)) {
                $this->addPath($path, $namespace);
            }
        }
    }
}
