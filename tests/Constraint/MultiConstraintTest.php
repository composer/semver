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
    /**
     * @var Constraint
     */
    protected $versionRequireStart;
    /**
     * @var Constraint
     */
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
     * @param bool  $constraints
     * @param Bound $expectedLower
     * @param Bound $expectedUpper
     */
    public function testBounds(array $constraints, $conjunctive, Bound $expectedLower, Bound $expectedUpper)
    {
        $constraint = new MultiConstraint($constraints, $conjunctive);

        $this->assertEquals($expectedLower, $constraint->getLowerBound(), 'Expected lower bound does not match');
        $this->assertEquals($expectedUpper, $constraint->getUpperBound(), 'Expected upper bound does not match');
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
                new Bound('1.0.0.0', true),
                new Bound('1.0.0.0', true),
            ),
            '">" should take precedence ">=" for lower bound when conjunctive' => array(
                array(
                    new Constraint('>', '1.0.0.0'),
                    new Constraint('>=', '1.0.0.0'),
                    new Constraint('>', '1.0.0.0'),
                ),
                true,
                new Bound('1.0.0.0', false),
                Bound::positiveInfinity(),
            ),
            '">=" should take precedence ">" for lower bound when disjunctive' => array(
                array(
                    new Constraint('>', '1.0.0.0'),
                    new Constraint('>=', '1.0.0.0'),
                    new Constraint('>', '1.0.0.0'),
                ),
                false,
                new Bound('1.0.0.0', true),
                Bound::positiveInfinity(),
            ),
            'Bounds should be limited when conjunctive' => array(
                array(
                    new Constraint('>=', '7.0.0.0'),
                    new Constraint('<', '8.0.0.0'),
                ),
                true,
                new Bound('7.0.0.0', true),
                new Bound('8.0.0.0', false),
            ),
            'Bounds should be unlimited when disjunctive' => array(
                array(
                    new Constraint('>=', '7.0.0.0'),
                    new Constraint('<', '8.0.0.0'),
                ),
                false,
                Bound::zero(),
                Bound::positiveInfinity(),
            ),
        );
    }

    /**
     * @dataProvider boundsIntegration
     *
     * @param string $constraints
     * @param Bound  $expectedLower
     * @param Bound  $expectedUpper
     */
    public function testBoundsIntegrationWithVersionParser($constraints, Bound $expectedLower, Bound $expectedUpper)
    {
        $versionParser = new VersionParser();
        $constraint = $versionParser->parseConstraints($constraints);

        $this->assertEquals($expectedLower, $constraint->getLowerBound(), 'Expected lower bound does not match');
        $this->assertEquals($expectedUpper, $constraint->getUpperBound(), 'Expected upper bound does not match');
    }

    /**
     * @return array
     */
    public function boundsIntegration()
    {
        return array(
            '^7.0' => array(
                '^7.0',
                new Bound('7.0.0.0-dev', true),
                new Bound('8.0.0.0-dev', false),
            ),
            '^7.2' => array(
                '^7.2',
                new Bound('7.2.0.0-dev', true),
                new Bound('8.0.0.0-dev', false),
            ),
            '7.4.*' => array(
                '7.4.*',
                new Bound('7.4.0.0-dev', true),
                new Bound('7.5.0.0-dev', false),
            ),
            '7.2.* || 7.4.*' => array(
                '7.2.* || 7.4.*',
                new Bound('7.2.0.0-dev', true),
                new Bound('7.5.0.0-dev', false),
            ),
        );
    }

    public function testMultipleMultiConstraintsMerging()
    {
        $versionParser = new VersionParser();
        $constraints = array(
            '^7.0',
            '^7.2',
            '7.4.*',
            '7.2.* || 7.4.*',
        );
        foreach ($constraints as &$constraint) {
            $constraint = $versionParser->parseConstraints($constraint);
        }

        $constraint = new MultiConstraint($constraints);

        $this->assertEquals(new Bound('7.4.0.0-dev', true), $constraint->getLowerBound(), 'Expected lower bound does not match');
        $this->assertEquals(new Bound('7.5.0.0-dev', false), $constraint->getUpperBound(), 'Expected upper bound does not match');
    }

    public function testMultipleMultiConstraintsMergingWithGaps()
    {
        $versionParser = new VersionParser();

        $constraint = new MultiConstraint(array(
            $versionParser->parseConstraints('^7.1.15 || ^7.2.3'),
            $versionParser->parseConstraints('^7.2.2'),
        ));

        $this->assertEquals(new Bound('7.2.2.0-dev', true), $constraint->getLowerBound(), 'Expected lower bound does not match');
        $this->assertEquals(new Bound('8.0.0.0-dev', false), $constraint->getUpperBound(), 'Expected upper bound does not match');
    }
}
