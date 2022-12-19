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

namespace Arnapou\SimpleSite;

require __DIR__ . '/../vendor/autoload.php';

Core\Php::setErrorReporting();
set_error_handler(Core\Php::getErrorHandler());
set_exception_handler(Core\Php::getExceptionHandler());
register_shutdown_function(Core\Php::getShutdownHandler());

function run(
    string $name,
    string $path_public,
    string $path_cache,
    string $path_data = '',
    string $path_templates = '',
    string $path_php = '',
    string $log_path = '',
    int $log_max_files = 7,
    string $log_level = 'notice'
): void {
    (new Core\Kernel(
        new Core\Config(
            $name,
            $path_public,
            $path_cache,
            $path_data,
            $path_templates,
            $path_php,
            $log_path,
            $log_max_files,
            $log_level
        )
    ))
        ->handle(\Symfony\Component\HttpFoundation\Request::createFromGlobals())
        ->send();
}
