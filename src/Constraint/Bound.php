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

class Bound
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var bool
     */
    private $isInclusive;

    /**
     * @param string $version
     * @param bool   $isInclusive
     */
    public function __construct($version, $isInclusive)
    {
        $this->version = $version;
        $this->isInclusive = $isInclusive;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return bool
     */
    public function isInclusive()
    {
        return $this->isInclusive;
    }

    /**
     * Compares a bound to another with a given operator.
     *
     * @param Bound  $other
     * @param string $operator
     *
     * @return bool
     */
    public function compareTo(Bound $other, $operator)
    {
        if (!in_array($operator, array('<', '>'))) {
            throw new \InvalidArgumentException('Does not support any other operator other than > or <.');
        }

        // If they are the same it doesn't matter
        if ($this == $other) {
            return false;
        }

        // Not the same version means we can use regular version comparison and inclusive/exclusive doesn't matter
        if (!version_compare($this->getVersion(), $other->getVersion(), '==')) {
            return version_compare($this->getVersion(), $other->getVersion(), $operator);
        }

        // Question we're answering here is "am I higher than $other?"
        return '>' === $operator ? $other->isInclusive() : !$other->isInclusive();
    }

    public function __toString()
    {
        return sprintf(
            '%s [%s]',
            $this->getVersion(),
            $this->isInclusive() ? 'inclusive' : 'exclusive'
        );
    }

    /**
     * @return self
     */
    public static function lowerMost()
    {
        return new Bound('0', true);
    }

    /**
     * @return self
     */
    public static function upperMost()
    {
        return new Bound((string) PHP_INT_MAX, false);
    }
}
