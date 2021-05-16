<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite;

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

Utils::defaultErrorReporting();
set_error_handler(Utils::defaultErrorHandler());
set_exception_handler(Utils::defaultExceptionHandler());
register_shutdown_function(Utils::defaultShutdownHandler());

function run(array $config): void
{
    $kernel = new Kernel(new Config($config));
    $kernel->handle(Request::createFromGlobals())->send();
}
