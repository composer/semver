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
 * Defines a constraint on a link between two packages.
 *
 * @author Nils Adermann <naderman@naderman.de>
 */
interface LinkConstraintInterface
{
    /**
     * @param LinkConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(LinkConstraintInterface $provider);

    /**
     * @param string $prettyString
     */
    public function setPrettyString($prettyString);

    /**
     * @return string
     */
    public function getPrettyString();

    /**
     * @return string
     */
    public function __toString();
}
