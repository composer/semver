<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver\Constraint;

interface CompilableConstraintInterface extends ConstraintInterface
{
    /**
     * Provides a compiled version of the constraint for the given operator
     * The compiled version must be a PHP expression.
     * Executor of compile version must provide 2 variables:
     * - $v = the string version to compare with
     * - $b = whether or not the version is a non-comparable branch (starts with "dev-")
     *
     * @see Constraint::OP_* for the list of available operators.
     * @example return '!$b && version_compare($v, '1.0', '>')';
     *
     * @param string $operator one of ==, !=, >, >=, <, <=
     *
     * @throws NotCompilableConstraintException if the constraint cannot be compiled
     *
     * @return string
     */
    public function compile($operator);
}
