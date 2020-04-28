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
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Composer\Semver\Comparator
 */
class CompiledMatcherTest extends TestCase
{
    public function testMatch()
    {
        $matcher = new CompiledMatcher(\sys_get_temp_dir());
        $this->assertTrue($matcher->match(new Constraint('>=', '1'), Constraint::OP_GE, '2'));
    }
}
