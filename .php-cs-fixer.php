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

$header = <<<HEADER
    This file is part of the Arnapou Simple Site package.

    (c) Arnaud Buathier <arnaud@arnapou.net>

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    HEADER;

$finder = (new PhpCsFixer\Finder())
    ->notPath('tmp')
    ->in(
        [
            __DIR__ . '/build/src',
            __DIR__ . '/src',
        ]
    );

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PSR2' => true,
            '@PSR12' => true,
            '@Symfony' => true,
            '@Symfony:risky' => true,
            '@DoctrineAnnotation' => true,
            '@PHP80Migration' => true,
            '@PHP81Migration' => true,
            '@PHP82Migration' => true,
            'declare_strict_types' => true,
            'concat_space' => ['spacing' => 'one'],
            'ordered_imports' => ['sort_algorithm' => 'alpha'],
            'native_function_invocation' => ['include' => ['@compiler_optimized']],
            'combine_consecutive_issets' => true,
            'combine_consecutive_unsets' => true,
            'phpdoc_order' => true,
            'phpdoc_var_annotation_correct_order' => true,
            'global_namespace_import' => ['import_classes' => true, 'import_functions' => true, 'import_constants' => true],
            'header_comment' => ['location' => 'after_declare_strict', 'header' => $header],
            // 👇 override @Symfony - fait péter les phpdoc @psalm
            'phpdoc_to_comment' => false,
        ]
    )
    ->setFinder($finder);
