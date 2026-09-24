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

    public function testCacheSeparatesOperatorsConstraintsAndVersions()
    {
        CompilingMatcher::clear();
        $constraint = new Constraint('>=', '2');

        $this->assertFalse(CompilingMatcher::match($constraint, Constraint::OP_EQ, '1'));
        $this->assertTrue(CompilingMatcher::match($constraint, Constraint::OP_GT, '1'));
        $this->assertTrue(CompilingMatcher::match(new Constraint('<', '2'), Constraint::OP_EQ, '1'));
        $this->assertTrue(CompilingMatcher::match($constraint, Constraint::OP_EQ, '3'));
        $this->assertFalse(CompilingMatcher::match($constraint, Constraint::OP_EQ, '1'));
        CompilingMatcher::clear();
    }

    public function testReusesCompiledCheckerAndClearsBothCaches()
    {
        CompilingMatcher::clear();
        $constraint = $this->getMockBuilder('Composer\\Semver\\Constraint\\Constraint')
            ->setConstructorArgs(array('>=', '1'))
            ->setMethods(array('compile'))
            ->getMock();
        $constraint->expects($this->exactly(2))->method('compile')->willReturn('$v === "2"');

        $this->assertFalse(CompilingMatcher::match($constraint, Constraint::OP_EQ, '1'));
        $this->assertFalse(CompilingMatcher::match($constraint, Constraint::OP_EQ, '1'));
        $this->assertTrue(CompilingMatcher::match($constraint, Constraint::OP_EQ, '2'));
        CompilingMatcher::clear();
        $this->assertFalse(CompilingMatcher::match($constraint, Constraint::OP_EQ, '1'));
        CompilingMatcher::clear();
    }
}
