<?php

$header = <<<HEADER
This file is part of the Arnapou Simple Site package.

(c) Arnaud Buathier <arnaud@arnapou.net>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$finder = PhpCsFixer\Finder::create()
    ->notPath('tmp')
    ->in(
        [
            __DIR__ . '/build',
            __DIR__ . '/src',
        ]
    );

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PSR2'                               => true,
            '@PSR12'                              => true,
            '@Symfony'                            => true,
            '@DoctrineAnnotation'                 => true,
            '@PHP80Migration'                     => true,
            'concat_space'                        => ['spacing' => 'one'],
            'ordered_imports'                     => ['sort_algorithm' => 'alpha'],
            'native_function_invocation'          => ['include' => ['@compiler_optimized']],
            'combine_consecutive_issets'          => true,
            'combine_consecutive_unsets'          => true,
            'phpdoc_order'                        => true,
            'phpdoc_var_annotation_correct_order' => true,
            'global_namespace_import'             => ['import_classes' => false, 'import_functions' => false, 'import_constants' => false],
            'header_comment'                      => ['header' => $header],
        ]
    )
    ->setFinder($finder);
