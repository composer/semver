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

class ConstraintTest extends TestCase
{
    protected $constraint;
    protected $versionProvide;

    protected function setUp()
    {
        $this->constraint = new Constraint('==', '1');
        $this->versionProvide = new Constraint('==', 'dev-foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testVersionCompareInvalidArgumentException()
    {
        $result = $this->constraint->versionCompare('1.1', '1.2', null);
    }

    public function testGetPrettyString()
    {
        $expectedString = 'pretty-string';
        $this->constraint->setPrettyString($expectedString);
        $result = $this->constraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedVersion = '== 1';
        $this->constraint->setPrettyString(null);
        $result = $this->constraint->getPrettyString();

        $this->assertSame($expectedVersion, $result);
    }

    public static function successfulVersionMatches()
    {
        return array(
            //    require    provide
            array('==', '1', '==', '1'),
            array('>=', '1', '>=', '2'),
            array('>=', '2', '>=', '1'),
            array('>=', '2', '>', '1'),
            array('<=', '2', '>=', '1'),
            array('>=', '1', '<=', '2'),
            array('==', '2', '>=', '2'),
            array('!=', '1', '!=', '1'),
            array('!=', '1', '==', '2'),
            array('!=', '1', '<', '1'),
            array('!=', '1', '<=', '1'),
            array('!=', '1', '>', '1'),
            array('!=', '1', '>=', '1'),
            array('==', 'dev-foo-bar', '==', 'dev-foo-bar'),
            array('==', 'dev-events+issue-17', '==', 'dev-events+issue-17'),
            array('==', 'dev-foo-xyz', '==', 'dev-foo-xyz'),
            array('>=', 'dev-foo-bar', '>=', 'dev-foo-xyz'),
            array('<=', 'dev-foo-bar', '<', 'dev-foo-xyz'),
            array('!=', 'dev-foo-bar', '<', 'dev-foo-xyz'),
            array('>=', 'dev-foo-bar', '!=', 'dev-foo-bar'),
            array('!=', 'dev-foo-bar', '!=', 'dev-foo-xyz'),
        );
    }

    /**
     * @dataProvider successfulVersionMatches
     */
    public function testVersionMatchSucceeds($requireOperator, $requireVersion, $provideOperator, $provideVersion)
    {
        $versionRequire = new Constraint($requireOperator, $requireVersion);
        $versionProvide = new Constraint($provideOperator, $provideVersion);

        $this->assertTrue($versionRequire->matches($versionProvide));
    }

    public static function failingVersionMatches()
    {
        return array(
            //    require    provide
            array('==', '1', '==', '2'),
            array('>=', '2', '<=', '1'),
            array('>=', '2', '<', '2'),
            array('<=', '2', '>', '2'),
            array('>', '2', '<=', '2'),
            array('<=', '1', '>=', '2'),
            array('>=', '2', '<=', '1'),
            array('==', '2', '<', '2'),
            array('!=', '1', '==', '1'),
            array('==', '1', '!=', '1'),
            array('==', 'dev-foo-dist', '==', 'dev-foo-zist'),
            array('==', 'dev-foo-bist', '==', 'dev-foo-aist'),
            array('<=', 'dev-foo-bist', '>=', 'dev-foo-aist'),
            array('>=', 'dev-foo-bist', '<', 'dev-foo-aist'),
            array('<', '0.12', '==', 'dev-foo'), // branches are not comparable
            array('>', '0.12', '==', 'dev-foo'), // branches are not comparable
        );
    }

    /**
     * @dataProvider failingVersionMatches
     */
    public function testVersionMatchFails($requireOperator, $requireVersion, $provideOperator, $provideVersion)
    {
        $versionRequire = new Constraint($requireOperator, $requireVersion);
        $versionProvide = new Constraint($provideOperator, $provideVersion);

        $this->assertFalse($versionRequire->matches($versionProvide));
    }

    public function testInverseMatchingOtherConstraints()
    {
        $constraint = new Constraint('>', '1.0.0');

        $multiConstraint = $this
            ->getMockBuilder('Composer\Semver\Constraint\MultiConstraint')
            ->disableOriginalConstructor()
            ->setMethods(array('matches'))
            ->getMock()
        ;

        $emptyConstraint = $this
            ->getMockBuilder('Composer\Semver\Constraint\EmptyConstraint')
            ->setMethods(array('matches'))
            ->getMock()
        ;

        foreach (array($multiConstraint, $emptyConstraint) as $mock) {
            $mock
                ->expects($this->once())
                ->method('matches')
                ->with($constraint)
                ->willReturn(true)
            ;
        }

        $this->assertTrue($constraint->matches($multiConstraint));
        $this->assertTrue($constraint->matches($emptyConstraint));
    }

    public function testComparableBranches()
    {
        $versionRequire = new Constraint('>', '0.12');

        $this->assertFalse($versionRequire->matches($this->versionProvide));
        $this->assertFalse($versionRequire->matchSpecific($this->versionProvide, true));

        $versionRequire = new Constraint('<', '0.12');

        $this->assertFalse($versionRequire->matches($this->versionProvide));
        $this->assertTrue($versionRequire->matchSpecific($this->versionProvide, true));
    }

    /**
     * @dataProvider invalidOperators
     *
     * @param string $version
     * @param string $operator
     * @param bool   $expected
     */
    public function testInvalidOperators($version, $operator, $expected)
    {
        $this->setExpectedException($expected);

        new Constraint($operator, $version);
    }

    /**
     * @return array
     */
    public function invalidOperators()
    {
        return array(
            array('1.2.3', 'invalid', 'InvalidArgumentException'),
            array('1.2.3', '!', 'InvalidArgumentException'),
            array('1.2.3', 'equals', 'InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider bounds
     *
     * @param string $operator
     * @param string $normalizedVersion
     * @param array $expectedLower
     * @param array $expectedUpper
     */
    public function testBounds($operator, $normalizedVersion, array $expectedLower, array $expectedUpper)
    {
        $constraint = new Constraint($operator, $normalizedVersion);

        $this->assertSame($expectedLower, $constraint->getLowerBound());
        $this->assertSame($expectedUpper, $constraint->getUpperBound());
    }

    /**
     * @return array
     */
    public function bounds()
    {
        return array(
            'equal to 1.0.0.0' => array('==', '1.0.0.0', array('==', '1.0.0.0'), array('==', '1.0.0.0')),
            'equal to 1.0.0.0-rc3' => array('==', '1.0.0.0-rc3', array('==', '1.0.0.0.rc.3'), array('==', '1.0.0.0.rc.3')),
            'equal to dev-feature-branch' => array('>=', 'dev-feature-branch', array('>=', '0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),

            'lower than 0.0.4.0' => array('<', '0.0.4.0', array('>=', '0'), array('<', '0.0.4.0')),
            'lower than 1.0.0.0' => array('<', '1.0.0.0', array('>=', '0'), array('<', '1.0.0.0')),
            'lower than 2.0.0.0' => array('<', '2.0.0.0', array('>=', '0'), array('<', '2.0.0.0')),
            'lower than 3.0.3.0' => array('<', '3.0.3.0', array('>=', '0'), array('<', '3.0.3.0')),
            'lower than 3.0.3.0-rc3' => array('<', '3.0.3.0-rc3', array('>=', '0'), array('<', '3.0.3.0.rc.3')),
            'lower than dev-feature-branch' => array('<', 'dev-feature-branch', array('>=', '0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),

            'greater than 0.0.4.0' => array('>', '0.0.4.0', array('>', '0.0.4.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than 1.0.0.0' => array('>', '1.0.0.0', array('>', '1.0.0.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than 2.0.0.0' => array('>', '2.0.0.0', array('>', '2.0.0.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than 3.0.3.0' => array('>', '3.0.3.0', array('>', '3.0.3.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than 3.0.3.0-rc3' => array('>', '3.0.3.0-rc3', array('>', '3.0.3.0.rc.3'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than dev-feature-branch' => array('>', 'dev-feature-branch', array('>=', '0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),

            'lower than or equal to 0.0.4.0' => array('<=', '0.0.4.0', array('>=', '0'), array('<=', '0.0.4.0')),
            'lower than or equal to 1.0.0.0' => array('<=', '1.0.0.0', array('>=', '0'), array('<=', '1.0.0.0')),
            'lower than or equal to 2.0.0.0' => array('<=', '2.0.0.0', array('>=', '0'), array('<=', '2.0.0.0')),
            'lower than or equal to 3.0.3.0' => array('<=', '3.0.3.0', array('>=', '0'), array('<=', '3.0.3.0')),
            'lower than or equal to 3.0.3.0-rc3' => array('<=', '3.0.3.0-rc3', array('>=', '0'), array('<=', '3.0.3.0.rc.3')),
            'lower than or equal to dev-feature-branch' => array('<=', 'dev-feature-branch', array('>=', '0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),

            'greater than or equal to 0.0.4.0' => array('>=', '0.0.4.0', array('>=', '0.0.4.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than or equal to 1.0.0.0' => array('>=', '1.0.0.0', array('>=', '1.0.0.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than or equal to 2.0.0.0' => array('>=', '2.0.0.0', array('>=', '2.0.0.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than or equal to 3.0.3.0' => array('>=', '3.0.3.0', array('>=', '3.0.3.0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than or equal to 3.0.3.0-rc3' => array('>=', '3.0.3.0-rc3', array('>=', '3.0.3.0.rc.3'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
            'greater than or equal to dev-feature-branch' => array('>=', 'dev-feature-branch', array('>=', '0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),

            'not equal to 1.0.0.0' => array('<>', '1.0.0.0', array('>=', '0'), array('<', BoundsProvidingInterface::UPPER_INFINITY)),
        );
    }
}