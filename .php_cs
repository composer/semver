<?php

$header = <<<EOF
This file is part of composer/semver.

(c) Composer <https://github.com/composer>

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

/* fabpot/php-cs-fixer:^2.0-dev */
return  PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,
        'header_comment' => array('header' => $header),
        'include' => true,
        'method_separation' => true,
        'no_blank_lines_after_class_opening' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'single_blank_line_before_namespace' => true,
    ))
    ->setFinder($finder)
;
