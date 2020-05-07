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
        '>=' => -2,
        '<' => -1,
        '>' => 1,
        '<=' => 2,
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
//var_Dump((string) new MultiConstraint(array($candidate, $constraint), true), $intersectionIntervals['intervals'], $candidateIntervals['intervals']);
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

        return true;
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

    private static function generateIntervals(ConstraintInterface $constraint)
    {
        if ($constraint instanceof EmptyConstraint) {
            return array('intervals' => array(array('start' => self::zero(), 'end' => self::positiveInfinity())), 'devConstraints' => array());
        }

        if ($constraint instanceof Constraint) {
            if (substr($constraint->getVersion(), 0, 4) === 'dev-') {
                return array('intervals' => array(), 'devConstraints' => array($constraint));
            }

            $op = $constraint->getOperator();
            if ($op[0] === '>') { // > & >=
                return array('intervals' => array(array('start' => $constraint, 'end' => self::positiveInfinity())), 'devConstraints' => array());
            }
            if ($op[0] === '<') { // < & <=
                return array('intervals' => array(array('start' => self::zero(), 'end' => $constraint)), 'devConstraints' => array());
            }
            if ($op === '!=') {
                // convert !=x to intervals of 0 - <x && >x - +inf
                return array('intervals' => array(
                    array('start' => self::zero(), 'end' => new Constraint('<', $constraint->getVersion())),
                    array('start' => new Constraint('>', $constraint->getVersion()), 'end' => self::positiveInfinity()),
                ), 'devConstraints' => array());
            }

            // convert ==x to an interval of >=x - <=x
            return array('intervals' => array(
                array('start' => new Constraint('>=', $constraint->getVersion()), 'end' => new Constraint('<=', $constraint->getVersion())),
            ), 'devConstraints' => array());
        }

        $constraints = $constraint->getConstraints();

        $intervalGroups = array();
        $dev = array();
        foreach ($constraints as $c) {
            $res = self::get($c);
            if ($res['intervals']) {
                $intervalGroups[] = $res['intervals'];
            }
            if ($res['devConstraints']) {
                $dev = array_merge($dev, $res['devConstraints']);
            }
        }

        if (count($intervalGroups) === 1) {
            return array('intervals' => $intervalGroups[0], 'devConstraints' => $dev);
        }

        $borders = array();
        foreach ($intervalGroups as $group) {
            foreach ($group as $interval) {
                $borders[] = array('version' => $interval['start']->getVersion(), 'operator' => $interval['start']->getOperator(), 'side' =>'start');
                $borders[] = array('version' => $interval['end']->getVersion(), 'operator' => $interval['end']->getOperator(), 'side' =>'end');
            }
        }

        $opSortOrder = self::$opSortOrder;
        usort($borders, function ($a, $b) use ($opSortOrder) {
            if ($a['version'] === $b['version']) {
                return $opSortOrder[$a['operator']] - $opSortOrder[$b['operator']];
            }

            return version_compare($a['version'], $b['version']);
        });

        $activeIntervals = 0;
        $intervals = array();
        $activationThreshold = $constraint->isConjunctive() ? \count($intervalGroups) : 1;
        $active = false;
        foreach ($borders as $border) {
            if ($border['side'] === 'start') {
                $activeIntervals++;
            } else {
                $activeIntervals--;
            }
            if (!$active && $activeIntervals >= $activationThreshold) {
                $intervals[] = array('start' => new Constraint($border['operator'], $border['version']));
                $active = true;
            }
            if ($active && $activeIntervals < $activationThreshold) {
                $intervals[count($intervals)-1]['end'] = new Constraint($border['operator'], $border['version']);
                $active = false;
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
}
