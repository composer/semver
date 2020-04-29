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
use Composer\Semver\Constraint\NotCompilableConstraintException;

/**
 * Helper class to evaluate constraint by compiling and reusing the code to evaluate
 */
class CompiledMatcher
{
    private static $checked = array();
    private static $enabled = null;

    private static $transOpInt = array(
        Constraint::OP_EQ => '==',
        Constraint::OP_LT => '<',
        Constraint::OP_LE => '<=',
        Constraint::OP_GT => '>',
        Constraint::OP_GE => '>=',
        Constraint::OP_NE => '!=',
    );

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
            return $constraint->matches(new Constraint(self::$transOpInt[$operator], $version));
        }

        $cacheKey = $operator.$constraint;
        if (!isset(static::$checked[$cacheKey])) {
            try {
                $code = $constraint->compile($operator);
                static::$checked[$cacheKey] = $function = eval('return function($v, $b){return '.$code.';};');
            } catch (NotCompilableConstraintException $e) {
                static::$checked[$cacheKey] = $function = function($v, $b) use ($constraint, $operator) {
                    return $constraint->matches(new Constraint(CompiledMatcher::$transOpInt[$operator], $v));
                };
            }
        } else {
            $function = static::$checked[$cacheKey];
        }

        $v = $version;

        return $function($version, $v[0] === 'd' && 'dev-' === substr($v, 0, 4));
    }
}
