<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/Classes',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'function_typehint_space' => true,
        'single_quote' => true,
        'no_empty_phpdoc' => true,
        'no_empty_comment' => true,
        'no_extra_blank_lines' => true,
        'no_superfluous_phpdoc_tags' => false,
        'no_trailing_comma_in_singleline' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'phpdoc_align' => false,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_blank_line_at_eof' => true,
        'single_line_after_imports' => true,
        'strict_param' => true,
        'void_return' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
