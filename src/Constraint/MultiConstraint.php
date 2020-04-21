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
    protected $constraints;

    /** @var string|null */
    protected $prettyString;

    /** @var bool */
    protected $conjunctive;

    /** @var Bound|null */
    protected $lowerBound;

    /** @var Bound|null */
    protected $upperBound;

    /**
     * @param ConstraintInterface[] $constraints A set of constraints
     * @param bool                  $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
     *
     * @throws \InvalidArgumentException If less than 2 constraints are passed
     */
    public function __construct(array $constraints, $conjunctive = true)
    {
        if (count($constraints) < 2) {
            throw new \InvalidArgumentException(
                'Must provide at least two constraints for a MultiConstraint. Use '.
                'the regular Constraint class for one constraint only or EmptyConstraint for none. You may use '.
                'MultiConstraint::create() which optimizes and handles those cases automatically.'
            );
        }

        $this->constraints = $constraints;
        $this->conjunctive = $conjunctive;
    }

    /**
     * @return ConstraintInterface[]
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * @return bool
     */
    public function isConjunctive()
    {
        return $this->conjunctive;
    }

    /**
     * @return bool
     */
    public function isDisjunctive()
    {
        return !$this->conjunctive;
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
        $constraints = array();
        foreach ($this->constraints as $constraint) {
            $constraints[] = (string) $constraint;
        }

        return '[' . implode($this->conjunctive ? ' ' : ' || ', $constraints) . ']';
    }

    /**
     * {@inheritDoc}
     */
    public function getLowerBound()
    {
        $this->extractBounds();

        return $this->lowerBound;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpperBound()
    {
        $this->extractBounds();

        return $this->upperBound;
    }

    /**
     * Tries to optimize the constraints as much as possible, meaning
     * reducing/collapsing congruent constraints etc.
     * Does not necessarily return a MultiConstraint instance if
     * things can be reduced to a simple constraint
     *
     * @param ConstraintInterface[] $constraints A set of constraints
     * @param bool                  $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
     *
     * @return ConstraintInterface
     */
    public static function create(array $constraints, $conjunctive = true)
    {
        if (0 === count($constraints)) {
            return new EmptyConstraint();
        }

        if (1 === count($constraints)) {
            return $constraints[0];
        }

        $optimized = self::optimizeConstraints($constraints, $conjunctive);
        if ($optimized !== null) {
            list($constraints, $conjunctive) = $optimized;
        }

        return new self($constraints, $conjunctive);
    }

    /**
     * @return array|null
     */
    private static function optimizeConstraints(array $constraints, $conjunctive)
    {
        // parse the two OR groups and if they are contiguous we collapse
        // them into one constraint
        if (!$conjunctive
            && 2 === count($constraints)
            && $constraints[0] instanceof MultiConstraint
            && $constraints[1] instanceof MultiConstraint
            && 2 === count($constraints[0]->getConstraints())
            && 2 === count($constraints[1]->getConstraints())
            && ($a = (string) $constraints[0])
            && strpos($a, '[>=') === 0 && (false !== ($posA = strpos($a, '<', 4)))
            && ($b = (string) $constraints[1])
            && strpos($b, '[>=') === 0 && (false !== ($posB = strpos($b, '<', 4)))
            && substr($a, $posA + 2, -1) === substr($b, 4, $posB - 5)
        ) {
            return array(
                array(
                    new Constraint('>=', substr($a, 4, $posB - 5)),
                    new Constraint('<', substr($b, $posB + 2, -1)),
                ),
                true,
            );
        }

        // TODO: Here's the place to put more optimizations

        return null;
    }

    private function extractBounds()
    {
        if (null !== $this->lowerBound) {
            return;
        }

        foreach ($this->constraints as $constraint) {
            if (null === $this->lowerBound && null === $this->upperBound) {
                $this->lowerBound = $constraint->getLowerBound();
                $this->upperBound = $constraint->getUpperBound();
                continue;
            }

            if ($constraint->getLowerBound()->compareTo($this->lowerBound, $this->isConjunctive() ? '>' : '<')) {
                $this->lowerBound = $constraint->getLowerBound();
            }

            if ($constraint->getUpperBound()->compareTo($this->upperBound, $this->isConjunctive() ? '<' : '>')) {
                $this->upperBound = $constraint->getUpperBound();
            }
        }
    }
}
