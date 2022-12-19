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

namespace Arnapou\SimpleSite\Build;

$config = require __DIR__ . '/config.php';
$builder = new PharBuilder($config);

foreach ($builder->allfiles() as $file) {
    echo $file->getPathname() . "\n";
}
