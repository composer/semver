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

class Semver
{
    /** @var VersionParser */
    private static $versionParser;

    /**
     * Determine if given version satisfies given constraints.
     *
     * @param string $version
     * @param string $constraints
     *
     * @return bool
     */
    public static function satisfies($version, $constraints)
    {
        if (null === self::$versionParser) {
            self::$versionParser = new VersionParser();
        }

        $versionParser = self::$versionParser;
        $provider = new Constraint('==', $versionParser->normalize($version));
        $constraints = $versionParser->parseConstraints($constraints);

        return $constraints->matches($provider);
    }

    /**
     * Return all versions that satisfy given constraints.
     *
     * @param string $constraints
     * @param array $versions
     *
     * @return array
     */
    public static function satisfiedBy($constraints, array $versions)
    {
        if (null === self::$versionParser) {
            self::$versionParser = new VersionParser();
        }

        $versionParser = self::$versionParser;
        $constraints = $versionParser->parseConstraints($constraints);
        $versions = array_filter($versions, function ($version) use ($constraints, $versionParser) {
            $version = new Constraint('==', $versionParser->normalize($version));

            return $constraints->matches($version);
        });

        return array_values($versions);
    }
}
