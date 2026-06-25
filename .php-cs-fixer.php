<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        '@PER-CS:risky' => true,
        '@PSR12' => true,
        '@PHP83Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'final_class' => true,
        'final_internal_class' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'mb_str_functions' => true,
        'modernize_types_casting' => true,
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced',
            'strict' => true,
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => false,
            'allow_unused_params' => false,
        ],
        'no_unneeded_final_method' => true,
        'no_unused_imports' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'none',
        ],
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'single_line_throw' => false,
        'strict_comparison' => true,
        'strict_param' => true,
        'void_return' => true,
    ])
    ->setFinder($finder);
