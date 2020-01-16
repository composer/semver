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
class MultiConstraint implements ConstraintInterface, BoundsProvidingInterface
{
    /** @var ConstraintInterface[] */
    protected $constraints;

    /** @var string */
    protected $prettyString;

    /** @var bool */
    protected $conjunctive;

    /** @var array */
    protected $lowerBound;

    /** @var array */
    protected $upperBound;

    /**
     * @param ConstraintInterface[] $constraints A set of constraints
     * @param bool                  $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
     */
    public function __construct(array $constraints, $conjunctive = true)
    {
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
     * @inheritDoc
     */
    public function getLowerBound()
    {
        $this->extractBounds();

        return $this->lowerBound;
    }

    /**
     * @inheritDoc
     */
    public function getUpperBound()
    {
        $this->extractBounds();

        return $this->upperBound;
    }

    private function extractBounds()
    {
        if (null !== $this->lowerBound) {
            return;
        }

        foreach ($this->constraints as $constraint) {
            if (!$constraint instanceof BoundsProvidingInterface) {
                continue; // TODO: or exception? Is it better to ignore those or not?
            }

            $constraintLower = $constraint->getLowerBound();
            $constraintUpper = $constraint->getUpperBound();

            if (null === $this->lowerBound && null === $this->upperBound) {
                $this->lowerBound = $constraintLower;
                $this->upperBound = $constraintUpper;
                continue;
            }

            if ($this->versionCompare($constraintLower, $this->lowerBound, $this->isConjunctive() ? '>' : '<')) {
                $this->lowerBound = $constraintLower;
            }

            if ($this->versionCompare($constraintUpper, $this->upperBound, $this->isConjunctive() ? '<' : '>')) {
                $this->upperBound = $constraintUpper;
            }
        }
    }

    private function versionCompare(array $boundA, array $boundB, $operator)
    {
        return version_compare(
            $this->prepareBoundForVersionCompare($boundA),
            $this->prepareBoundForVersionCompare($boundB),
            $operator
        );
    }

    private function prepareBoundForVersionCompare(array $bound)
    {
        $version = str_replace(BoundsProvidingInterface::UPPER_INFINITY, (string) PHP_INT_MAX, $bound[1]);

        switch ($bound[0]) {
            case '>=':
                $version .= '.6';
                break;
            case '>':
                $version .= '.7';
                break;
            case '<=':
                $version .= '.4';
                break;
            case '<':
                $version .= '.3';
                break;
        }

        return $version;
    }
}
