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

use Composer\Semver\VersionParser;
use PHPUnit\Framework\TestCase;

class MultiConstraintTest extends TestCase
{
    protected $versionRequireStart;
    protected $versionRequireEnd;

    protected function setUp()
    {
        $this->versionRequireStart = new Constraint('>', '1.0');
        $this->versionRequireEnd = new Constraint('<', '1.2');
    }

    public function testIsConjunctive()
    {
        $multiConstraint = new MultiConstraint(array(), true);
        $this->assertTrue($multiConstraint->isConjunctive());
        $this->assertFalse($multiConstraint->isDisjunctive());
    }

    public function testIsDisjunctive()
    {
        $multiConstraint = new MultiConstraint(array(), false);
        $this->assertFalse($multiConstraint->isConjunctive());
        $this->assertTrue($multiConstraint->isDisjunctive());
    }

    public function testMultiVersionMatchSucceeds()
    {
        $versionProvide = new Constraint('==', '1.1');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));

        $this->assertTrue($multiRequire->matches($versionProvide));
    }

    public function testMultiVersionProvidedMatchSucceeds()
    {
        $versionProvideStart = new Constraint('>=', '1.1');
        $versionProvideEnd = new Constraint('<', '2.0');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd));

        $this->assertTrue($multiRequire->matches($multiProvide));
    }

    public function testMultiVersionMatchSucceedsInsideForeachLoop()
    {
        $versionProvideStart = new Constraint('>', '1.0');
        $versionProvideEnd = new Constraint('<', '1.2');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd), false);
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd), false);

        $this->assertTrue($multiRequire->matches($multiProvide));
    }

    public function testMultiVersionMatchFails()
    {
        $versionProvide = new Constraint('==', '1.2');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));

        $this->assertFalse($multiRequire->matches($versionProvide));
    }

    public function testGetPrettyString()
    {
        $multiConstraint = new MultiConstraint(array());
        $expectedString = 'pretty-string';
        $multiConstraint->setPrettyString($expectedString);
        $result = $multiConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = '[]';
        $multiConstraint->setPrettyString(null);
        $result = $multiConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }

    /**
     * @dataProvider bounds
     *
     * @param array $constraints
     * @param bool $constraints
     * @param array $expectedLower
     * @param array $expectedUpper
     */
    public function testBounds(array $constraints, $conjunctive, array $expectedLower, array $expectedUpper)
    {
        $constraint = new MultiConstraint($constraints, $conjunctive);

        $this->assertSame($expectedLower, $constraint->getLowerBound(), 'Expected lower bound does not match');
        $this->assertSame($expectedUpper, $constraint->getUpperBound(), 'Expected upper bound does not match');
    }

    /**
     * @return array
     */
    public function bounds()
    {
        return array(
            'all equal' => array(
                array(
                    new Constraint('==', '1.0.0.0'),
                    new Constraint('==', '1.0.0.0'),
                ),
                true,
                array('==', '1.0.0.0'),
                array('==', '1.0.0.0')
            ),
            '">" should take precedence ">=" for lower bound when conjunctive' => array(
                array(
                    new Constraint('>', '1.0.0.0'),
                    new Constraint('>=', '1.0.0.0'),
                    new Constraint('>', '1.0.0.0'),
                ),
                true,
                array('>', '1.0.0.0'),
                array('<', BoundsProvidingInterface::UPPER_INFINITY)
            ),
            '">=" should take precedence ">" for lower bound when disjunctive' => array(
                array(
                    new Constraint('>', '1.0.0.0'),
                    new Constraint('>=', '1.0.0.0'),
                    new Constraint('>', '1.0.0.0'),
                ),
                false,
                array('>=', '1.0.0.0'),
                array('<', BoundsProvidingInterface::UPPER_INFINITY)
            ),
            'Bounds should be limited when conjunctive' => array(
                array(
                    new Constraint('>=', '7.0.0.0'),
                    new Constraint('<', '8.0.0.0'),
                ),
                true,
                array('>=', '7.0.0.0'),
                array('<', '8.0.0.0')
            ),
            'Bounds should be unlimited when disjunctive' => array(
                array(
                    new Constraint('>=', '7.0.0.0'),
                    new Constraint('<', '8.0.0.0'),
                ),
                false,
                array('>=', '0'),
                array('<', BoundsProvidingInterface::UPPER_INFINITY)
            ),
        );
    }

    /**
     * @dataProvider boundsIntegration
     *
     * @param string $constraints
     * @param array $expectedLower
     * @param array $expectedUpper
     */
    public function testBoundsIntegrationWithVersionParser($constraints, array $expectedLower, array $expectedUpper)
    {
        $versionParser = new VersionParser();
        $constraint = $versionParser->parseConstraints($constraints);

        $this->assertSame($expectedLower, $constraint->getLowerBound(), 'Expected lower bound does not match');
        $this->assertSame($expectedUpper, $constraint->getUpperBound(), 'Expected upper bound does not match');
    }


    /**
     * @return array
     */
    public function boundsIntegration()
    {
        return array(
            '^7.0' => array(
                '^7.0',
                array('>=', '7.0.0.0.dev'),
                array('<', '8.0.0.0.dev')
            ),
            '^7.2' => array(
                '^7.2',
                array('>=', '7.2.0.0.dev'),
                array('<', '8.0.0.0.dev')
            ),
            '7.4.*' => array(
                '7.4.*',
                array('>=', '7.4.0.0.dev'),
                array('<', '7.5.0.0.dev')
            ),
            '7.2.* || 7.4.*' => array(
                '7.2.* || 7.4.*',
                array('>=', '7.2.0.0.dev'),
                array('<', '7.5.0.0.dev')
            ),
        );
    }
}
