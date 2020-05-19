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
    protected $matchAllConstraint;

    protected function setUp()
    {
        $this->versionProvide = new Constraint('==', '1.1');
        $this->matchAllConstraint = new MatchAllConstraint();
    }

    public function testMatches()
    {
        $result = $this->matchAllConstraint->matches($this->versionProvide);

        $this->assertTrue($result);
    }

    public function testGetPrettyString()
    {
        $expectedString = 'pretty-string';
        $this->matchAllConstraint->setPrettyString($expectedString);
        $result = $this->matchAllConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = '*';
        $this->matchAllConstraint->setPrettyString(null);
        $result = $this->matchAllConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }
}
