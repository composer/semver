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

class MatchNoneConstraintTest extends TestCase
{
    /**
     * @var MatchNoneConstraint
     */
    protected $matchNoneConstraint;

    protected function setUp()
    {
        $this->matchNoneConstraint = new MatchNoneConstraint();
    }

    public function testMatches()
    {
        $this->assertFalse($this->matchNoneConstraint->matches(new Constraint('==', '1.1')));
        $this->assertFalse($this->matchNoneConstraint->matches(new Constraint('!=', '1.1')));
        $this->assertFalse($this->matchNoneConstraint->matches(new Constraint('==', 'dev-foo')));
        $this->assertFalse($this->matchNoneConstraint->matches(new Constraint('!=', 'dev-foo')));
    }

    public function testGetPrettyString()
    {
        $expectedString = 'pretty-string';
        $this->matchNoneConstraint->setPrettyString($expectedString);
        $result = $this->matchNoneConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = '[]';
        $this->matchNoneConstraint->setPrettyString(null);
        $result = $this->matchNoneConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }
}
