<?php

include __DIR__ . '/../simplesite.phar';

\Arnapou\SimpleSite\run(
    [
        // mandatory
        'name'           => 'Arnapou simple site',
        'path_public'    => __DIR__,
        'path_cache'     => dirname(__DIR__) . '/cache',
        // optional: if you don't need them, you don't need to create these folders
        'path_templates' => dirname(__DIR__) . '/templates',
        'path_data'      => dirname(__DIR__) . '/data',
        'path_php'       => dirname(__DIR__) . '/php',
        // defaults: to change only if needed
        // 'log_max_files'  => 14,
        // 'log_level'      => Arnapou\SimpleSite\Core\Config::LOG_INFO,
    ]
);
