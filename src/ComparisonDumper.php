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

class ComparisonDumper
{
    /**
     * Takes a given constraint and creates the version_compare()
     * statements required to evaluate whether a version matches
     * upper and lower bounds.
     * Uses '%version%' as placeholder by default so you can replace
     * it at runtime.
     * It can be configured by passing the second argument to e.g. allow
     * for a constant (without the wrapping quotes).
     *
     * It returns a string that can be evaluated at runtime
     * which means it is a return value so you can e.g.
     * assign it to a variable like so:
     *
     * $result = eval(str_replace('%version%', PHP_VERSION, $comparisionDumperResult));
     *
     * If there are no bounds (lowermost and uppermost) this
     * method dumps "return true;".
     *
     * @param ConstraintInterface $constraint
     * @param string              $versionPlaceholder The placeholder used for the version
     *
     * @return string
     */
    public function dump(ConstraintInterface $constraint, $versionPlaceholder = "'%version%'")
    {
        if ($constraint->getLowerBound()->isLowerMost() && $constraint->getUpperBound()->isUpperMost()) {
            return 'return true;';
        }

        $comparison = $this->getInit($constraint, $versionPlaceholder) . "\n" . 'return ';

        if (!$constraint->getLowerBound()->isLowerMost()) {
            $comparison .= sprintf(
                'version_compare($version, $lowerVersion, \'%s\')',
                $constraint->getLowerBound()->isInclusive() ? '>=' : '>'
            );
        }

        if (!$constraint->getUpperBound()->isUpperMost()) {
            if (!$constraint->getLowerBound()->isLowerMost()) {
                $comparison .= ' && ';
            }

            $comparison .= sprintf(
                'version_compare($version, $upperVersion, \'%s\')',
                $constraint->getUpperBound()->isInclusive() ? '<=' : '<'
            );
        }

        return $comparison . ';';
    }

    private function getInit(ConstraintInterface $constraint, $versionPlaceholder)
    {
        $init = '$version = ' . $versionPlaceholder . ';' . "\n";
        $init .= '$version = preg_replace(array(\'/[-_+]/\', \'/([^\d\.])([^\D\.])/\', \'/([^\D\.])([^\d\.])/\'), array(\'.\', \'$1.$2\', \'$1.$2\'), $version);' . "\n";
        $init .= '$versionChunks = explode(\'.\', $version);' . "\n";
        $init .= '$versionLengths = array(count($versionChunks));' . "\n";

        if (!$constraint->getLowerBound()->isLowerMost()) {
            $init .= sprintf(
                '$lowerVersion = \'%s\';' . "\n" . '$lowerVersionChunks = explode(\'.\', $lowerVersion);' . "\n" . '$versionLengths[] = count($lowerVersionChunks);' . "\n",
                $this->canonicalizeVersion($constraint->getLowerBound()->getVersion())
            );
        }

        if (!$constraint->getUpperBound()->isUpperMost()) {
            $init .= sprintf(
                '$upperVersion = \'%s\';' . "\n" . '$upperVersionChunks = explode(\'.\', $upperVersion);' . "\n" . '$versionLengths[] = count($upperVersionChunks);' . "\n",
                $this->canonicalizeVersion($constraint->getUpperBound()->getVersion())
            );
        }

        $init .= '$max = max($versionLengths);'. "\n";
        $init .= '$version = implode(\'.\', array_pad($versionChunks, $max, \'0\'));'. "\n";

        if (!$constraint->getLowerBound()->isLowerMost()) {
            $init .= '$lowerVersion = implode(\'.\', array_pad($lowerVersionChunks, $max, \'0\'));'. "\n";
        }

        if (!$constraint->getUpperBound()->isUpperMost()) {
            $init .= '$upperVersion = implode(\'.\', array_pad($upperVersionChunks, $max, \'0\'));'. "\n";
        }

        return $init;
    }

    private function canonicalizeVersion($version)
    {
        return preg_replace(
            array('/[-_+]/', '/([^\d\.])([^\D\.])/', '/([^\D\.])([^\d\.])/'),
            array('.', '$1.$2', '$1.$2'),
            $version
        );
    }
}
