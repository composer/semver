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

class CompiledMatcherTest extends TestCase
{
    public function testMatch()
    {
        $this->assertTrue(CompiledMatcher::match(new Constraint('>=', '1'), '==', '2'));
    }

    public function testMatchHandleNoCompilable()
    {
        $self = $this;
        $constraint = $this->getMockBuilder('Composer\Semver\Constraint\ConstraintInterface')->getMock();
        $constraint->expects($this->once())
            ->method('matches')
            ->with($this->callback(function($provideConstraint) use ($self) {
                $self->assertInstanceOf('Composer\Semver\Constraint\Constraint', $provideConstraint);
                $self->assertSame('== 1', (string) $provideConstraint);

                return true;
            }))
            ->willReturn(true);

        // @phpstan-ignore-next-line
        $this->assertTrue(CompiledMatcher::match($constraint, '==', '1'));
    }

    public function testMatchHandleNoCompilableInMulti()
    {
        $self = $this;
        $constraint = $this->getMockBuilder('Composer\Semver\Constraint\ConstraintInterface')->getMock();
        $constraint->expects($this->once())
            ->method('matches')
            ->with($this->callback(function($provideConstraint) use ($self) {
                $self->assertInstanceOf('Composer\Semver\Constraint\Constraint', $provideConstraint);
                $self->assertSame('== 1', (string) $provideConstraint);

                return true;
            }))
            ->willReturn(true);

        // @phpstan-ignore-next-line
        $multi = new MultiConstraint(array(
            $constraint,
            new Constraint('>', '2'),
        ), true);

        $this->assertFalse(CompiledMatcher::match($multi, '==', '1'));
    }
}
