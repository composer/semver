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

use Composer\Semver\Constraint\CompilableConstraintInterface;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\NotCompilableConstraintException;

/**
 * Helper class to evaluate constraint by compiling and reusing the code to evaluate
 */
class CompilingMatcher
{
    private static $compiledCheckerCache = array();
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
        if (!self::$enabled || !$constraint instanceof CompilableConstraintInterface) {
            return $constraint->matches(new Constraint(self::$transOpInt[$operator], $version));
        }

        $cacheKey = $operator.$constraint;
        if (!isset(self::$compiledCheckerCache[$cacheKey])) {
            try {
                $code = $constraint->compile($operator);
                self::$compiledCheckerCache[$cacheKey] = $function = eval('return function($v, $b){return '.$code.';};');
            } catch (NotCompilableConstraintException $e) {
                $operator = self::$transOpInt[$operator];
                self::$compiledCheckerCache[$cacheKey] = $function = function($v, $b) use ($constraint, $operator) {
                    return $constraint->matches(new Constraint($operator, $v));
                };
            }
        } else {
            $function = self::$compiledCheckerCache[$cacheKey];
        }

        return $function($version, $version[0] === 'd' && 'dev-' === substr($version, 0, 4));
    }
}
