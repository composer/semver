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

use PHPUnit\Framework\TestCase;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Constraint\Constraint;

class IntervalsTest extends TestCase
{
    const INTERVAL_ANY = '*';

    /**
     * @dataProvider intervalsProvider
     */
    public function testGetIntervals($expected, $constraint)
    {
        if (is_string($constraint)) {
            $parser = new VersionParser;
            $constraint = $parser->parseConstraints($constraint);
        }

        $result = Intervals::get($constraint);
        if (is_array($result)) {
            array_walk_recursive($result, function (&$c) {
                if ($c instanceof ConstraintInterface) {
                    $c = (string) $c;
                }
            });
        }

        if ($expected === self::INTERVAL_ANY) {
            $expected = array('intervals' => array(
                array(
                    'start' => '>= 0.0.0.0-dev',
                    'end' => '< '.PHP_INT_MAX.'.0.0.0',
                ),
            ), 'devConstraints' => array());
        }

        $this->assertSame($expected, $result);
    }

    public function intervalsProvider()
    {
        return array(
            'simple case' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^1.0'
            ),
            'simple case/2' => array(
                array('intervals' => array(
                    array(
                        'start' => '> 1.0.0.0',
                        'end' => '< '.PHP_INT_MAX.'.0.0.0',
                    ),
                ), 'devConstraints' => array()),
                '> 1.0'
            ),
            'intervals should be sorted' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 0.9.0.0-dev',
                        'end' => '< 1.0.0.0-dev',
                    ),
                    array(
                        'start' => '>= 1.2.3.0',
                        'end' => '<= 1.2.3.0',
                    ),
                    array(
                        'start' => '>= 1.3.4.0',
                        'end' => '<= 1.3.4.0',
                    ),
                    array(
                        'start' => '> 2.3.0.0',
                        'end' => '< 2.5.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '1.3.4 || 1.2.3 || >2.3,<2.5 || <1,>=0.9'
            ),
            'intervals should be sorted and consecutive ones merged' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                    array(
                        'start' => '>= 3.0.0.0-dev',
                        'end' => '< 5.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^4.0 || ^1.0 || ^3.0'
            ),
            'consecutive intervals should be merged even if one has no end' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 4.0.0.0-dev',
                        'end' => '< '.PHP_INT_MAX.'.0.0.0',
                    ),
                ), 'devConstraints' => array()),
                '^4.0 || >= 5'
            ),
            'consecutive intervals should be merged even if one has no start' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 0.0.0.0-dev',
                        'end' => '< 6.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '>= 5,< 6 || < 5'
            ),
            'consecutive intervals representing everything should become *' => array(
                self::INTERVAL_ANY,
                '>= 5 || < 5'
            ),
            'intervals should be sorted and overlapping ones merged' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.1.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                    array(
                        'start' => '>= 3.0.0.0-dev',
                        'end' => '< 5.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^4.0 || ^1.1 || ^3.0 || ^1.2'
            ),
            'intervals should be sorted and overlapping ones merged/2' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 1.5.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '1.2 - 1.4 || 1.0 - 1.3'
            ),
            'overlapping intervals should be merged even if the last has no end' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 4.0.0.0-dev',
                        'end' => '< '.PHP_INT_MAX.'.0.0.0',
                    ),
                ), 'devConstraints' => array()),
                '^4.0 || >= 4.5'
            ),
            'overlapping intervals should be merged even if the first has no start' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 0.0.0.0-dev',
                        'end' => '< 6.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '>= 5,< 6 || < 5.3'
            ),
            'overlapping intervals representing everything should become *' => array(
                self::INTERVAL_ANY,
                '>= 5 || <= 5'
            ),
            'equal intervals should be merged' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^1.0 || ^1.0'
            ),
            'weird input order should still be a good result' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 0.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '< 2.0 || < 1.2'
            ),
            'weird input order should still be a good result/matches everything' => array(
                self::INTERVAL_ANY,
                '< 2.0 || >= 1'
            ),
            'weird input order should still be a good result, conjunctive' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '< 2.0, >= 1'
            ),
            'conjunctive constraints should be intersected' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.2.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^1.0, ^1.2'
            ),
            'conjunctive constraints should be intersected/2' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.5.0.0-dev',
                        'end' => '< 1.7.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^1.0, ^1.2, 1.4 - 1.8, 1.5 - 1.6, 1.5 - 2'
            ),
            'conjunctive constraints should be intersected/not flattened by version parser' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.5.0.0-dev',
                        'end' => '< 1.7.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                new MultiConstraint(array(
                    new MultiConstraint(array(
                        new Constraint('>=', '1.0.0.0-dev'),
                        new Constraint('<', '2.0.0.0-dev'),
                    ), true),
                    new MultiConstraint(array(
                        new Constraint('>=', '1.2.0.0-dev'),
                        new Constraint('<', '2.0.0.0-dev'),
                    ), true),
                    new MultiConstraint(array(
                        new Constraint('>=', '1.4.0.0-dev'),
                        new Constraint('<', '1.9.0.0-dev'),
                    ), true),
                    new MultiConstraint(array(
                        new Constraint('>=', '1.5.0.0-dev'),
                        new Constraint('<', '1.7.0.0-dev'),
                    ), true),
                    new MultiConstraint(array(
                        new Constraint('>=', '1.5.0.0-dev'),
                        new Constraint('<', '3.0.0.0-dev'),
                    ), true),
                ), true),
            ),
            'conjunctive constraints with disjunctive subcomponents should be intersected/not flattened by version parser' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.8.0.0-dev',
                        'end' => '< 1.10.0.0-dev',
                    ),
                    array(
                        'start' => '>= 1.12.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                new MultiConstraint(array(
                    new MultiConstraint(array( // 1.0 - 1.2 || ^1.5
                        new MultiConstraint(array(
                            new Constraint('>=', '1.0.0.0-dev'),
                            new Constraint('<', '1.3.0.0-dev'),
                        ), true),
                        new MultiConstraint(array(
                            new Constraint('>=', '1.5.0.0-dev'),
                            new Constraint('<', '2.0.0.0-dev'),
                        ), true),
                    ), false),
                    new MultiConstraint(array( // 1.8 - 1.9 || ^1.12
                        new MultiConstraint(array(
                            new Constraint('>=', '1.8.0.0-dev'),
                            new Constraint('<', '1.10.0.0-dev'),
                        ), true),
                        new MultiConstraint(array(
                            new Constraint('>=', '1.12.0.0-dev'),
                            new Constraint('<', '2.0.0.0-dev'),
                        ), true),
                    ), false),
                ), true),
            ),
            'conjunctive constraints with equal constraints' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.3.2.0-dev',
                        'end' => '<= 1.3.2.0-dev',
                    ),
                ), 'devConstraints' => array()),
                new MultiConstraint(array(
                    new MultiConstraint(array(
                        new Constraint('==', '1.3.1.0-dev'),
                        new Constraint('==', '1.3.2.0-dev'),
                        new Constraint('==', '1.3.3.0-dev'),
                    ), false),
                    new Constraint('==', '1.3.2.0-dev'),
                ), true),
            ),
            'conjunctive constraints simple' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.5.0.0-dev',
                        'end' => '< 3.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '1.5 - 2'
            ),
            'conjunctive constraints with exclusions' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 1.2.3.0',
                    ),
                    array(
                        'start' => '> 1.2.3.0',
                        'end' => '< 1.4.5.0',
                    ),
                    array(
                        'start' => '> 1.4.5.0',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array('!= dev-foo', '!= dev-master')),
                '!= 1.4.5, ^1.0, != 1.2.3, != 2.3, != dev-foo, != dev-master'
            ),
            'disjunctive constraints with exclusions' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 0.0.0.0-dev',
                        'end' => '< '.PHP_INT_MAX.'.0.0.0',
                    ),
                ), 'devConstraints' => array('!= dev-foo', '!= dev-master')),
                '!= 1.4.5 || ^1.0 || != dev-foo || != dev-master'
            ),
            'conjunctive constraints with exact versions' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array('== dev-master')),
                'dev-master, ^1.0'
            ),
            'disjunctive constraints with exact versions' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array('== dev-master', '== dev-foo')),
                'dev-master || ^1.0 || dev-foo'
            ),
            'conjunctive constraints with * should skip it' => array(
                array('intervals' => array(
                    array(
                        'start' => '>= 1.0.0.0-dev',
                        'end' => '< 2.0.0.0-dev',
                    ),
                ), 'devConstraints' => array()),
                '^1.0, *'
            ),
            'disjunctive constraints with * should result in *' => array(
                self::INTERVAL_ANY,
                '^1.0 || *'
            ),
            'conjunctive constraints with only * should result in *' => array(
                self::INTERVAL_ANY,
                '*, *'
            ),
            'conjunctive constraints with only one * should result in *' => array(
                self::INTERVAL_ANY,
                new MultiConstraint(array(new Constraint('>=', '0.0.0.0-dev'), new Constraint('<', PHP_INT_MAX.'.0.0.0'))),
            ),
        );
    }
}
