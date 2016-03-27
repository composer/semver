<?php

$header = <<<EOF
This file is part of composer/semver.

(c) Composer <https://github.com/composer>

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

/* fabpot/php-cs-fixer:^2.0-dev */
return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(array(
        '@PSR2',
        'duplicate_semicolon',
        'extra_empty_lines',
        'header_comment',
        'include',
        'long_array_syntax',
        'method_separation',
        'multiline_array_trailing_comma',
        'namespace_no_leading_whitespace',
        'no_blank_lines_after_class_opening',
        'no_empty_lines_after_phpdocs',
        'object_operator',
        'operators_spaces',
        'phpdoc_indent',
        'phpdoc_no_access',
        'phpdoc_no_package',
        'phpdoc_order',
        'phpdoc_scalar',
        'phpdoc_separation',
        'phpdoc_trim',
        'phpdoc_type_to_var',
        'return',
        'remove_leading_slash_use',
        'remove_lines_between_uses',
        'single_array_no_trailing_comma',
        'single_blank_line_before_namespace',
        'spaces_cast',
        'standardize_not_equal',
        'ternary_spaces',
        'unused_use',
        'whitespacy_lines',
    ))
    ->finder($finder)
;
