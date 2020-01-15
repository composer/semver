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

interface BoundsProvidingInterface
{
    const UPPER_INFINITY = 'inf';

    /**
     * Returns the lower bound.
     * The return value is an array so you can use it
     * together with version_compare() right away.
     * Examples:
     *
     * Given the constraint "< 7.3" the return value will be:
     *
     * array('>=', '0')
     *
     * Given the constraint "> 7.3" the return value will be:
     *
     * array('>', '7.3')
     *
     * @return array
     */
    public function getLowerBound();

    /**
     * Returns the upper bound.
     * The return value is an array so you can use it
     * together with version_compare() right away.
     * Note that for infinite upper bounds, the constant BoundsProvidingInterface::UPPER_INFINITY
     * must be used.
     * Examples:
     *
     * Given the constraint "< 7.3" the return value will be:
     *
     * array('<', '7.3')
     *
     * Given the constraint "> 7.3" the return value will be:
     *
     * array('<', BoundsProvidingInterface::UPPER_INFINITY)
     *
     * @return array
     */
    public function getUpperBound();
}
