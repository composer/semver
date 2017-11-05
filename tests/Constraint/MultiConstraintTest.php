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

use PHPUnit\Framework\TestCase;

class MultiConstraintTest extends TestCase
{
    public function testMultiVersionMatchSucceeds()
    {
        $versionRequireStart = new Constraint('>', '1.0');
        $versionRequireEnd = new Constraint('<', '1.2');
        $versionProvide = new Constraint('==', '1.1');

        $multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));

        $this->assertTrue($multiRequire->matches($versionProvide));
    }

    public function testMultiVersionProvidedMatchSucceeds()
    {
        $versionRequireStart = new Constraint('>', '1.0');
        $versionRequireEnd = new Constraint('<', '1.2');
        $versionProvideStart = new Constraint('>=', '1.1');
        $versionProvideEnd = new Constraint('<', '2.0');

        $multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd));

        $this->assertTrue($multiRequire->matches($multiProvide));
    }

    public function testMultiVersionMatchFails()
    {
        $versionRequireStart = new Constraint('>', '1.0');
        $versionRequireEnd = new Constraint('<', '1.2');
        $versionProvide = new Constraint('==', '1.2');

        $multiRequire = new MultiConstraint(array($versionRequireStart, $versionRequireEnd));

        $this->assertFalse($multiRequire->matches($versionProvide));
    }
}
