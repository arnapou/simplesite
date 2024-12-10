<?php

declare(strict_types=1);

include __DIR__ . '/../../bin/simplesite.phar';

SimpleSite::run(
    // mandatory
    name: 'Arnapou simple site',
    path_public: __DIR__,
    path_pages: \dirname(__DIR__) . '/pages',
    path_cache: '/tmp/simplesite',

    // optional folders: if you don't need them, you don't need to create them
    path_data: \dirname(__DIR__) . '/data',
    path_templates: \dirname(__DIR__) . '/templates',
    path_php: \dirname(__DIR__) . '/src',

    // logging: change only if needed
    log_path: \dirname(__DIR__) . '/log',
    // log_max_files: 7, // days
    // log_level: 'debug',
    // log_level: 'info',
    // log_level: 'notice', // default
    // log_level: 'warning',
    // log_level: 'error',
    // log_level: 'critical',
    // log_level: 'alert',
    // log_level: 'emergency',

    // optional parameters
    base_path_root: '',
    base_path_admin: 'admin',
);
