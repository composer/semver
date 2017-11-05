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
        $versionProvide = new Constraint('==', 'dev-foo');

        $this->assertFalse($versionRequire->matches($versionProvide));
        $this->assertFalse($versionRequire->matchSpecific($versionProvide, true));

        $versionRequire = new Constraint('<', '0.12');
        $versionProvide = new Constraint('==', 'dev-foo');

        $this->assertFalse($versionRequire->matches($versionProvide));
        $this->assertTrue($versionRequire->matchSpecific($versionProvide, true));
    }

    /**
     * @dataProvider invalidOperators
     *
     * @param string $version
     * @param string $operator
     * @param bool $expected
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
}
