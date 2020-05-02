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

function run(array $config)
{
    error_reporting(E_ALL & ~E_USER_DEPRECATED);

    set_exception_handler(
        function (\Throwable $throwable) {
            header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 500 Internal Server Error');
            echo '<h1>500 Internal Server Error</h1>';
            echo '<pre>';
            $dump = Utils::dump_throwable($throwable);
            print_r($dump);
            echo '</pre>';
        }
    );

    $kernel = new Kernel(new Config($config));
    $kernel->handle(Request::createFromGlobals())->send();
}
