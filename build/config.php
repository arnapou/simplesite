<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Build;

\define('PROJECT_DIR', \dirname(__DIR__));

const BUILD_BOOTSTRAP_FILE = 'src/main.php';

const BUILD_INCLUDED_DIRS = [
    'src',
    'vendor',
];

const BUILD_IGNORE_FILENAMES = [
    '.editorconfig',
    '.gitattributes',
    '.gitignore',
    '.travis.yml',
    'composer.json',
    'composer.lock',
    'CONTRIBUTING.md',
    'drupal_test.sh',
    'Makefile',
    'psalm.xml',
    'UPGRADE.md',
];

const BUILD_IGNORE_PATHMATCH = [
    '*/*TestCase.php',
    '*/.git/*',
    '*/.github/*',
    '*/.gitlab-ci.*',
    '*/.php_cs*',
    '*/.psalm/*',
    '*/CHANGELOG*',
    '*/ChangeLog*',
    '*/composer/tmp-*',
    '*/LICENCE*',
    '*/LICENSE*',
    '*/monolog/monolog/src/Monolog/Handler/TestHandler.php',
    '*/monolog/monolog/src/Monolog/Test/*',
    '*/pfdb/demo/*',
    '*/pfdb/tests/*',
    '*/phpstan.neon*',
    '*/phpunit.xml*',
    '*/psr/log/Psr/Log/Test/*',
    '*/README*',
    '*/tests/*',
    '*/Tests/*',
    '*/twig/doc/*',
];

const BUILD_TMPDIR = PROJECT_DIR . '/build/tmp';

/**
 * Include automatically all build/src php files.
 */
foreach (glob(__DIR__ . '/src/*.php') ?: [] as $srcPhpFile) {
    /** @psalm-suppress UnresolvableInclude */
    require $srcPhpFile;
}

/**
 * Forbid not CLI execution.
 */
if ('cli' !== PHP_SAPI) {
    PharBuilder::bye('⛔ This script should be run only in CLI');
}
