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

    public function testMatches()
    {
        $this->assertTrue($this->anyDevConstraint->matches(new AnyDevConstraint()));
        $this->assertTrue($this->anyDevConstraint->matches(new Constraint('!=', '1.1')));
        $this->assertTrue($this->anyDevConstraint->matches(new Constraint('==', 'dev-foo')));
        $this->assertTrue($this->anyDevConstraint->matches(new MultiConstraint(array(new Constraint('==', 'dev-foo'), new Constraint('==', 'dev-bar')), true)));
        $this->assertTrue($this->anyDevConstraint->matches(new MultiConstraint(array(new Constraint('==', 'dev-foo'), new Constraint('==', 'dev-bar')), false)));
        $this->assertFalse($this->anyDevConstraint->matches(new Constraint('!=', 'dev-foo')));
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
