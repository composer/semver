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

use Composer\Semver\Constraint\ConstraintInterface;

/**
 * Helper class to evaluate constraint by compiling and reusing the code to evaluate
 */
class CompiledMatcher
{
    private $cacheDir;

    private static $checked;

    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ? $cacheDir : \sys_get_temp_dir().'/composer-constraints';
    }

    /**
     * Evaluates the expression: $constraint match $operator $version
     *
     * @param ConstraintInterface $constraint
     * @param int                 $operator
     * @param string              $version
     *
     * @return mixed
     */
    public function match(ConstraintInterface $constraint, $operator, $version)
    {
        $cacheKey = $operator.$constraint;
        if (!isset(static::$checked[$cacheKey])) {
            $sha = \sha1($cacheKey);
            $file = $this->cacheDir.'/'.substr($sha, 0, 2).'/'.$sha.'.php';
            $function = 'composer_semver_constraint_'.$sha;
            if (!\is_file($file)) {
                @mkdir(\dirname($file), 0777, true);
                \file_put_contents($file, '<?php function '.$function.'($v, $b){return '.$constraint->compile($operator).';}');
            }
            $function = '\\'.$function;
            require_once($file);
            static::$checked[$cacheKey] = $function;
        } else {
            $function = static::$checked[$cacheKey];
        }

        $v = $version;

        return $function($version, $v[0] === 'd' && 'dev-' === substr($v, 0, 4));
    }

}
