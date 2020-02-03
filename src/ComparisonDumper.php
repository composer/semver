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
     * Takes a given array of constraints and creates the version_compare()
     * statements required to evaluate whether a version matches
     * upper and lower bounds.
     *
     * It returns a string that can be evaluated at runtime
     * which means it is a return value so you can e.g.
     * assign it to a variable like so:
     *
     * $result = eval(str_replace('%version%', PHP_VERSION, $comparisonDumperResult));
     *
     * Make sure you use the correct placeholder, see ComparisonDumpConfig.
     *
     * If there are no bounds (lowermost and uppermost) this
     * method dumps "return true;".
     *
     * In case of successful checks, it will return "true", otherwise it will return the key
     * of the failing constraint you provided. This is why it's recommended to provide strings
     * as keys but you can also provide a regular indexed array.
     *
     * @param array<ComparisonDumpConfig> $dumpConfigs
     *
     * @return string
     */
    public function dump(array $dumpConfigs)
    {
        $statements = array();
        $statements[] = '$statements = array();';

        foreach ($dumpConfigs as $dumpConfig) {

            /** @var ConstraintInterface $constraint */
            $constraint = $dumpConfig->getConstraint();

            if ($constraint->getLowerBound()->isLowerMost() && $constraint->getUpperBound()->isUpperMost()) {
                continue;
            }

            $statement = array();
            $statement = array_merge($statement, $this->getInitLines($constraint, $dumpConfig->getVersionPlaceholder()));

            $comparison = 'return ';

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

            $comparison .= ';';
            $statement[] = $comparison;

            // Indent
            $statement = array_map(function ($line) {
                return '    ' . $line;
            }, $statement);

            // Add to statements
            $statements[] = sprintf('$statements[\'%s\'] = function() {', $dumpConfig->getKey());
            $statements = array_merge($statements, $statement);
            $statements[] = '};';
        }

        if (1 === count($statements)) {
            return 'return true;';
        }

        // Actual checks
        $statements[] = '';
        $statements[] = 'foreach ($statements as $k => $statement) {';
        $statements[] = '   if (!$statement()) {';
        $statements[] = '       return $k;';
        $statements[] = '   }';
        $statements[] = '}';
        $statements[] = '';
        $statements[] = 'return true;';

        return implode("\n", $statements);
    }

    /**
     * @param ConstraintInterface $constraint
     * @param $versionPlaceholder
     *
     * @return array
     */
    private function getInitLines(ConstraintInterface $constraint, $versionPlaceholder)
    {
        $init = array();
        $init[] = '$version = ' . $versionPlaceholder . ';';
        $init[] = '$version = preg_replace(array(\'/[-_+]/\', \'/([^\d\.])([^\D\.])/\', \'/([^\D\.])([^\d\.])/\'), array(\'.\', \'$1.$2\', \'$1.$2\'), $version);';
        $init[] = '$versionChunks = explode(\'.\', $version);';
        $init[] = '$versionLengths = array(count($versionChunks));';

        if (!$constraint->getLowerBound()->isLowerMost()) {
            $init[] = sprintf(
                '$lowerVersion = \'%s\';',
                $this->canonicalizeVersion($constraint->getLowerBound()->getVersion())
            );
            $init[] = '$lowerVersionChunks = explode(\'.\', $lowerVersion);';
            $init[] = '$versionLengths[] = count($lowerVersionChunks);';
        }

        if (!$constraint->getUpperBound()->isUpperMost()) {
            $init[] = sprintf(
                '$upperVersion = \'%s\';',
                $this->canonicalizeVersion($constraint->getUpperBound()->getVersion())
            );
            $init[] = '$upperVersionChunks = explode(\'.\', $upperVersion);';
            $init[] = '$versionLengths[] = count($upperVersionChunks);';
        }

        $init[] .= '$max = max($versionLengths);';
        $init[] .= '$version = implode(\'.\', array_pad($versionChunks, $max, \'0\'));';

        if (!$constraint->getLowerBound()->isLowerMost()) {
            $init[] .= '$lowerVersion = implode(\'.\', array_pad($lowerVersionChunks, $max, \'0\'));';
        }

        if (!$constraint->getUpperBound()->isUpperMost()) {
            $init[] .= '$upperVersion = implode(\'.\', array_pad($upperVersionChunks, $max, \'0\'));';
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
