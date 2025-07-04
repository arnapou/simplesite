<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Build;

require_once __DIR__ . '/src/BuildConfig.php';
require_once __DIR__ . '/src/BuildFilesIterator.php';
require_once __DIR__ . '/src/PharBuilder.php';
require_once __DIR__ . '/../vendor/autoload.php';

/** @phpstan-ignore argument.type */
$builder = new PharBuilder(require __DIR__ . '/config.php');
$builder->build();
