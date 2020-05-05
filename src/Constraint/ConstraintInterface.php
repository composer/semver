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

/**
 * DO NOT IMPLEMENT this interface. It is only meant for usage as a type hint
 * in libraries relying on composer/semver but creating your own constraint class
 * that implements this interface is not a supported use case and will cause the
 * composer/semver components to return unexpected results.
 */
interface ConstraintInterface
{
    /**
     * Checks whether the given constraint intersects in any way with this constraint
     *
     * @param ConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(ConstraintInterface $provider);

    /**
     * Checks whether the given constraint is wholly contained within this constraint
     *
     * @param ConstraintInterface $constraint
     *
     * @return bool
     */
    public function isSubsetOf(ConstraintInterface $constraint);

    /**
     * @return Bound
     */
    public function getUpperBound();

    /**
     * @return Bound
     */
    public function getLowerBound();

    /**
     * @return string
     */
    public function getPrettyString();

    /**
     * @param string|null $prettyString
     */
    public function setPrettyString($prettyString);

    /**
     * @return string
     */
    public function __toString();
}
