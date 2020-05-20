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
 * Matches any branch version (dev-foo, dev-bar, etc.)
 */
class AnyDevConstraint implements CompilableConstraintInterface
{
    /** @var string|null */
    protected $prettyString;

    /**
     * @param ConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(ConstraintInterface $provider)
    {
        if ($provider instanceof self) {
            return true;
        }

        if ($provider instanceof Constraint) {
            $version = $provider->getVersion();

            // for dev versions this matches all == dev-x
            if ($version[0] === 'd' && 'dev-' === substr($version, 0, 4)) {
                return $provider->getOperator() === '==';
            }

            // for numeric versions this matches only != x.y
            return $provider->getOperator() === '!=';
        }

        // turn matching around to find a match
        return $provider->matches($this);
    }

    public function compile($otherOperator)
    {
        if (Constraint::OP_EQ === $otherOperator) {
            return '$b';
        }
        if (Constraint::OP_NE === $otherOperator) {
            return '!$b';
        }
        return 'false';
    }

    /**
     * @param string|null $prettyString
     */
    public function setPrettyString($prettyString)
    {
        $this->prettyString = $prettyString;
    }

    /**
     * @return string
     */
    public function getPrettyString()
    {
        if ($this->prettyString) {
            return $this->prettyString;
        }

        return (string) $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'dev*';
    }

    /**
     * {@inheritDoc}
     */
    public function getUpperBound()
    {
        return Bound::positiveInfinity();
    }

    /**
     * {@inheritDoc}
     */
    public function getLowerBound()
    {
        return Bound::zero();
    }
}
