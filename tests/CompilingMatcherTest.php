<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\MultiConstraint;
use PHPUnit\Framework\TestCase;

class CompilingMatcherTest extends TestCase
{
    public function testMatch()
    {
        $this->assertTrue(CompilingMatcher::match(new Constraint('>=', '1'), Constraint::OP_EQ, '2'));
    }

    public function testCacheKey()
    {
        $this->assertFalse(CompilingMatcher::match(new Constraint('>=', '2.11'), Constraint::OP_EQ, '1.0'));
        $this->assertTrue(CompilingMatcher::match(new Constraint('>=', '2.1'), Constraint::OP_EQ, '11.0'));
    }
}
