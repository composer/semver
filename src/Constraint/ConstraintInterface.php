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
 * where appropriate but we do not support third parties implementing this
 * interface themselves, and will do BC breaks to the interface as we see fit.
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
