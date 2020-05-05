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
use Composer\Semver\VersionParser;

class SubsetsTest extends TestCase
{
    /**
     * @dataProvider subsets
     */
    public function testIsSubsetOf($aStr, $bStr)
    {
        $versionParser = new VersionParser;
        $a = $versionParser->parseConstraints($aStr);
        $b = $versionParser->parseConstraints($bStr);

        $this->assertTrue($a->isSubsetOf($b), $aStr.' ('.$a.') should be seen as a subset of '.$bStr.' ('.$b.')');
    }

    public function subsets()
    {
        return array(
            // x is subset of y
            array('*',               '*'),
            array('1.0.0',           '*'),
            array('1.0.*',           '*'),
            array('^1.0 || ^2.0',    '*'),
            array('^1.0 || ^2.0',    '^1.0 || ^2.0'),
            array('^1.2',            '^1.0 || ^2.0'),
            array('^1.0 || ^2.0',    '^1.0'),
            array('1.2.3',           '^1.0 || ^2.0'),
            array('2.0.0-dev',       '^1.0 || ^2.0'),
            array('>= 2.1.0',        '>= 2.0.0'),
            array('^2.0',            '<3.0.0'),
            array('3.0.0',           '<= 3.0.0'),
            array('!= 3.0.0',        '*'),
            array('!= 3.0.0',        '!= 3.0'),
            array('!= 3.0.0',        '> 3.0 || < 3.0'),
            array('!= 3.0.0',        '^2.0 || <2 || >3'),
            array('>3',              '^2 || ^3 || >=4'),
            array('= dev-foo',        '= dev-foo'),
            array('!= dev-foo',        '!= dev-foo'),
        );
    }

    /**
     * @dataProvider notSubsets
     */
    public function testIsNotSubsetOf($aStr, $bStr)
    {
        $versionParser = new VersionParser;
        $a = $versionParser->parseConstraints($aStr);
        $b = $versionParser->parseConstraints($bStr);

        $this->assertFalse($a->isSubsetOf($b), $aStr.' ('.$a.') should not be seen as a subset of '.$bStr.' ('.$b.')');
    }

    public function notSubsets()
    {
        return array(
            // x is subset of y
            array('*',               '1.0.0'),
            array('*',               '1.0.*'),
            array('*',               '>= 1 || < 1'), // technically this should be a subset
            array('*',               '^1.0 || ^2.0'),
            array('^1.0 || ^2.0',    '^1.0, ^2.0'), // buggy constraint on the right here, checking it does not match
            array('^1.0 || ^2.0',    '^1.2'),
            array('^1.0 || ^2.0',    '1.2.3'),
            array('3.0.0',           '^1.0 || ^2.0'),
            array('3.0.0',           '< 3.0.0'),
            array('3.0.0',           '>= 3.0.1'),
            array('!= 3.0.0',        '= 3.0.0'),
            array('!= 3.0.0',        '!= 3.0.1'),
            array('>3',              '^2 || ^3 || >4'),
            array('^2.1',            '^2.0, !=2.1.3'),
            array('<2.0',            '>=1.1'),
            array('< dev-foo',       '= dev-foo'),
            array('!= dev-foo',      '!= dev-bar'),
            array('!= dev-foo',      '= dev-bar'),
        );
    }
}
