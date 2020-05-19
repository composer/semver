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

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use Composer\Semver\Constraint\MultiConstraint;

/**
 * Helper class to evaluate constraint by compiling and reusing the code to evaluate
 */
class Intervals
{
    /**
     * @phpstan-var array<string, array{'numeric': Interval[], 'branches': Constraint[]}>
     */
    private static $intervalsCache = array();

    /**
     * @phpstan-var array<string, int>
     */
    private static $opSortOrder = array(
        '>=' => -3,
        '<' => -2,
        '>' => 2,
        '<=' => 3,
    );

    /**
     * Clears the memoization cache once you are done
     *
     * @return void
     */
    public static function clear()
    {
        self::$intervalsCache = array();
    }

    /**
     * Checks whether $candidate is a subset of $constraint
     *
     * @return bool
     */
    public static function isSubsetOf(ConstraintInterface $candidate, ConstraintInterface $constraint)
    {
        if ($constraint instanceof EmptyConstraint) {
            return true;
        }

        $intersectionIntervals = self::get(new MultiConstraint(array($candidate, $constraint), true));
        $candidateIntervals = self::get($candidate);
        if (\count($intersectionIntervals['numeric']) !== \count($candidateIntervals['numeric'])) {
            return false;
        }

        foreach ($intersectionIntervals['numeric'] as $index => $interval) {
            if (!isset($candidateIntervals['numeric'][$index])) {
                return false;
            }

            if ((string) $candidateIntervals['numeric'][$index]->getStart() !== (string) $interval->getStart()) {
                return false;
            }

            if ((string) $candidateIntervals['numeric'][$index]->getEnd() !== (string) $interval->getEnd()) {
                return false;
            }
        }

        if (\count($intersectionIntervals['branches']) !== \count($candidateIntervals['branches'])) {
            return false;
        }
        foreach ($intersectionIntervals['branches'] as $index => $c) {
            if ((string) $c !== (string) $candidateIntervals['branches'][$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether $a and $b have any intersection, equivalent to $a->matches($b)
     *
     * @return bool
     */
    public static function haveIntersections(ConstraintInterface $a, ConstraintInterface $b)
    {
        if ($a instanceof EmptyConstraint || $b instanceof EmptyConstraint) {
            return true;
        }

        $intersectionIntervals = self::generateIntervals(new MultiConstraint(array($a, $b), true), true);

        return \count($intersectionIntervals['numeric']) > 0 || \count($intersectionIntervals['branches']) > 0;
    }

    /**
     * Creates an array of numeric intervals and branch constraints representing a given constraint
     *
     * if the returned numeric array is empty it means the constraint matches nothing in the numeric range (0 - +inf)
     * if the returned branches array is empty it means no dev-* versions are matched
     * if a constraint matches all possible dev-* versions, branches will contain Interval::anyDev() as a constraint
     *
     * @return array
     * @phpstan-return array{'numeric': Interval[], 'branches': Constraint[]}
     */
    public static function get(ConstraintInterface $constraint)
    {
        $key = (string) $constraint;

        if (!isset(self::$intervalsCache[$key])) {
            self::$intervalsCache[$key] = self::generateIntervals($constraint);
        }

        return self::$intervalsCache[$key];
    }

    /**
     * @phpstan-return array{'numeric': Interval[], 'branches': Constraint[]}
     */
    private static function generateIntervals(ConstraintInterface $constraint, $stopOnFirstValidInterval = false)
    {
        if ($constraint instanceof EmptyConstraint) {
            return array('numeric' => array(new Interval(Interval::zero(), Interval::positiveInfinity())), 'branches' => array(Interval::anyDev()));
        }

        if ($constraint instanceof Constraint) {
            return self::generateSingleConstraintIntervals($constraint);
        }

        if (!$constraint instanceof MultiConstraint) {
            throw new \UnexpectedValueException('The constraint passed in should be an EmptyConstraint, Constraint or MultiConstraint instance, got '.get_class($constraint).'.');
        }

        $constraints = $constraint->getConstraints();

        $numericGroups = array();
        $branchesGroups = array();
        foreach ($constraints as $c) {
            $res = self::get($c);
            $numericGroups[] = $res['numeric'];
            $branchesGroups[] = $res['branches'];
        }

        $branchConstraints = array();
        if ($constraint->isDisjunctive()) {
            foreach ($branchesGroups as $group) {
                foreach ($group as $c) {
                    if (!isset($branchConstraints[(string) $c])) {
                        $branchConstraints[(string) $c] = $c;
                    }
                }
            }

            foreach ($branchConstraints as $i => $c) {
                if ($c === Interval::anyDev()) {
                    $branchConstraints = array($c);
                    break;
                }

                if ($c->getOperator() === '!=') {
                    foreach ($branchConstraints as $j => $c2) {
                        if ($i === $j) {
                            continue;
                        }
                        $op = $c2->getOperator();
                        // != dev-foo || != dev-bar -> *
                        if ($op === '!=' && $c->getVersion() !== $c2->getVersion()) {
                            $branchConstraints = array();
                            break 2;
                        }
                        // != dev-foo || == dev-foo -> *
                        if ($op === '==' && $c->getVersion() === $c2->getVersion()) {
                            $branchConstraints = array();
                            break 2;
                        }
                        // != dev-foo || == dev-master -> != dev-foo
                        if ($op === '==') {
                            unset($branchConstraints[$j]);
                            continue;
                        }
                    }
                }
            }
        } else {
            $disallowlist = array();
            foreach ($branchesGroups as $i => $group) {
                foreach ($group as $j => $c) {
                    // all != constraints are kept
                    if ($c->getOperator() === '!=') {
                        $branchConstraints[(string) $c] = $c;
                        continue;
                    }

                    $otherGroupMatches = 0;
                    foreach ($branchesGroups as $i2 => $group2) {
                        if ($i2 === $i) {
                            continue;
                        }

                        foreach ($group2 as $j2 => $c2) {
                            if ((string) $c2 === (string) $c || $c2 === Interval::anyDev()) {
                                $otherGroupMatches++;
                            }

                            // != x && == x cancel each other, make sure none of these appears in the output
                            if ($c->getOperator() === '==' && $c2->getOperator() === '!=' && $c->getVersion() === $c2->getVersion()) {
                                $disallowlist[(string) $c] = true;
                                $disallowlist[(string) $c2] = true;
                            }
                        }
                    }

                    // only keep == constraints which appear in all conjunctive sub-constraints
                    if ($otherGroupMatches === \count($branchesGroups) - 1) {
                        $branchConstraints[(string) $c] = $c;
                    }
                }
            }
            foreach ($disallowlist as $c => $dummy) {
                unset($branchConstraints[$c]);
            }
        }

        $branchConstraints = array_values($branchConstraints);

        if (count($numericGroups) === 1) {
            return array('numeric' => $numericGroups[0], 'branches' => $branchConstraints);
        }

        $borders = array();
        foreach ($numericGroups as $group) {
            foreach ($group as $interval) {
                $borders[] = array('version' => $interval->getStart()->getVersion(), 'operator' => $interval->getStart()->getOperator(), 'side' => 'start');
                $borders[] = array('version' => $interval->getEnd()->getVersion(), 'operator' => $interval->getEnd()->getOperator(), 'side' => 'end');
            }
        }

        $opSortOrder = self::$opSortOrder;
        usort($borders, function ($a, $b) use ($opSortOrder) {
            $order = version_compare($a['version'], $b['version']);
            if ($order === 0) {
                return $opSortOrder[$a['operator']] - $opSortOrder[$b['operator']];
            }

            return $order;
        });

        $activeIntervals = 0;
        $intervals = array();
        $index = 0;
        $activationThreshold = $constraint->isConjunctive() ? \count($numericGroups) : 1;
        $active = false;
        $start = null;
        foreach ($borders as $border) {
            if ($border['side'] === 'start') {
                $activeIntervals++;
            } else {
                $activeIntervals--;
            }
            if (!$active && $activeIntervals >= $activationThreshold) {
                $start = new Constraint($border['operator'], $border['version']);
                $active = true;
            }
            if ($active && $activeIntervals < $activationThreshold) {
                $active = false;

                // filter out invalid intervals like > x - <= x, or >= x - < x
                if (
                    version_compare($start->getVersion(), $border['version'], '=')
                    && (
                        ($start->getOperator() === '>' && $border['operator'] === '<=')
                        || ($start->getOperator() === '>=' && $border['operator'] === '<')
                    )
                ) {
                    unset($intervals[$index]);
                } else {
                    $intervals[$index] = new Interval($start, new Constraint($border['operator'], $border['version']));
                    $index++;

                    if ($stopOnFirstValidInterval) {
                        break;
                    }
                }

                $start = null;
            }
        }

        return array('numeric' => $intervals, 'branches' => $branchConstraints);
    }

    /**
     * @phpstan-return array{'numeric': Interval[], 'branches': Constraint[]}
     */
    private static function generateSingleConstraintIntervals(Constraint $constraint)
    {
        $op = $constraint->getOperator();

        // handle branch constraints first
        if (substr($constraint->getVersion(), 0, 4) === 'dev-') {
            $intervals = array();

            // != dev-foo means any numeric version may match
            if ($op === '!=') {
                $intervals[] = new Interval(Interval::zero(), Interval::positiveInfinity());
            }

            return array('numeric' => $intervals, 'branches' => array($constraint));
        }

        if ($op[0] === '>') { // > & >=
            return array('numeric' => array(new Interval($constraint, Interval::positiveInfinity())), 'branches' => array());
        }
        if ($op[0] === '<') { // < & <=
            return array('numeric' => array(new Interval(Interval::zero(), $constraint)), 'branches' => array());
        }
        if ($op === '!=') {
            // convert !=x to intervals of 0 - <x && >x - +inf + dev*
            return array('numeric' => array(
                new Interval(Interval::zero(), new Constraint('<', $constraint->getVersion())),
                new Interval(new Constraint('>', $constraint->getVersion()), Interval::positiveInfinity()),
            ), 'branches' => array(Interval::anyDev()));
        }

        // convert ==x to an interval of >=x - <=x
        return array('numeric' => array(
            new Interval(new Constraint('>=', $constraint->getVersion()), new Constraint('<=', $constraint->getVersion())),
        ), 'branches' => array());
    }
}
