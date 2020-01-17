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

use Composer\Semver\VersionParser;

class ComparisonDumper
{
    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @param VersionParser $versionParser
     */
    public function __construct(VersionParser $versionParser = null)
    {
        if (null === $versionParser) {
            $versionParser = new VersionParser();
        }

        $this->versionParser = $versionParser;
    }

    /**
     * Takes a given constraint and a given version and creates
     * the version_compare() statements required to evaluate
     * whether a version matches upper and lower bounds.
     *
     * @param ConstraintInterface $constraint
     * @param string              $version
     *
     * @return string
     */
    public function dump(ConstraintInterface $constraint, $version)
    {
        $version = $this->versionParser->normalize($version);
        $comparison = '';

        if (!$constraint->getLowerBound()->isLowerMost()) {
            $comparison .= sprintf(
                "version_compare('%s', '%s', '%s')",
                $version,
                $constraint->getLowerBound()->getVersion(),
                $constraint->getLowerBound()->isInclusive() ? '>=' : '>'
            );
        }

        if (!$constraint->getUpperBound()->isUpperMost()) {
            if ('' !== $comparison) {
                $comparison .= ' && ';
            }

            $comparison .= sprintf(
                "version_compare('%s', '%s', '%s')",
                $version,
                $constraint->getUpperBound()->getVersion(),
                $constraint->getUpperBound()->isInclusive() ? '<=' : '<'
            );
        }

        if ('' === $comparison) {
            $comparison .= 'true';
        }

        return $comparison;
    }
}
