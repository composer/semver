<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;

/**
 * Helper class to evaluate constraint by compiling and reusing the code to evaluate
 */
class CompiledMatcher
{
    private static $checked = array();
    private static $enabled = null;

    /**
     * Evaluates the expression: $constraint match $operator $version
     *
     * @param ConstraintInterface $constraint
     * @param int                 $operator
     * @param string              $version
     *
     * @return mixed
     */
    public static function match(ConstraintInterface $constraint, $operator, $version)
    {
        if (self::$enabled === null) {
            self::$enabled = !in_array('eval', explode(',', ini_get('disable_functions')));
        }
        if (!self::$enabled) {
            $transOpInt = array(
                Constraint::OP_EQ => '==',
                Constraint::OP_LT => '<',
                Constraint::OP_LE => '<=',
                Constraint::OP_GT => '>',
                Constraint::OP_GE => '>=',
                Constraint::OP_NE => '!=',
            );
            return $constraint->matches(new Constraint($transOpInt[$operator], $version));
        }

        $cacheKey = $operator.$constraint;
        if (!isset(static::$checked[$cacheKey])) {
            $sha = \sha1($cacheKey);
            $function = 'composer_semver_constraint_'.$sha;
            static::$checked[$cacheKey] = $function;
            eval('function '.$function.'($v, $b){return '.$constraint->compile($operator).';}');
            $function = '\\'.$function;
        } else {
            $function = static::$checked[$cacheKey];
        }

        $v = $version;

        return $function($version, $v[0] === 'd' && 'dev-' === substr($v, 0, 4));
    }
}
