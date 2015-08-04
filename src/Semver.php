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
     * Determine if given version satisfies given constraint.
     *
     * @param string $version
     * @param string $constraint
     *
     * @return bool
     */
    public static function satisfies($version, $constraint)
    {
        if (null === static::$versionParser) {
            static::$versionParser = new VersionParser();
        }

        $provider = new Constraint('==', static::$versionParser->normalize($version));
        $constraints = static::$versionParser->parseConstraints($constraint);

        return $constraints->matches($provider);
    }
}
