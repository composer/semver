<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver\Test;

use Composer\Semver\Semver;

/**
 * @coversDefaultClass \Composer\Semver\Semver
 */
class SemverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::satisfies
     * @dataProvider satisfiesProvider
     *
     * @param string $version
     * @param string $constraint
     */
    public function testSatisfies($version, $constraint)
    {
        $this->assertEquals(true, Semver::satisfies($version, $constraint));
    }

    /**
     * @return array
     */
    public function satisfiesProvider()
    {
        return array(
            array('1.2.3', '1.0.0 - 2.0.0'),
//            array('1.2.3', '^1.2.3+build'),
//            array('1.3.0', '^1.2.3+build'),
//            array('1.2.3', '1.2.3-pre+asdf - 2.4.3-pre+asdf'),
//            array('1.2.3', '1.2.3pre+asdf - 2.4.3-pre+asdf'),
//            array('1.2.3', '1.2.3-pre+asdf - 2.4.3pre+asdf'),
//            array('1.2.3', '1.2.3pre+asdf - 2.4.3pre+asdf'),
//            array('1.2.3-pre.2', '1.2.3-pre+asdf - 2.4.3-pre+asdf'),
//            array('2.4.3-alpha', '1.2.3-pre+asdf - 2.4.3-pre+asdf'),
//            array('1.2.3', '1.2.3+asdf - 2.4.3+asdf'),
            array('1.0.0', '1.0.0'),
//            array('0.2.4', '>=*'),
//            array('', '1.0.0'),
            array('1.2.3', '*'),
            array('v1.2.3', '*'),
            array('1.0.0', '>=1.0.0'),
            array('1.0.1', '>=1.0.0'),
            array('1.1.0', '>=1.0.0'),
            array('1.0.1', '>1.0.0'),
            array('1.1.0', '>1.0.0'),
            array('2.0.0', '<=2.0.0'),
            array('1.9999.9999', '<=2.0.0'),
            array('0.2.9', '<=2.0.0'),
            array('1.9999.9999', '<2.0.0'),
            array('0.2.9', '<2.0.0'),
            array('1.0.0', '>= 1.0.0'),
            array('1.0.1', '>=  1.0.0'),
            array('1.1.0', '>=   1.0.0'),
            array('1.0.1', '> 1.0.0'),
            array('1.1.0', '>  1.0.0'),
            array('2.0.0', '<=   2.0.0'),
            array('1.9999.9999', '<= 2.0.0'),
            array('0.2.9', '<=  2.0.0'),
            array('1.9999.9999', '<    2.0.0'),
//            array('0.2.9', '<\t2.0.0'),
            array('v0.1.97', '>=0.1.97'),
            array('0.1.97', '>=0.1.97'),
            array('1.2.4', '0.1.20 || 1.2.4'),
            array('0.0.0', '>=0.2.3 || <0.0.1'),
            array('0.2.3', '>=0.2.3 || <0.0.1'),
            array('0.2.4', '>=0.2.3 || <0.0.1'),
//            array('1.3.4', '||'),
//            array('2.1.3', '2.x.x'),
            array('1.2.3', '1.2.x'),
            array('2.1.3', '1.2.x || 2.x'),
            array('1.2.3', '1.2.x || 2.x'),
            array('1.2.3', 'x'),
//            array('2.1.3', '2.*.*'),
            array('1.2.3', '1.2.*'),
            array('2.1.3', '1.2.* || 2.*'),
            array('1.2.3', '1.2.* || 2.*'),
            array('1.2.3', '*'),
//            array('2.1.2', '2'),
//            array('2.3.1', '2.3'),
            array('2.4.0', '~2.4'), // >=2.4.0 <2.5.0
            array('2.4.5', '~2.4'),
//            array('3.2.2', '~>3.2.1'), // >=3.2.1 <3.3.0,
            array('1.2.3', '~1'), // >=1.0.0 <2.0.0
//            array('1.2.3', '~>1'),
//            array('1.2.3', '~> 1'),
            array('1.0.2', '~1.0'), // >=1.0.0 <1.1.0,
//            array('1.0.2', '~ 1.0'),
//            array('1.0.12', '~ 1.0.3'),
            array('1.0.0', '>=1'),
            array('1.0.0', '>= 1'),
            array('1.1.1', '<1.2'),
            array('1.1.1', '< 1.2'),
//            array('0.5.5', '~v0.5.4-pre'),
//            array('0.5.4', '~v0.5.4-pre'),
//            array('0.7.2', '=0.7.x'),
//            array('0.7.2', '<=0.7.x'),
//            array('0.7.2', '>=0.7.x'),
//            array('0.6.2', '<=0.7.x'),
            array('1.2.3', '~1.2.1 >=1.2.3'),
            array('1.2.3', '~1.2.1 =1.2.3'),
            array('1.2.3', '~1.2.1 1.2.3'),
            array('1.2.3', '~1.2.1 >=1.2.3 1.2.3'),
            array('1.2.3', '~1.2.1 1.2.3 >=1.2.3'),
            array('1.2.3', '~1.2.1 1.2.3'),
            array('1.2.3', '>=1.2.1 1.2.3'),
            array('1.2.3', '1.2.3 >=1.2.1'),
            array('1.2.3', '>=1.2.3 >=1.2.1'),
            array('1.2.3', '>=1.2.1 >=1.2.3'),
            array('1.2.8', '>=1.2'),
            array('1.8.1', '^1.2.3'),
            array('0.1.2', '^0.1.2'),
            array('0.1.2', '^0.1'),
            array('1.4.2', '^1.2'),
            array('1.4.2', '^1.2 ^1'),
//            array('1.2.3-pre', '^1.2.3-alpha'),
//            array('1.2.0-pre', '^1.2.0-alpha'),
            array('0.0.1-beta', '^0.0.1-alpha'),
        );
    }
}
