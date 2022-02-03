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

use PHPUnit\Framework\TestCase;
use Composer\Semver\Constraint\MatchNoneConstraint;
use Composer\Semver\Constraint\MatchAllConstraint;

class SubsetsTest extends TestCase
{
    /**
     * @dataProvider subsets
     * @param string $aStr
     * @param string $bStr
     */
    public function testIsSubsetOf($aStr, $bStr)
    {
        $versionParser = new VersionParser;
        $a = $versionParser->parseConstraints($aStr);
        $b = $versionParser->parseConstraints($bStr);

        $this->assertTrue(Intervals::isSubsetOf($a, $b), $aStr.' ('.$a.') should be seen as a subset of '.$bStr.' ('.$b.')');
    }

    /**
     * @return array<mixed>
     */
    public function subsets()
    {
        return array(
            // x is subset of y
            array('*',               '*'),
            array('*',               '!= 1 || == 1'),
            array('1.0.0',           '*'),
            array('1.0.*',           '*'),
            array('^1.0 || ^2.0',    '*'),
            array('^3.0',            '^3.2 || *'),
            array('^1.0 || ^2.0',    '^1.0 || ^2.0'),
            array('^1.0 || ^2.0',    '^1.0 || ^2.0 || ^4.0'),
            array('^1.0 || ^2.1',    '^1.0 || ^2.1 || ^4.0'),
            array('^1.2',            '^1.0 || ^2.0'),
            array('1.2.3',           '^1.0 || ^2.0'),
            array('2.0.0-dev',       '^1.0 || ^2.0'),
            array('>= 2.1.0',        '>= 2.0.0'),
            array('^2.0',            '<3.0.0'),
            array('^3.0',            '> 2.1.3'),
            array('3.0.0',           '<= 3.0.0'),
            array('!= 3.0.0',        '*'),
            array('!= 3.0.0',        '!= 3.0'),
            array('!= 3.0, != 2.0',  '!= 2.0, != 3.0'),
            array('>3',              '^2 || ^3 || >=4'),
            array('>3',              '>=3'),
            array('<3',              '<=3'),
            array('= dev-foo',       '= dev-foo'),
            array('!= dev-foo',      '!= dev-foo'),
            array('< dev-foo',       '= dev-foo'), // invalid range matches nothing so is a subset of any other
            array('1.5.*',           '^1.4'),
            array('1.5.*',           '1.3 - 1.6 || 1.8 - 1.9'),
            array('1.3.2',           '1.3.0 || 1.3.1 || 1.3.2'),
            array('1.3.1',           '1.3.0 || 1.3.1 || 1.3.2'),
            array('1.3.1 || 1.3.1',  '1.3.1'),
            array('^1.0 || ^3.2',    '^1.0 || ^3.0'),
            array('^1.3 || ^3.2',    '>1.2'),
            array('^1.6',            '<1.3 || >1.5'),
            array('>1.6',            '<1.3 || >1.5'),
            array('>1.6',            '>1.5, >1.4, !=1.1'),
            array('>1.6',            '>1.5 || >1.7'),
            array('^1.1',            '> 1.0.0'),
            array('^1.1, !=1.5.0',   '> 1.0.0'),
            array('^1.1, !=0.5.0',   '> 1.0.0'),
            array('^2.0 || dev-foo', '> 1.0 || dev-foo || dev-bar'),
            array('^1.0, ^1.2',      '>=1.2'),
            array('^1.0, ^1.2',      '^1.2'),
            array('^1.0, ^1.2 || ^1.3', '^1.2'),
        );
    }

    /**
     * @dataProvider notSubsets
     * @param string $aStr
     * @param string $bStr
     */
    public function testIsNotSubsetOf($aStr, $bStr)
    {
        $versionParser = new VersionParser;
        $a = $versionParser->parseConstraints($aStr);
        $b = $versionParser->parseConstraints($bStr);

        $this->assertFalse(Intervals::isSubsetOf($a, $b), $aStr.' ('.$a.') should not be seen as a subset of '.$bStr.' ('.$b.')');
    }

    /**
     * @return array<mixed>
     */
    public function notSubsets()
    {
        return array(
            // x is subset of y
            array('*',               '>= 1 || < 1'), // it is a subset of the numeric interval, but * allows dev- branches while the latter does not
            array('*',               '1.0.0'),
            array('*',               '1.0.*'),
            array('*',               '^1.0 || ^2.0'),
            array('^1.0 || ^2.0',    '^1.0, ^2.0'), // buggy constraint on the right here, checking it does not match
            array('^1.0 || ^2.0',    '^1.2'),
            array('^1.0 || ^2.0',    '^1.0'),
            array('^1.0 || ^2.0',    '1.2.3'),
            array('^1.0 || ^3.0',    '1.2.3'),
            array('3.0.0',           '^1.0 || ^2.0'),
            array('3.0.0',           '< 3.0.0'),
            array('3.0.0',           '>= 3.0.1'),
            array('!= 3.0.0',        '> 3.0.0 || < 3.0.0-stable'), // it is a subset of the numeric interval, but != x allows dev- branches while the right side does not
            array('!= 3.0.0-dev',    '^2.0 || <2 || >3.0-dev'), // it is a subset of the numeric interval, but != x allows dev- branches while the right side does not
            array('!= 3.0.0',        '= 3.0.0'),
            array('!= 3.0.0',        '!= 3.0.1'),
            array('!= 3.0.0',        'dev-foo || dev-bar'),
            array('!= 3.0.0',        '<dev-foo || >dev-bar'),
            array('>= 1.0.0',        '= 1.2.3'),
            array('< 2.0.0',         '= 1.2.3'),
            array('>3',              '^2 || ^3 || >4'),
            array('>=3',             '>3'),
            array('<=3',             '<3'),
            array('^2.1',            '^2.0, !=2.1.3'),
            array('<2.0',            '>=1.1'),
            array('!= dev-foo',      '!= dev-bar'),
            array('!= dev-foo',      '= dev-bar'),
            array('1.3.3',           '1.3.0 || 1.3.1 || 1.3.2'),
            array('1.3.1 || 1.3.2',  '1.3.1'),
            array('>1.6',            '>1.5, >1.4, !=1.7'),
            array('>1.6',            '>1.5, >1.7'),
            array('^1.0 || ^3.2',    '^1.2 || ^3.0'),
            array('^1.0 || ^3.2',    '^3.0'),
            array('^1.3 || ^3.2',    '>1.4'),
            array('^2.0 || dev-foo', '> 1.0 || dev-bar'),
        );
    }

    public function testMatchNoneIsNoSubsetNorSupersetExceptOfMatchAll()
    {
        $versionParser = new VersionParser;
        $matchNone = new MatchNoneConstraint;

        $notSubsets = array(
            '1.0.0',
            '^1.0',
            '>3',
            '<3',
            'dev-foo',
            '!= 1',
            '!= dev-foo',
            '<= dev-foo',
        );
        foreach ($notSubsets as $constraint) {
            $c = $versionParser->parseConstraints($constraint);
            $this->assertFalse(Intervals::isSubsetOf($c, $matchNone), $constraint.' ('.$c.') should not be seen as a subset of '.$matchNone);
            $this->assertFalse(Intervals::isSubsetOf($matchNone, $c), $matchNone.' should not be seen as a subset of '.$constraint.' ('.$c.')');
        }

        $empty = new MatchAllConstraint;
        $this->assertFalse(Intervals::isSubsetOf($empty, $matchNone), $empty.' should not be seen as a subset of '.$matchNone);
        $this->assertTrue(Intervals::isSubsetOf($matchNone, $empty), $matchNone.' should be seen as a subset of '.$empty);
    }
}
