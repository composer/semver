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

/**
 * @coversDefaultClass \Composer\Semver\Semver
 */
class SemverTest extends TestCase
{
    /**
     * @covers ::satisfies
     * @dataProvider satisfiesProvider
     *
     * @param bool   $expected
     * @param string $version
     * @param string $constraint
     */
    public function testSatisfies($expected, $version, $constraint)
    {
        $this->assertEquals($expected, Semver::satisfies($version, $constraint));
    }

    /**
     * @covers ::satisfiedBy
     * @dataProvider satisfiedByProvider
     *
     * @param string $constraint
     * @param array<string> $versions
     * @param array<string> $expected
     */
    public function testSatisfiedBy($constraint, $versions, $expected)
    {
        $this->assertEquals($expected, Semver::satisfiedBy($versions, $constraint));
    }

    /**
     * @covers ::sort
     * @covers ::rsort
     * @covers ::usort
     * @dataProvider sortProvider
     *
     * @param array<string> $versions
     * @param array<string> $sorted
     * @param array<string> $rsorted
     */
    public function testSort(array $versions, array $sorted, array $rsorted)
    {
        $this->assertEquals($sorted, Semver::sort($versions));
        $this->assertEquals($rsorted, Semver::rsort($versions));
    }

    public function testUsortShouldInitialVersionParserClass()
    {
        $versions = array('1.0', '2.0', '2.1');
        $semver = new \ReflectionClass('\Composer\Semver\Semver');
        $versionParserProperty = $semver->getProperty('versionParser');
        $versionParserProperty->setAccessible(true);
        $versionParserProperty->setValue(null);

        $manipulateVersionStringMethod = $semver->getMethod('usort');
        $manipulateVersionStringMethod->setAccessible(true);
        $result = $manipulateVersionStringMethod->invoke(new Semver(), $versions, 1);

        $this->assertTrue(is_array($result));
        $this->assertCount(3, $versions);
    }

    /**
     * @return array<mixed>
     */
    public function sortProvider()
    {
        return array(
            array(
                array('1.0', '0.1', '0.1', '3.2.1', '2.4.0-alpha', '2.4.0'),
                array('0.1', '0.1', '1.0', '2.4.0-alpha', '2.4.0', '3.2.1'),
                array('3.2.1', '2.4.0', '2.4.0-alpha', '1.0', '0.1', '0.1'),
            ),
            array(
                array('dev-foo', 'dev-master', '1.0', '50.2'),
                array('dev-foo', '1.0', '50.2', 'dev-master'),
                array('dev-master', '50.2', '1.0', 'dev-foo'),
            ),
        );
    }

    /**
     * @return array<mixed>
     */
    public function satisfiesProvider()
    {
        $positive = array_map(function ($array) {
            array_unshift($array, true);

            return $array;
        }, $this->satisfiesProviderPositive());

        $negative = array_map(function ($array) {
            array_unshift($array, false);

            return $array;
        }, $this->satisfiesProviderNegative());

        return array_merge($positive, $negative);
    }

    /**
     * @return array<mixed>
     */
    public function satisfiesProviderPositive()
    {
        return array(
            array('1.2.3', '1.0.0 - 2.0.0'),
            array('1.2.3', '^1.2.3+build'),
            array('1.3.0', '^1.2.3+build'),
            array('2.4.3-alpha', '1.2.3+asdf - 2.4.3+asdf'),
            array('1.3.0-beta', '>1.2'),
            array('1.2.3-beta', '<=1.2.3'),
            array('1.2.3-beta', '^1.2.3'),
            array('1.2.3', '1.2.3+asdf - 2.4.3+asdf'),
            array('1.0.0', '1.0.0'),
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
            array('0.2.9', "<\t2.0.0"),
            array('v0.1.97', '>=0.1.97'),
            array('0.1.97', '>=0.1.97'),
            array('1.2.4', '0.1.20 || 1.2.4'),
            array('0.0.0', '>=0.2.3 || <0.0.1'),
            array('0.2.3', '>=0.2.3 || <0.0.1'),
            array('0.2.4', '>=0.2.3 || <0.0.1'),
            array('2.1.3', '2.x.x'),
            array('1.2.3', '1.2.x'),
            array('2.1.3', '1.2.x || 2.x'),
            array('1.2.3', '1.2.x || 2.x'),
            array('1.2.3', 'x'),
            array('2.1.3', '2.*.*'),
            array('1.2.3', '1.2.*'),
            array('2.1.3', '1.2.* || 2.*'),
            array('1.2.3', '1.2.* || 2.*'),
            array('1.2.3', '*'),
            array('2.9.0', '~2.4'), // >=2.4.0 <3.0.0
            array('2.4.5', '~2.4'),
            array('1.2.3', '~1'), // >=1.0.0 <2.0.0
            array('1.4.7', '~1.0'), // >=1.0.0 <2.0.0
            array('1.0.0', '>=1'),
            array('1.0.0', '>= 1'),
            array('1.2.8', '>1.2'), // >1.2.0
            array('1.1.1', '<1.2'), // <1.2.0
            array('1.1.1', '< 1.2'),
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
            array('0.0.1-beta', '^0.0.1-alpha'),
        );
    }

    /**
     * @return array<mixed>
     */
    public function satisfiesProviderNegative()
    {
        return array(
            array('2.2.3', '1.0.0 - 2.0.0'),
            array('2.0.0', '^1.2.3+build'),
            array('1.2.0', '^1.2.3+build'),
            array('1.0.0beta', '1'),
            array('1.0.0beta', '<1'),
            array('1.0.0beta', '< 1'),
            array('1.0.1', '1.0.0'),
            array('0.0.0', '>=1.0.0'),
            array('0.0.1', '>=1.0.0'),
            array('0.1.0', '>=1.0.0'),
            array('0.0.1', '>1.0.0'),
            array('0.1.0', '>1.0.0'),
            array('3.0.0', '<=2.0.0'),
            array('2.9999.9999', '<=2.0.0'),
            array('2.2.9', '<=2.0.0'),
            array('2.9999.9999', '<2.0.0'),
            array('2.2.9', '<2.0.0'),
            array('v0.1.93', '>=0.1.97'),
            array('0.1.93', '>=0.1.97'),
            array('1.2.3', '0.1.20 || 1.2.4'),
            array('0.0.3', '>=0.2.3 || <0.0.1'),
            array('0.2.2', '>=0.2.3 || <0.0.1'),
            array('1.1.3', '2.x.x'),
            array('3.1.3', '2.x.x'),
            array('1.3.3', '1.2.x'),
            array('3.1.3', '1.2.x || 2.x'),
            array('1.1.3', '1.2.x || 2.x'),
            array('1.1.3', '2.*.*'),
            array('3.1.3', '2.*.*'),
            array('1.3.3', '1.2.*'),
            array('3.1.3', '1.2.* || 2.*'),
            array('1.1.3', '1.2.* || 2.*'),
            array('1.1.2', '2'),
            array('2.4.1', '2.3'),
            array('3.0.0', '~2.4'), // >=2.4.0 <3.0.0
            array('2.3.9', '~2.4'),
            array('0.2.3', '~1'), // >=1.0.0 <2.0.0
            array('1.0.0', '<1'),
            array('1.1.1', '>=1.2'),
            array('2.0.0beta', '1'),
            array('0.5.4-alpha', '~v0.5.4-beta'),
            array('1.2.3-beta', '<1.2.3'),
            array('2.0.0-alpha', '^1.2.3'),
            array('1.2.2', '^1.2.3'),
            array('1.1.9', '^1.2'),
        );
    }

    /**
     * @return array<mixed>
     */
    public function satisfiedByProvider()
    {
        return array(
            array(
                '~1.0',
                array('1.0', '1.2', '1.9999.9999', '2.0', '2.1', '0.9999.9999'),
                array('1.0', '1.2', '1.9999.9999'),
            ),
            array(
                '>1.0 <3.0 || >=4.0',
                array('1.0', '1.1', '2.9999.9999', '3.0', '3.1', '3.9999.9999', '4.0', '4.1'),
                array('1.1', '2.9999.9999', '4.0', '4.1'),
            ),
            array(
                '^0.2.0',
                array('0.1.1', '0.1.9999', '0.2.0', '0.2.1', '0.3.0'),
                array('0.2.0', '0.2.1'),
            ),
        );
    }
}
