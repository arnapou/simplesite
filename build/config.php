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

require __DIR__ . '/src/common.php';

return new BuildConfig(
    'site/simplesite.phar',
    'src/main.php',
    dirname(__DIR__),
    __DIR__ . '/tmp',
    // included directories
    [
        'src',
        'vendor',
    ],
    // ignored filenames
    [
        '.editorconfig',
        '.gitattributes',
        '.gitignore',
        '.gitlab-ci.yaml',
        '.php-cs-fixer.php',
        '.php-cs-fixer.dist.php',
        '.travis.yml',
        'CHANGELOG.md',
        'composer.json',
        'composer.lock',
        'LICENCE',
        'LICENSE',
        'LICENSE.md',
        'Makefile',
        'phpstan.neon',
        'phpunit.xml',
        'psalm.xml',
        'README.md',
        'README.rst',
        'UPGRADE.md',
    ],
    // ignored pathmatch
    [
        '*/vendor/bin/*',
        '*/pfdb/demo/*',
        '*/pfdb/tests/*',
        '*/.git/*',
        '*/.github/*',
    ]
);