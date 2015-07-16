<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Test\Semver;

use Composer\Semver\Comparator;

class ComparatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Comparator::compare
     * @dataProvider versionComparisons
     */
    public function testCompare($v1, $c, $v2, $expected)
    {
        $this->assertEquals($expected, Comparator::compare($v1, $c, $v2));
    }

    public function versionComparisons()
    {
        return array(
            array('1.25.0', '>', '1.26.0', false),
            array('1.25.0', '<=', '1.26.0', true),
            array('1.25.0', '!=', '1.26.0', true),
            array('1.25.0', '==', '1.25.0', true),
        );
    }

    /**
     * @covers Comparator:compare
     * @dataProvider badVersionComparisons
     */
    public function testBadCompare($v1, $o, $v2, $expected)
    {
        $this->setExpectedException($expected);
        Comparator::compare($v1, $o, $v2);
    }

    public function badVersionComparisons()
    {
        return array(
            array('1.26.0', 'invalid', '1.25.0', 'InvalidArgumentException'),
        );
    }
}
