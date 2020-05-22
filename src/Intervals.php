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
use Composer\Semver\Constraint\CompilableConstraintInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Semver\Constraint\MatchNoneConstraint;
use Composer\Semver\Constraint\MultiConstraint;

/**
 * Helper class to evaluate constraint by compiling and reusing the code to evaluate
 */
class Intervals
{
    /**
     * @phpstan-var array<string, array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}>
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
        if ($constraint instanceof MatchAllConstraint) {
            return true;
        }

        if ($candidate instanceof MatchNoneConstraint || $constraint instanceof MatchNoneConstraint) {
            return false;
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

        if ($intersectionIntervals['branches']['exclude'] !== $candidateIntervals['branches']['exclude']) {
            return false;
        }
        if (\count($intersectionIntervals['branches']['names']) !== \count($candidateIntervals['branches']['names'])) {
            return false;
        }
        foreach ($intersectionIntervals['branches']['names'] as $index => $name) {
            if ($name !== $candidateIntervals['branches']['names'][$index]) {
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
        if ($a instanceof MatchAllConstraint || $b instanceof MatchAllConstraint) {
            return true;
        }

        if ($a instanceof MatchNoneConstraint || $b instanceof MatchNoneConstraint) {
            return false;
        }

        $intersectionIntervals = self::generateIntervals(new MultiConstraint(array($a, $b), true), true);

        return \count($intersectionIntervals['numeric']) > 0 || $intersectionIntervals['branches']['exclude'] || \count($intersectionIntervals['branches']['names']) > 0;
    }

    /**
     * Attempts to optimize a MultiConstraint
     *
     * When merging MultiConstraints together they can get very large, this will
     * compact it by looking at the real intervals covered by all the constraints
     * and then creates a new constraint containing only the smallest amount of rules
     * to match the same intervals.
     *
     * @return CompilableConstraintInterface
     */
    public static function compactConstraint(ConstraintInterface $constraint)
    {
        if (!$constraint instanceof MultiConstraint) {
            return $constraint;
        }

        $intervals = self::generateIntervals($constraint);
        $constraints = array();
        $hasNumericMatchAll = false;
        $isConjunctive = false;

        $count = \count($intervals['numeric']);
        // attempt to convert back 0 - <x + >x - +inf to != x as long as we only have some of those, otherwise bail out of this optimization
        if ($count > 1 && (string) $intervals['numeric'][0]->getStart() === (string) Interval::zero() && (string) $intervals['numeric'][$count-1]->getEnd() === (string) Interval::positiveInfinity()) {
            $isConjunctive = true;
            for ($i = 0; $i < $count-1; $i++) {
                $interval = $intervals['numeric'][$i];
                if ($interval->getEnd()->getVersion() === $intervals['numeric'][$i+1]->getStart()->getVersion() && $interval->getEnd()->getOperator() === '<' && $intervals['numeric'][$i+1]->getStart()->getOperator() === '>') {
                    $constraints[] = new Constraint('!=', $interval->getEnd()->getVersion());
                    continue;
                }

                $constraints = array();
                $isConjunctive = false;
                break;
            }
        }

        if (!$isConjunctive) {
            for ($i = 0, $count = \count($intervals['numeric']); $i < $count; $i++) {
                $interval = $intervals['numeric'][$i];
                if ((string) $interval->getStart() === (string) Interval::zero() && (string) $interval->getEnd() === (string) Interval::positiveInfinity()) {
                    $constraints[] = $interval->getStart();
                    $hasNumericMatchAll = true;
                    break;
                }

                // convert back >= x - <= x intervals to == x
                if ($interval->getStart()->getVersion() === $interval->getEnd()->getVersion() && $interval->getStart()->getOperator() === '>=' && $interval->getEnd()->getOperator() === '<=') {
                    $constraints[] = new Constraint('==', $interval->getStart()->getVersion());
                    continue;
                }

                if ((string) $interval->getStart() === (string) Interval::zero()) {
                    $constraints[] = $interval->getEnd();
                } elseif ((string) $interval->getEnd() === (string) Interval::positiveInfinity()) {
                    $constraints[] = $interval->getStart();
                } else {
                    $constraints[] = new MultiConstraint(array($interval->getStart(), $interval->getEnd()), true);
                }
            }
        }

        $devConstraints = array();

        if (0 === \count($intervals['branches']['names'])) {
            if ($intervals['branches']['exclude']) {
                if ($hasNumericMatchAll) {
                    return new MatchAllConstraint;
                }
                // if conjunctive, then the constraint consists only of != operators, we already match all dev versions
                if (!$isConjunctive) {
                    // exclude nothing so allow all dev versions, this should not be possible while disallowing all numeric versions
                    throw new \RuntimeException("Unexpected constraint compaction: cannot represent constraint matching any branch but no numeric version.");
                }
            }
        } else {
            foreach ($intervals['branches']['names'] as $branchName) {
                if ($intervals['branches']['exclude']) {
                    $devConstraints[] = new Constraint('!=', $branchName);
                } else {
                    $devConstraints[] = new Constraint('==', $branchName);
                }
            }

            if ($intervals['branches']['exclude']) {
                if (!$isConjunctive) {
                    if (\count($devConstraints) > 1) {
                        $devConstraint = new MultiConstraint($devConstraints, true);
                    } else {
                        $devConstraint = $devConstraints[0];
                    }

                    if (\count($constraints) === 1 && (string)$constraints[0] === (string)Interval::zero()) {
                        return $devConstraint;
                    }

                    $constraints[] = $devConstraint;
                } else {
                    $constraints = array_merge($constraints, $devConstraints);
                }
            } else {
                $constraints = array_merge($constraints, $devConstraints);
            }
        }

        if (\count($constraints) > 1) {
            return new MultiConstraint($constraints, $isConjunctive);
        }

        if (\count($constraints) === 1) {
            return $constraints[0];
        }

        return new MatchNoneConstraint;
    }

    /**
     * Creates an array of numeric intervals and branch constraints representing a given constraint
     *
     * if the returned numeric array is empty it means the constraint matches nothing in the numeric range (0 - +inf)
     * if the returned branches array is empty it means no dev-* versions are matched
     * if a constraint matches all possible dev-* versions, branches will contain Interval::anyDev()
     *
     * @return array
     * @phpstan-return array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}
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
     * @phpstan-return array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}
     */
    private static function generateIntervals(ConstraintInterface $constraint, $stopOnFirstValidInterval = false)
    {
        if ($constraint instanceof MatchAllConstraint) {
            return array('numeric' => array(new Interval(Interval::zero(), Interval::positiveInfinity())), 'branches' => Interval::anyDev());
        }

        if ($constraint instanceof MatchNoneConstraint) {
            return array('numeric' => array(), 'branches' => array('names' => array(), 'exclude' => false));
        }

        if ($constraint instanceof Constraint) {
            return self::generateSingleConstraintIntervals($constraint);
        }

        if (!$constraint instanceof MultiConstraint) {
            throw new \UnexpectedValueException('The constraint passed in should be an MatchAllConstraint, Constraint or MultiConstraint instance, got '.get_class($constraint).'.');
        }

        $constraints = $constraint->getConstraints();

        $numericGroups = array();
        $constraintBranches = array();
        foreach ($constraints as $c) {
            $res = self::get($c);
            $numericGroups[] = $res['numeric'];
            $constraintBranches[] = $res['branches'];
        }

        if ($constraint->isDisjunctive()) {
            $branches = Interval::noDev();
            foreach ($constraintBranches as $b) {
                if ($b['exclude']) {
                    if ($branches['exclude']) {
                        // disjunctive constraint, so only exclude what's excluded in all constraints
                        // !=a,!=b || !=b,!=c => !=b
                        $branches['names'] = array_intersect($branches['names'], $b['names']);
                    } else {
                        // disjunctive constraint so exclude all names which are not explicitly included in the alternative
                        // (==b || ==c) || !=a,!=b => !=a
                        $branches['exclude'] = true;
                        $branches['names'] = array_diff($b['names'], $branches['names']);
                    }
                } else {
                    if ($branches['exclude']) {
                        // disjunctive constraint so exclude all names which are not explicitly included in the alternative
                        // !=a,!=b || (==b || ==c) => !=a
                        $branches['names'] = array_diff($branches['names'], $b['names']);
                    } else {
                        // disjunctive constraint, so just add all the other branches
                        // (==a || ==b) || ==c => ==a || ==b || ==c
                        $branches['names'] = array_merge($branches['names'], $b['names']);
                    }
                }
            }
        } else {
            $branches = Interval::anyDev();
            foreach ($constraintBranches as $b) {
                if ($b['exclude']) {
                    if ($branches['exclude']) {
                        // conjunctive, so just add all branch names to be excluded
                        // !=a && !=b => !=a,!=b
                        $branches['names'] = array_merge($branches['names'], $b['names']);
                    } else {
                        // conjunctive, so only keep included names which are not excluded
                        // (==a||==c) && !=a,!=b => ==c
                        $branches['names'] = array_diff($branches['names'], $b['names']);
                    }
                } else {
                    if ($branches['exclude']) {
                        // conjunctive, so only keep included names which are not excluded
                        // !=a,!=b && (==a||==c) => ==c
                        $branches['names'] = array_diff($b['names'], $branches['names']);
                        $branches['exclude'] = false;
                    } else {
                        // conjunctive, so only keep names that are included in both
                        // (==a||==b) && (==a||==c) => ==a
                        $branches['names'] = array_intersect($branches['names'], $b['names']);
                    }
                }
            }
        }

        $branches['names'] = array_unique($branches['names']);

        if (count($numericGroups) === 1) {
            return array('numeric' => $numericGroups[0], 'branches' => $branches);
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

        return array('numeric' => $intervals, 'branches' => $branches);
    }

    /**
     * @phpstan-return array{'numeric': Interval[], 'branches': array{'names': string[], 'exclude': bool}}}
     */
    private static function generateSingleConstraintIntervals(Constraint $constraint)
    {
        $op = $constraint->getOperator();

        // handle branch constraints first
        if (substr($constraint->getVersion(), 0, 4) === 'dev-') {
            $intervals = array();
            $branches = array('names' => array(), 'exclude' => false);

            // != dev-foo means any numeric version may match, we treat >/< like != they are not really defined for branches
            if ($op === '!=') {
                $intervals[] = new Interval(Interval::zero(), Interval::positiveInfinity());
                $branches = array('names' => array($constraint->getVersion()), 'exclude' => true);
            } elseif ($op === '==') {
                $branches['names'][] = $constraint->getVersion();
            }

            return array(
                'numeric' => $intervals,
                'branches' => $branches,
            );
        }

        if ($op[0] === '>') { // > & >=
            return array('numeric' => array(new Interval($constraint, Interval::positiveInfinity())), 'branches' => Interval::noDev());
        }
        if ($op[0] === '<') { // < & <=
            return array('numeric' => array(new Interval(Interval::zero(), $constraint)), 'branches' => Interval::noDev());
        }
        if ($op === '!=') {
            // convert !=x to intervals of 0 - <x && >x - +inf + dev*
            return array('numeric' => array(
                new Interval(Interval::zero(), new Constraint('<', $constraint->getVersion())),
                new Interval(new Constraint('>', $constraint->getVersion()), Interval::positiveInfinity()),
            ), 'branches' => Interval::anyDev());
        }

        // convert ==x to an interval of >=x - <=x
        return array('numeric' => array(
            new Interval(new Constraint('>=', $constraint->getVersion()), new Constraint('<=', $constraint->getVersion())),
        ), 'branches' => Interval::noDev());
    }
}
