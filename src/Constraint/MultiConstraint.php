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
 * Defines a conjunctive or disjunctive set of constraints.
 */
class MultiConstraint implements ConstraintInterface
{
    /** @var ConstraintInterface[] */
    private $constraints;

    /** @var string */
    private $prettyString;

    /** @var bool */
    private $conjunctive;

    /**
     * @param ConstraintInterface[] $constraints A set of constraints
     * @param bool $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
     */
    public function __construct(array $constraints, $conjunctive = true)
    {
        $this->constraints = $constraints;
        $this->conjunctive = $conjunctive;
    }

    /**
     * @param ConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(ConstraintInterface $provider)
    {
        if (false === $this->conjunctive) {
            foreach ($this->constraints as $constraint) {
                if ($constraint->matches($provider)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($this->constraints as $constraint) {
            if (!$constraint->matches($provider)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $prettyString
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

        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $constraints = array();
        foreach ($this->constraints as $constraint) {
            $constraints[] = (string) $constraint;
        }

        return '[' . implode($this->conjunctive ? ' ' : ' || ', $constraints) . ']';
    }
}
