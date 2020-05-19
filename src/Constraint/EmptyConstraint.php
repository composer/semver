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

@trigger_error(E_USER_DEPRECATED, 'Composer\Semver\Constraint\EmptyConstraint is deprecated and will be removed in 3.0, use MatchAllConstraint instead.');

/**
 * Defines the absence of a constraint.
 *
 * @deprecated Use MatchAllConstraint instead
 */
class EmptyConstraint extends MatchAllConstraint
{
}
