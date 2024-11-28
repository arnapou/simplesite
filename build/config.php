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

return new BuildConfig(
    pharOutputFile: 'bin/simplesite.phar',
    pharBootstrap: 'src/main.php',
    buildTempDir: __DIR__ . '/tmp',
    projectRootDir: \dirname(__DIR__),
    includedDirectories: [
        'src',
        'vendor',
    ],
    ignoredFilenames: [
        '.editorconfig',
        '.gitattributes',
        '.gitignore',
        '.gitlab-ci.yml',
        '.gitlab-ci.yaml',
        '.php-cs-fixer.php',
        '.php-cs-fixer.dist.php',
        '.scrutinizer.yml',
        '.travis.yml',
        'CHANGELOG',
        'CHANGELOG.md',
        'composer.json',
        'composer.lock',
        'LICENCE',
        'LICENSE',
        'LICENSE.md',
        'LICENSE.txt',
        'Makefile',
        'phpstan.neon',
        'phpunit.xml',
        'phpunit.xml.dist',
        'psalm.baseline.xml',
        'psalm.xml',
        'README.md',
        'Readme.md',
        'Readme.php',
        'README.rst',
        'UPGRADE.md',
    ],
    ignoredPathMatch: [
        '*/vendor/arnapou/*/icon.png',
        '*/vendor/arnapou/*/icon.svg',
        '*/vendor/arnapou/*/demo/*',
        '*/vendor/arnapou/*/example/*',
        '*/vendor/arnapou/*/tests/*',
        '*/vendor/composer/InstalledVersions.php',
        '*/vendor/composer/installed.php',
        '*/vendor/composer/installed.json',
        '*/vendor/psr/*/docs/*.md',
        '*/vendor/Expectation.php',
        '*/vendor/michelf/php-markdown/test/*',
        '*/vendor/symfony/http-client/Test/*',
        '*/vendor/symfony/http-client-contracts/Test/*',
        '*/vendor/symfony/service-contracts/Test/*',
        '*/vendor/symfony/translation-contracts/Test/*',
        '*/bin/yaml-lint',
        '*/.git/*',
        '*/.github/*',
    ]
);
