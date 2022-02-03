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
use Composer\Semver\Constraint\Constraint;

/**
 * @coversDefaultClass \Composer\Semver\Comparator
 */
class ComparatorTest extends TestCase
{
    /**
     * @covers ::greaterThan
     * @dataProvider greaterThanProvider
     *
     * @uses \Composer\Semver\Comparator::compare
     *
     * @param string $version1
     * @param string $version2
     * @param bool   $expected
     */
    public function testGreaterThan($version1, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::greaterThan($version1, $version2));
    }

    /**
     * @covers ::greaterThanOrEqualTo
     * @dataProvider greaterThanOrEqualToProvider
     *
     * @uses \Composer\Semver\Comparator::compare
     *
     * @param string $version1
     * @param string $version2
     * @param bool   $expected
     */
    public function testGreaterThanOrEqualTo($version1, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::greaterThanOrEqualTo($version1, $version2));
    }

    /**
     * @covers ::lessThan
     * @dataProvider lessThanProvider
     *
     * @uses \Composer\Semver\Comparator::compare
     *
     * @param string $version1
     * @param string $version2
     * @param bool   $expected
     */
    public function testLessThan($version1, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::lessThan($version1, $version2));
    }

    /**
     * @covers ::lessThanOrEqualTo
     * @dataProvider lessThanOrEqualToProvider
     *
     * @uses \Composer\Semver\Comparator::compare
     *
     * @param string $version1
     * @param string $version2
     * @param bool   $expected
     */
    public function testLessThanOrEqualTo($version1, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::lessThanOrEqualTo($version1, $version2));
    }

    /**
     * @covers ::equalTo
     * @dataProvider equalToProvider
     *
     * @uses \Composer\Semver\Comparator::compare
     *
     * @param string $version1
     * @param string $version2
     * @param bool   $expected
     */
    public function testEqualTo($version1, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::equalTo($version1, $version2));
    }

    /**
     * @covers ::notEqualTo
     * @dataProvider notEqualToProvider
     *
     * @uses \Composer\Semver\Comparator::compare
     *
     * @param string $version1
     * @param string $version2
     * @param bool   $expected
     */
    public function testNotEqualTo($version1, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::notEqualTo($version1, $version2));
    }

    /**
     * @covers ::compare
     * @dataProvider compareProvider
     *
     * @param string $version1
     * @param string $operator
     * @param string $version2
     * @param bool   $expected
     *
     * @phpstan-param Constraint::STR_OP_* $operator
     */
    public function testCompare($version1, $operator, $version2, $expected)
    {
        $this->assertEquals($expected, Comparator::compare($version1, $operator, $version2));
    }

    /**
     * @return array<mixed>
     */
    public function greaterThanProvider()
    {
        return array(
            array('1.25.0', '1.24.0', true),
            array('1.25.0', '1.25.0', false),
            array('1.25.0', '1.26.0', false),
            array('1.26.0', 'dev-foo', true),
            array('dev-foo', 'dev-master', false),
            array('dev-foo', 'dev-bar', false),
        );
    }

    /**
     * @return array<mixed>
     */
    public function greaterThanOrEqualToProvider()
    {
        return array(
            array('1.25.0', '1.24.0', true),
            array('1.25.0', '1.25.0', true),
            array('1.25.0', '1.26.0', false),
        );
    }

    /**
     * @return array<mixed>
     */
    public function lessThanProvider()
    {
        return array(
            array('1.25.0', '1.24.0', false),
            array('1.25.0', '1.25.0', false),
            array('1.25.0', '1.26.0', true),
            array('1.0.0', '1.2-dev', true),
            array('dev-foo', '1.26.0', true),
            array('dev-foo', 'dev-master', false),
            array('dev-foo', 'dev-bar', false),
        );
    }

    /**
     * @return array<mixed>
     */
    public function lessThanOrEqualToProvider()
    {
        return array(
            array('1.25.0', '1.24.0', false),
            array('1.25.0', '1.25.0', true),
            array('1.25.0', '1.26.0', true),
        );
    }

    /**
     * @return array<mixed>
     */
    public function equalToProvider()
    {
        return array(
            array('1.25.0', '1.24.0', false),
            array('1.25.0', '1.25.0', true),
            array('1.25.0', '1.26.0', false),
            array('dev-foo', '1.26.0', false),
            array('dev-foo', 'dev-master', false),
            array('dev-foo', 'dev-bar', false),
        );
    }

    /**
     * @return array<mixed>
     */
    public function notEqualToProvider()
    {
        return array(
            array('1.25.0', '1.24.0', true),
            array('1.25.0', '1.25.0', false),
            array('1.25.0', '1.26.0', true),
        );
    }

    /**
     * @return array<mixed>
     */
    public function compareProvider()
    {
        return array(
            array('1.25.0', '>', '1.24.0', true),
            array('1.25.0', '>', '1.25.0', false),
            array('1.25.0', '>', '1.26.0', false),

            array('1.25.0', '>=', '1.24.0', true),
            array('1.25.0', '>=', '1.25.0', true),
            array('1.25.0', '>=', '1.26.0', false),

            array('1.25.0', '<', '1.24.0', false),
            array('1.25.0', '<', '1.25.0', false),
            array('1.25.0', '<', '1.26.0', true),
            array('1.25.0-beta2.1', '<', '1.25.0-b.3', true),
            array('1.25.0-b2.1', '<', '1.25.0beta.3', true),
            array('1.25.0-b-2.1', '<', '1.25.0-rc', true),

            array('1.25.0', '<=', '1.24.0', false),
            array('1.25.0', '<=', '1.25.0', true),
            array('1.25.0', '<=', '1.26.0', true),

            array('1.25.0', '==', '1.24.0', false),
            array('1.25.0', '==', '1.25.0', true),
            array('1.25.0', '==', '1.26.0', false),
            array('1.25.0-beta2.1', '==', '1.25.0-b.2.1', true),
            array('1.25.0beta2.1', '==', '1.25.0-b2.1', true),

            array('1.25.0', '=', '1.24.0', false),
            array('1.25.0', '=', '1.25.0', true),
            array('1.25.0', '=', '1.26.0', false),

            array('1.25.0', '!=', '1.24.0', true),
            array('1.25.0', '!=', '1.25.0', false),
            array('1.25.0', '!=', '1.26.0', true),

            array('1.25.0', '<>', '1.24.0', true),
            array('1.25.0', '<>', '1.25.0', false),
            array('1.25.0', '<>', '1.26.0', true),
        );
    }
}
