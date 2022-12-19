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

require __DIR__ . '/BuildConfig.php';
require __DIR__ . '/BuildFilesIterator.php';
require __DIR__ . '/PharBuilder.php';

if ('cli' !== \PHP_SAPI) {
    bye('⛔ This script should be run only in CLI');
}

function bye(string $msg): never
{
    echo "$msg\n";
    exit(1);
}
