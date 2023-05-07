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

include __DIR__ . '/../../bin/simplesite.phar';

\Arnapou\SimpleSite\run(
    // mandatory
    name: 'Arnapou simple site',
    path_public: __DIR__,
    path_cache: \dirname(__DIR__) . '/cache',

    // optional folders: if you don't need them, you don't need to create them
    path_data: \dirname(__DIR__) . '/data',
    path_templates: \dirname(__DIR__) . '/templates',
    path_php: \dirname(__DIR__) . '/php',

    // logging: change only if needed
    // log_path: \dirname(__DIR__) . '/cache/logs',
    // log_max_files: 7, // days
    // log_level: 'debug',
    // log_level: 'info',
    // log_level: 'notice', // default
    // log_level: 'warning',
    // log_level: 'error',
    // log_level: 'critical',
    // log_level: 'alert',
    // log_level: 'emergency',
);
