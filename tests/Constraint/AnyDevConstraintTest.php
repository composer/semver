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
use Composer\Semver\CompilingMatcher;
use Composer\Semver\Intervals;

class AnyDevConstraintTest extends TestCase
{
    /**
     * @var AnyDevConstraint
     */
    protected $anyDevConstraint;

    protected function setUp()
    {
        $this->anyDevConstraint = new AnyDevConstraint();
    }

    /**
     * @dataProvider provideMatchingConstraints
     */
    public function testMatches($constraint)
    {
        $this->assertTrue($this->anyDevConstraint->matches($constraint));

        // check compiled constraint also matches
        if ($constraint instanceof Constraint) {
            $this->assertTrue(CompilingMatcher::match($this->anyDevConstraint, Constraint::getOperatorConstant($constraint->getOperator()), $constraint->getVersion()));
        }

        // check compacted constraint also matches
        $this->assertTrue($this->anyDevConstraint->matches(Intervals::compactConstraint($constraint)));
        $this->assertTrue(Intervals::compactConstraint($this->anyDevConstraint)->matches($constraint));
    }

    public function provideMatchingConstraints()
    {
        return array(
            array(new AnyDevConstraint()),
            array(new Constraint('!=', '1.1')),
            array(new Constraint('==', 'dev-foo')),
            array(new Constraint('!=', 'dev-foo')),
            array(new MultiConstraint(array(new Constraint('==', 'dev-foo'), new Constraint('==', 'dev-bar')), false)),
            array(new MultiConstraint(array(new Constraint('==', 'dev-foo'), new Constraint('!=', 'dev-bar')), true)),
        );
    }

    /**
     * @dataProvider provideNotMatchingConstraints
     */
    public function testNotMatches($constraint)
    {
        $this->assertFalse($this->anyDevConstraint->matches($constraint));

        // check compiled constraint also matches
        if ($constraint instanceof Constraint) {
            $this->assertFalse(CompilingMatcher::match($this->anyDevConstraint, Constraint::getOperatorConstant($constraint->getOperator()), $constraint->getVersion()));
        }

        // check compacted constraint also matches
        $this->assertFalse($this->anyDevConstraint->matches(Intervals::compactConstraint($constraint)));
        $this->assertFalse(Intervals::compactConstraint($this->anyDevConstraint)->matches($constraint));
    }

    public function provideNotMatchingConstraints()
    {
        return array(
            array(new Constraint('<=', 'dev-foo')),
            array(new Constraint('<', 'dev-foo')),
            array(new Constraint('>=', 'dev-foo')),
            array(new Constraint('>', 'dev-foo')),
            array(new Constraint('<=', '1.0.0')),
            array(new Constraint('<', '1.0.0')),
            array(new Constraint('>=', '1.0.0')),
            array(new Constraint('>', '1.0.0')),
        );
    }

    public function testGetPrettyString()
    {
        $expectedString = 'pretty-string';
        $this->anyDevConstraint->setPrettyString($expectedString);
        $result = $this->anyDevConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = 'dev*';
        $this->anyDevConstraint->setPrettyString(null);
        $result = $this->anyDevConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }
}
