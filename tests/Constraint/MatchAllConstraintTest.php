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

class MatchAllConstraintTest extends TestCase
{
    /**
     * @var Constraint
     */
    protected $versionProvide;
    /**
     * @var MatchAllConstraint
     */
    protected $MatchAllConstraint;

    protected function setUp()
    {
        $this->versionProvide = new Constraint('==', '1.1');
        $this->MatchAllConstraint = new MatchAllConstraint();
    }

    public function testMatches()
    {
        $result = $this->MatchAllConstraint->matches($this->versionProvide);

        $this->assertTrue($result);
    }

    public function testGetPrettyString()
    {
        $expectedString = 'pretty-string';
        $this->MatchAllConstraint->setPrettyString($expectedString);
        $result = $this->MatchAllConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = '*';
        $this->MatchAllConstraint->setPrettyString(null);
        $result = $this->MatchAllConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }
}
