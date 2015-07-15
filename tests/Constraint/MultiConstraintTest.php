<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Test\Semver\Constraint;

use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Constraint\VersionConstraint;

class MultiConstraintTest extends \PHPUnit_Framework_TestCase
{
    public function testMultiVersionMatchSucceeds()
    {
        $versionRequireStart = new VersionConstraint('>', '1.0');
        $versionRequireEnd = new VersionConstraint('<', '1.2');
        $versionProvide = new VersionConstraint('==', '1.1');

        $multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));

        $this->assertTrue($multiRequire->matches($versionProvide));
    }

    public function testMultiVersionProvidedMatchSucceeds()
    {
        $versionRequireStart = new VersionConstraint('>', '1.0');
        $versionRequireEnd = new VersionConstraint('<', '1.2');
        $versionProvideStart = new VersionConstraint('>=', '1.1');
        $versionProvideEnd = new VersionConstraint('<', '2.0');

        $multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd));

        $this->assertTrue($multiRequire->matches($multiProvide));
    }

    public function testMultiVersionMatchFails()
    {
        $versionRequireStart = new VersionConstraint('>', '1.0');
        $versionRequireEnd = new VersionConstraint('<', '1.2');
        $versionProvide = new VersionConstraint('==', '1.2');

        $multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));

        $this->assertFalse($multiRequire->matches($versionProvide));
    }
}
