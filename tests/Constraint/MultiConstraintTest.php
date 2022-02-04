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
use Composer\Semver\Intervals;

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
        $multiConstraint = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd), true);
        $this->assertTrue($multiConstraint->isConjunctive());
        $this->assertFalse($multiConstraint->isDisjunctive());
    }

    public function testIsDisjunctive()
    {
        $multiConstraint = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd), false);
        $this->assertFalse($multiConstraint->isConjunctive());
        $this->assertTrue($multiConstraint->isDisjunctive());
    }

    public function testMultiVersionMatchSucceeds()
    {
        $versionProvide = new Constraint('==', '1.1');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));

        $this->assertTrue($multiRequire->matches($versionProvide));
        $this->assertTrue($versionProvide->matches($multiRequire));
        $this->assertTrue($this->matchCompiled($multiRequire, '==', '1.1'));
        $this->assertTrue(Intervals::haveIntersections($multiRequire, $versionProvide));
        $this->assertTrue(Intervals::compactConstraint($multiRequire)->matches(Intervals::compactConstraint($versionProvide)));
        $this->assertTrue(Intervals::compactConstraint($versionProvide)->matches(Intervals::compactConstraint($multiRequire)));
    }

    public function testMultiVersionProvidedMatchSucceeds()
    {
        $versionProvideStart = new Constraint('>=', '1.1');
        $versionProvideEnd = new Constraint('<', '2.0');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd));

        $this->assertTrue($multiRequire->matches($multiProvide));
        $this->assertTrue($multiProvide->matches($multiRequire));
        $this->assertTrue(Intervals::haveIntersections($multiRequire, $multiProvide));
        $this->assertTrue(Intervals::compactConstraint($multiRequire)->matches(Intervals::compactConstraint($multiProvide)));
        $this->assertTrue(Intervals::compactConstraint($multiProvide)->matches(Intervals::compactConstraint($multiRequire)));
    }

    public function testMultiVersionMatchSucceedsInsideForeachLoop()
    {
        $versionProvideStart = new Constraint('>', '1.0');
        $versionProvideEnd = new Constraint('<', '1.2');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd), false);
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd), false);

        $this->assertTrue($multiRequire->matches($multiProvide));
        $this->assertTrue($multiProvide->matches($multiRequire));
        $this->assertTrue(Intervals::haveIntersections($multiRequire, $multiProvide));
        $this->assertTrue(Intervals::compactConstraint($multiRequire)->matches(Intervals::compactConstraint($multiProvide)));
        $this->assertTrue(Intervals::compactConstraint($multiProvide)->matches(Intervals::compactConstraint($multiRequire)));
    }

    public function testConjunctiveMatchesDisjunctiveFalse()
    {
        $versionProvideStart = new Constraint('<', '1.0');
        $versionProvideEnd = new Constraint('>', '2.0');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd), true);
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd), false);

        $this->assertFalse($multiRequire->matches($multiProvide));
        $this->assertFalse($multiProvide->matches($multiRequire));
        $this->assertFalse(Intervals::haveIntersections($multiRequire, $multiProvide));
        $this->assertFalse(Intervals::compactConstraint($multiRequire)->matches(Intervals::compactConstraint($multiProvide)));
        $this->assertFalse(Intervals::compactConstraint($multiProvide)->matches(Intervals::compactConstraint($multiRequire)));
    }

    public function testMultiVersionMatchFails()
    {
        $versionProvide = new Constraint('==', '1.2');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));

        $this->assertFalse($multiRequire->matches($versionProvide));
        $this->assertFalse($versionProvide->matches($multiRequire));
        $this->assertFalse($this->matchCompiled($multiRequire, '==', '1.2'));
        $this->assertFalse(Intervals::haveIntersections($multiRequire, $versionProvide));
        $this->assertFalse(Intervals::compactConstraint($multiRequire)->matches(Intervals::compactConstraint($versionProvide)));
        $this->assertFalse(Intervals::compactConstraint($versionProvide)->matches(Intervals::compactConstraint($multiRequire)));
    }

    public function testGetPrettyString()
    {
        $multiConstraint = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));
        $expectedString = 'pretty-string';
        $multiConstraint->setPrettyString($expectedString);
        $result = $multiConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = '[> 1.0 < 1.2]';
        $multiConstraint->setPrettyString(null);
        $result = $multiConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }

    /**
     * @dataProvider bounds
     *
     * @param array<ConstraintInterface> $constraints
     * @param bool  $conjunctive
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
     * @return array<mixed>
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
     * @return array<mixed>
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
        $strConstraints = array(
            '^7.0',
            '^7.2',
            '7.4.*',
            '7.2.* || 7.4.*',
        );
        $constraints = array();
        foreach ($strConstraints as $str) {
            $constraints[] = $versionParser->parseConstraints($str);
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

    public function testCreatesMatchAllConstraintIfNoneGiven()
    {
        $this->assertInstanceOf('Composer\Semver\Constraint\MatchAllConstraint', MultiConstraint::create(array()));
    }

    public function testMatchAllConstraintWithinConjunctiveMultiConstraint()
    {
        $this->assertSame('[>= 2.5.0.0-dev <= 3.0.0.0-dev *]', (string) MultiConstraint::create(
            array(new Constraint('>=', '2.5.0.0-dev'), new Constraint('<=', '3.0.0.0-dev'), new MatchAllConstraint())
        ));
    }

    public function testMatchAllConstraintWithinDisjunctiveMultiConstraint()
    {
        $this->assertSame('[>= 2.5.0.0-dev || *]', (string) MultiConstraint::create(
            array(new Constraint('>=', '2.5.0.0-dev'), new MatchAllConstraint()), false
        ));
    }

    /**
     * @dataProvider multiConstraintOptimizations
     *
     * @param string $constraints
     */
    public function testMultiConstraintOptimizations($constraints, ConstraintInterface $expectedConstraint)
    {
        // We're using the version parser here because that uses MultiConstraint::create() internally and
        // thus tests our optimizations. It's just easier to write complex multi constraint instances
        // using the string notation.
        $parser = new VersionParser();
        $this->assertSame((string) $expectedConstraint, (string) $parser->parseConstraints($constraints));
    }

    /**
     * @return array<mixed>
     */
    public function multiConstraintOptimizations()
    {
        return array(
            'Test collapses contiguous' => array(
                '^2.5 || ^3.0',
                new MultiConstraint(
                    array(
                        new Constraint('>=', '2.5.0.0-dev'),
                        new Constraint('<', '4.0.0.0-dev'),
                    ),
                    true // conjunctive
                ),
            ),
            'Test collapses multiple contiguous' => array(
                '^2.5 || ^3.0 || ^4.0',
                new MultiConstraint(
                    array(
                        new Constraint('>=', '2.5.0.0-dev'),
                        new Constraint('<', '5.0.0.0-dev'),
                    ),
                    true // conjunctive
                ),
            ),
            'Test does not collapse when one side is more complex' => array(
                '~2.5.9 || ~2.6, >=2.6.2',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '2.5.9.0-dev'),
                                new Constraint('<', '2.6.0.0-dev'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '2.6.0.0-dev'),
                                new Constraint('<', '3.0.0.0-dev'),
                                new Constraint('>=', '2.6.2.0-dev'),
                            ),
                            true // conjunctive
                        ),
                    ),
                    false
                )
            ),
            'Test does not collapse multiple contiguous with other constraint but collapses the end' => array(
                '^1.0 || ^2.0 !=2.0.1 || ^3.0 || ^4.0',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '1.0.0.0-dev'),
                                new Constraint('<', '2.0.0.0-dev'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '2.0.0.0-dev'),
                                new Constraint('<', '3.0.0.0-dev'),
                                new Constraint('!=', '2.0.1.0'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '3.0.0.0-dev'),
                                new Constraint('<', '5.0.0.0-dev'),
                            ),
                            true // conjunctive
                        ),
                    ),
                    false
                )
            ),
            'Test does not collapse multiple contiguous with multiple other constraint' => array(
                '^1.0 != 1.0.1 || ^2.0 !=2.0.1 || ^3.0 || ^4.0 != 4.0.1',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '1.0.0.0-dev'),
                                new Constraint('<', '2.0.0.0-dev'),
                                new Constraint('!=', '1.0.1.0'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '2.0.0.0-dev'),
                                new Constraint('<', '3.0.0.0-dev'),
                                new Constraint('!=', '2.0.1.0'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '3.0.0.0-dev'),
                                new Constraint('<', '4.0.0.0-dev'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '4.0.0.0-dev'),
                                new Constraint('<', '5.0.0.0-dev'),
                                new Constraint('!=', '4.0.1.0'),
                            ),
                            true // conjunctive
                        ),
                    ),
                    false
                )
            ),
            'Test does not collapse if contiguous range and other constraints also apply' => array(
                '~0.1 || ~1.0 !=1.0.1',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '0.1.0.0-dev'),
                                new Constraint('<', '1.0.0.0-dev'),
                            ),
                            true // conjunctive
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '1.0.0.0-dev'),
                                new Constraint('<', '2.0.0.0-dev'),
                                new Constraint('!=', '1.0.1.0'),
                            ),
                            true // conjunctive
                        ),
                    ),
                    false
                )
            ),
            'Parse caret constraints must not collapse if non contiguous range' => array(
                '^0.2 || ^1.0',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '0.2.0.0-dev'),
                                new Constraint('<', '0.3.0.0-dev'),
                            )
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '1.0.0.0-dev'),
                                new Constraint('<', '2.0.0.0-dev'),
                            )
                        ),
                    ),
                    false // disjunctive
                ),
            ),
            'Must not collapse if not contiguous range but collapse following constraints' => array(
                '^0.1 || ^1.0 || ^2.0',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '0.1.0.0-dev'),
                                new Constraint('<', '0.2.0.0-dev'),
                            )
                        ),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '1.0.0.0-dev'),
                                new Constraint('<', '3.0.0.0-dev'),
                            )
                        ),
                    ),
                    false // disjunctive
                ),
            ),
            'Must not collapse other constraint not in range' => array(
                '^1.0 || 2.1 || ^3.0',
                new MultiConstraint(
                    array(
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '1.0.0.0-dev'),
                                new Constraint('<', '2.0.0.0-dev'),
                            )
                        ),
                        new Constraint('=', '2.1.0.0'),
                        new MultiConstraint(
                            array(
                                new Constraint('>=', '3.0.0.0-dev'),
                                new Constraint('<', '4.0.0.0-dev'),
                            )
                        ),
                    ),
                    false // disjunctive
                ),
            ),
        );
    }

    public function testMultiConstraintNotconjunctiveFillWithFalse()
    {
        $versionProvide = new Constraint('==', '1.1');
        $multiRequire = new MultiConstraint(array(
            new Constraint('>', 'dev-foo'), // always false
            new Constraint('>', 'dev-bar'), // always false
        ), false);

        $this->assertFalse($multiRequire->matches($versionProvide));
        $this->assertFalse($versionProvide->matches($multiRequire));
        $this->assertFalse($this->matchCompiled($multiRequire, '==', '1.1'));
        $this->assertFalse(Intervals::haveIntersections($multiRequire, $versionProvide));
    }

    public function testMultiConstraintConjunctiveFillWithTrue()
    {
        $versionProvide = new Constraint('!=', '1.1');
        $multiRequire = new MultiConstraint(array(
            new Constraint('!=', 'dev-foo'), // always true
            new Constraint('!=', 'dev-bar'), // always true
        ), true);

        $this->assertTrue($multiRequire->matches($versionProvide));
        $this->assertTrue($versionProvide->matches($multiRequire));
        $this->assertTrue($this->matchCompiled($multiRequire, '!=', '1.1'));
        $this->assertTrue(Intervals::haveIntersections($multiRequire, $versionProvide));
    }

    /**
     * @param Constraint::STR_OP_* $operator
     * @param string $version
     * @return bool
     */
    private function matchCompiled(ConstraintInterface $constraint, $operator, $version)
    {
        $map = array(
            '=' => Constraint::OP_EQ,
            '==' => Constraint::OP_EQ,
            '<' => Constraint::OP_LT,
            '<=' => Constraint::OP_LE,
            '>' => Constraint::OP_GT,
            '>=' => Constraint::OP_GE,
            '<>' => Constraint::OP_NE,
            '!=' => Constraint::OP_NE,
        );

        $code = $constraint->compile($map[$operator]);
        $v = $version;
        $b = 'dev-' === substr($v, 0, 4);

        return eval("return $code;");
    }
}
