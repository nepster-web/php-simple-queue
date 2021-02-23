<?php declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests']);

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR2' => true,
        'no_empty_phpdoc' => true,
        'single_blank_line_before_namespace' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sortAlgorithm' => 'length'],
        'no_spaces_after_function_name' => true,
        'no_whitespace_in_blank_line' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'no_unused_imports' => true,
        'standardize_not_equals' => true,
        'declare_strict_types' => true,
        'is_null' => true,
        'yoda_style' => false,
        'no_empty_statement' => true,
        'void_return' => true,
        'list_syntax' => ['syntax' => 'short'],
        'class_attributes_separation' => [
            'elements' => [
                'const',
                'method',
                'property',
            ]
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'return',
            ]
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
