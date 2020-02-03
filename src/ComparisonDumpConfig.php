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

use Composer\Semver\Constraint\ConstraintInterface;

class ComparisonDumpConfig
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var ConstraintInterface
     */
    private $constraint;

    /**
     * @var string
     */
    private $versionPlaceholder;

    /**
     * @param string              $key
     * @param ConstraintInterface $constraint
     * @param string              $versionPlaceholder
     */
    public function __construct($key, ConstraintInterface $constraint, $versionPlaceholder = "'%version%'")
    {
        $this->key = $key;
        $this->constraint = $constraint;
        $this->versionPlaceholder = $versionPlaceholder;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return ConstraintInterface
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * @return string
     */
    public function getVersionPlaceholder()
    {
        return $this->versionPlaceholder;
    }
}
