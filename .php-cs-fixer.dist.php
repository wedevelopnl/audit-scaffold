<?php declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->files()
    ->name('*.php')
    ->in([
        __DIR__ . '/src',
    ])
    ->ignoreVCS(true);

$cacheDir = !empty($_ENV['CI']) && !empty($_ENV['CI_PROJECT_DIR']) ? $_ENV['CI_PROJECT_DIR'] : __DIR__;
$cacheFile = sprintf('%s/.php-cs-fixer.cache', $cacheDir);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setCacheFile($cacheFile)
    ->setRules([

        '@PHP82Migration' => true,
        '@PER-CS' => true,
        '@PER-CS:risky' => true,

        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'backtick_to_shell_exec' => true,
        'binary_operator_spaces' => true,
        'blank_line_after_opening_tag' => false,
        'blank_line_before_statement' => ['statements' => []],
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => ['elements' => ['const' => 'none', 'method' => 'one', 'property' => 'only_if_meta', 'trait_import' => 'none', 'case' => 'none']],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'function_declaration' => ['closure_fn_spacing' => 'one', 'closure_function_spacing' => 'one'],
        'linebreak_after_opening_tag' => false,
        'mb_str_functions' => true,
        'modernize_strpos' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true],
        'no_unused_imports' => true,
        'no_whitespace_in_blank_line' => true,
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => true],
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'phpdoc_line_span' => ['const' => 'single', 'method' => 'single', 'property' => 'single'],
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'protected_to_private' => true,
        'psr_autoloading' => true,
        'semicolon_after_instruction' => true,
        'single_blank_line_at_eof' => true,
        'single_quote' => true,
        'static_lambda' => true,
        'strict_param' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'trim_array_spaces' => true,
        'visibility_required' => true,
        'void_return' => true,
        'yoda_style' => true,

    ]);
