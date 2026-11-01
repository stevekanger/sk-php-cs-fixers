<?php

declare(strict_types=1);

use SkPhpCsFixers\Fixer\ArrayNotation\ArrayFormatFixer;

require __DIR__ . '/vendor/autoload.php';

$rules = [
    // Docs https://cs.symfony.com/doc/rules/index.html
    '@Symfony' => true,
    // Same line opening braces
    'braces_position' => [
        'functions_opening_brace' => 'same_line',
        'classes_opening_brace' => 'same_line',
        'control_structures_opening_brace' => 'same_line',
        'anonymous_functions_opening_brace' => 'same_line',
        'anonymous_classes_opening_brace' => 'same_line',
    ],
    // Single space before and after =>
    'binary_operator_spaces' => [
        'default' => 'single_space',
        'operators' => [
            '=>' => 'single_space',
        ],
    ],
    // Dont change alternative syntax in html
    'no_alternative_syntax' => [
        'fix_non_monolithic_code' => false,
    ],
    // Single line concat space
    'concat_space' => [
        'spacing' => 'one',
    ],
    // Proper spacing for function opening braces
    'function_declaration' => [
        'closure_fn_spacing' => 'none',
        'closure_function_spacing' => 'none',
    ],
    // Require strict types
    'declare_strict_types' => [
        'preserve_existing_declaration' => true,
    ],
    // Fix multiline args in methods/functions
    'method_argument_space' => true,
    // Allow multiline throws
    'single_line_throw' => false,
    // Replace superfluous else if
    'no_superfluous_elseif' => true,
    // Set PHPUnit method casing to match project
    'php_unit_method_casing' => [
        'case' => 'snake_case',
    ],
    // Allow params without descriptions
    'no_superfluous_phpdoc_tags' => false,
    // Add any missing params to phpdoc
    'phpdoc_add_missing_param_annotation' => [
        'only_untyped' => false,
    ],
    // Align PHPDoc to the left
    'phpdoc_align' => [
        'align' => 'left',
    ],
    // PHPDoc annotation descriptions should not be full sentences
    'phpdoc_annotation_without_dot' => false,
    // Set order for PHPDoc params
    'phpdoc_order' => [
        'order' => [
            'param',
            'return',
            'throws',
            'since',
        ],
    ],
    // order params in the order they are declared
    'phpdoc_param_order' => true,
    // Require PHPDoc summary to end with punctuation
    'phpdoc_summary' => true,
    // Set statements that require blank lines before
    'blank_line_before_statement' => [
        'statements' => [
            'break',
            'continue',
            'declare',
            'if',
            'return',
            'throw',
            'try',
        ],
    ],
    // Custom fixers
    'array_indentation' => false,
    'SkPhpCsFixers/array_format' => true,
];

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->notName('*.blade.php')
    ->exclude([
        'vendor',
        'build',
        'node_modules',
    ]);

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->registerCustomFixers([
        new ArrayFormatFixer(),
    ])
    ->setFinder($finder);
