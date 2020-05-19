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
    private static $intervalsCache = array();

    /**
     * @psalm-var array<string, int>
     */
    private static $opSortOrder = array(
        '>=' => -3,
        '<' => -2,
        '==' => -1,
        '!=' => 1,
        '>' => 2,
        '<=' => 3,
    );

    public static function clear()
    {
        self::$intervalsCache = array();
    }

    public static function isSubsetOf(ConstraintInterface $candidate, ConstraintInterface $constraint)
    {
        if ($constraint instanceof EmptyConstraint) {
            return true;
        }

        $intersectionIntervals = self::get(new MultiConstraint(array($candidate, $constraint), true));
        $candidateIntervals = self::get($candidate);
        if (\count($intersectionIntervals['intervals']) !== \count($candidateIntervals['intervals'])) {
            return false;
        }

        foreach ($intersectionIntervals['intervals'] as $index => $interval) {
            if (!isset($candidateIntervals['intervals'][$index])) {
                return false;
            }

            if ((string) $candidateIntervals['intervals'][$index]['start'] !== (string) $interval['start']) {
                return false;
            }

            if ((string) $candidateIntervals['intervals'][$index]['end'] !== (string) $interval['end']) {
                return false;
            }
        }

        if (\count($intersectionIntervals['devConstraints']) !== \count($candidateIntervals['devConstraints'])) {
            return false;
        }
        foreach ($intersectionIntervals['devConstraints'] as $index => $c) {
            if ((string) $c !== (string) $candidateIntervals['devConstraints'][$index]) {
                return false;
            }
        }

        return true;
    }

    public static function haveIntersections(ConstraintInterface $a, ConstraintInterface $b)
    {
        if ($a instanceof EmptyConstraint || $b instanceof EmptyConstraint) {
            return true;
        }

        $intersectionIntervals = self::generateIntervals(new MultiConstraint(array($a, $b), true), true);

        return \count($intersectionIntervals['intervals']) > 0 || \count($intersectionIntervals['devConstraints']) > 0;
    }

    /**
     * @return array
     */
    public static function get(ConstraintInterface $constraint)
    {
        $key = (string) $constraint;

        if (!isset(self::$intervalsCache[$key])) {
            self::$intervalsCache[$key] = self::generateIntervals($constraint);
        }

        return self::$intervalsCache[$key];
    }

    private static function generateIntervals(ConstraintInterface $constraint, $stopOnFirstValidInterval = false)
    {
        if ($constraint instanceof EmptyConstraint) {
            return array('intervals' => array(array('start' => self::zero(), 'end' => self::positiveInfinity())), 'devConstraints' => array(self::anyDev()));
        }

        if ($constraint instanceof Constraint) {
            $op = $constraint->getOperator();
            if (substr($constraint->getVersion(), 0, 4) === 'dev-') {
                $intervals = array();

                // != dev-foo means any numeric version may match
                if ($op === '!=') {
                    $intervals[] = array('start' => self::zero(), 'end' => self::positiveInfinity());
                }

                return array('intervals' => $intervals, 'devConstraints' => array($constraint));
            }

            if ($op[0] === '>') { // > & >=
                return array('intervals' => array(array('start' => $constraint, 'end' => self::positiveInfinity())), 'devConstraints' => array());
            }
            if ($op[0] === '<') { // < & <=
                return array('intervals' => array(array('start' => self::zero(), 'end' => $constraint)), 'devConstraints' => array());
            }
            if ($op === '!=') {
                // convert !=x to intervals of 0 - <x && >x - +inf + dev*
                return array('intervals' => array(
                    array('start' => self::zero(), 'end' => new Constraint('<', $constraint->getVersion())),
                    array('start' => new Constraint('>', $constraint->getVersion()), 'end' => self::positiveInfinity()),
                ), 'devConstraints' => array(self::anyDev()));
            }

            // convert ==x to an interval of >=x - <=x
            return array('intervals' => array(
                array('start' => new Constraint('>=', $constraint->getVersion()), 'end' => new Constraint('<=', $constraint->getVersion())),
            ), 'devConstraints' => array());
        }

        if (!$constraint instanceof MultiConstraint) {
            throw new \UnexpectedValueException('The constraint passed in should be an EmptyConstraint, Constraint or MultiConstraint instance, got '.get_class($constraint).'.');
        }

        $constraints = $constraint->getConstraints();

        $intervalGroups = array();
        $devGroups = array();
        foreach ($constraints as $c) {
            $res = self::get($c);
            $intervalGroups[] = $res['intervals'];
            $devGroups[] = $res['devConstraints'];
        }

        $dev = array();
        if ($constraint->isDisjunctive()) {
            foreach ($devGroups as $group) {
                foreach ($group as $c) {
                    if (!isset($dev[(string) $c])) {
                        $dev[(string) $c] = $c;
                    }
                }
            }

            foreach ($dev as $i => $c) {
                if ($c->getOperator() === '!=') {
                    foreach ($dev as $j => $c2) {
                        if ($i === $j) {
                            continue;
                        }
                        $op = $c2->getOperator();
                        // != dev-foo || != dev-bar -> *
                        if ($op === '!=' && $c->getVersion() !== $c2->getVersion()) {
                            $dev = array();
                            break 2;
                        }
                        // != dev-foo || == dev-foo -> *
                        if ($op === '==' && $c->getVersion() === $c2->getVersion()) {
                            $dev = array();
                            break 2;
                        }
                        // != dev-foo || == dev-master -> != dev-foo
                        if ($op === '==') {
                            unset($dev[$j]);
                            continue;
                        }
                    }
                }
            }
        } else {
            $blacklist = array();
            foreach ($devGroups as $i => $group) {
                foreach ($group as $j => $c) {
                    // all != constraints are kept
                    if ($c->getOperator() === '!=') {
                        $dev[(string) $c] = $c;
                        continue;
                    }

                    $otherGroupMatches = 0;
                    foreach ($devGroups as $i2 => $group2) {
                        if ($i2 === $i) {
                            continue;
                        }

                        foreach ($group2 as $j2 => $c2) {
                            if ((string) $c2 === (string) $c || $c2 === self::anyDev()) {
                                $otherGroupMatches++;
                            }

                            // != x && == x cancel each other, make sure none of these appears in the output
                            if ($c->getOperator() === '==' && $c2->getOperator() === '!=' && $c->getVersion() === $c2->getVersion()) {
                                $blacklist[] = (string) $c;
                                $blacklist[] = (string) $c2;
                            }
                        }
                    }

                    // only keep == constraints which appear in all conjunctive sub-constraints
                    if ($otherGroupMatches === \count($devGroups) - 1) {
                        $dev[(string) $c] = $c;
                    }
                }
            }
            foreach ($blacklist as $c) {
                unset($dev[$c]);
            }
        }

        $dev = array_values($dev);

        if (count($intervalGroups) === 1) {
            return array('intervals' => $intervalGroups[0], 'devConstraints' => $dev);
        }

        $borders = array();
        foreach ($intervalGroups as $group) {
            foreach ($group as $interval) {
                $borders[] = array('version' => $interval['start']->getVersion(), 'operator' => $interval['start']->getOperator(), 'side' => 'start');
                $borders[] = array('version' => $interval['end']->getVersion(), 'operator' => $interval['end']->getOperator(), 'side' => 'end');
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
        $activationThreshold = $constraint->isConjunctive() ? \count($intervalGroups) : 1;
        $active = false;
        foreach ($borders as $border) {
            if ($border['side'] === 'start') {
                $activeIntervals++;
            } else {
                $activeIntervals--;
            }
            if (!$active && $activeIntervals >= $activationThreshold) {
                $intervals[$index] = array('start' => new Constraint($border['operator'], $border['version']));
                $active = true;
            }
            if ($active && $activeIntervals < $activationThreshold) {
                $active = false;

                // filter out invalid intervals like > x - <= x, or >= x - < x
                if (
                    version_compare($intervals[$index]['start']->getVersion(), $border['version'], '=')
                    && (
                        ($intervals[$index]['start']->getOperator() === '>' && $border['operator'] === '<=')
                        || ($intervals[$index]['start']->getOperator() === '>=' && $border['operator'] === '<')
                    )
                ) {
                    unset($intervals[$index]);
                } else {
                    $intervals[$index]['end'] = new Constraint($border['operator'], $border['version']);
                    $index++;

                    if ($stopOnFirstValidInterval) {
                        break;
                    }
                }
            }
        }

        return array('intervals' => $intervals, 'devConstraints' => $dev);
    }

    public static function zero()
    {
        static $zero;

        if (null === $zero) {
            $zero = new Constraint('>=', '0.0.0.0-dev');
        }

        return $zero;
    }

    public static function positiveInfinity()
    {
        static $positiveInfinity;

        if (null === $positiveInfinity) {
            $positiveInfinity = new Constraint('<', PHP_INT_MAX.'.0.0.0');
        }

        return $positiveInfinity;
    }

    public static function anyDev()
    {
        static $anyDev;

        if (null === $anyDev) {
            // this ideally should be an EmptyConstraint but the code expects Constraint instances so
            // this makes it work with less workarounds/checks above
            $anyDev = new Constraint('==', 'dev*');
        }

        return $anyDev;
    }
}
