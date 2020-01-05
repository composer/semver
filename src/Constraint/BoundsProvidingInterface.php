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

interface BoundsProvidingInterface
{
    /**
     * Returns an array of bounds.
     * The format is as follows:
     * array(
     *     array('lower' => '<version>', 'upper' => '<version>'),
     *     array('lower' => '<version>', 'upper' => '<version>')
     * )
     *
     * If more than one bound is provided, the bounds are disjunctive (OR).
     * Multiple conjunctive bounds have to be normalized into one so
     * that only one bounds entry is returned upon this method call.
     *
     * @return array
     */
    public function getBounds();
}
