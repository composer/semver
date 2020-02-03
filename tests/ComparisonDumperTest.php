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

class ComparisonDumperTest extends TestCase
{
    /**
     * @dataProvider dump
     *
     * @param array  $dumpConfigs
     * @param array  $versionsPerPackage
     * @param string $expectedDumpComparisonFile
     * @param mixed  $expectedResult
     */
    public function testDump(array $dumpConfigs, array $versionPlaceholdersAndValues, $expectedDumpComparisonFile, $expectedResult)
    {
        $dumper = new ComparisonDumper();
        $dumped = $dumper->dump($dumpConfigs);

        $this->assertStringEqualsFile(__DIR__ . '/Fixtures/' . $expectedDumpComparisonFile, $dumped);

        foreach ($versionPlaceholdersAndValues as $placeholder => $value) {
            $dumped = str_replace($placeholder, $value, $dumped);
        }

        $evaluated = eval($dumped);
        $this->assertSame($expectedResult, $evaluated);
    }

    /**
     * @return array
     */
    public function dump()
    {
        $versionParser = new VersionParser();

        return array(
            '== 1.0 (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('1.0'))),
                array('%version%' => '1.0'),
                'comparison_dump_1.txt',
                true,
            ),
            '> 1.0 (false)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('> 1.0'))),
                array('%version%' => '1.0'),
                'comparison_dump_2.txt',
                'packageA',
            ),
            '> 1.0 (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('> 1.0'))),
                array('%version%' => '1.1'),
                'comparison_dump_3.txt',
                true,
            ),
            '< 1.0 (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('< 1.0'))),
                array('%version%' => '0.8'),
                'comparison_dump_4.txt',
                true,
            ),
            '< 1.0 (false)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('< 1.0'))),
                array('%version%' => '1.1'),
                'comparison_dump_5.txt',
                'packageA', // This test will need to be adjusted with future PHP versions as it tests if setting a constant works (PHP_VERSION)
            ),
            '^7.2 (false)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('^7.2'))),
                array('%version%' => '7.0'),
                'comparison_dump_6.txt',
                'packageA',
            ),
            '^7.2 (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('^7.2'))),
                array('%version%' => '7.3.1'),
                'comparison_dump_7.txt',
                true,
            ),
            '<1.0 || >1.0 (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('<1.0 || >1.0'))),
                array('%version%' => '1.0.0'),
                'comparison_dump_8.txt',
                true,
            ),
            '^5.3 || ^7.0 (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('^5.3 || ^7.0'), '%version%')),
                array('%version%' => 'PHP_VERSION'),
                'comparison_dump_9.txt',
                true,
            ),
            'Multiple constraints (true)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('^1.0'), "'%version_packageA%'"), new ComparisonDumpConfig('packageB', $versionParser->parseConstraints('^2.0'), "'%version_packageB%'")),
                array('%version_packageA%' => '1.0.0', '%version_packageB%' => '2.0.0'),
                'comparison_dump_10.txt',
                true,
            ),
            'Multiple constraints (false)' => array(
                array(new ComparisonDumpConfig('packageA', $versionParser->parseConstraints('^1.0'), "'%version_packageA%'"), new ComparisonDumpConfig('packageB', $versionParser->parseConstraints('^2.0'), "'%version_packageB%'")),
                array('%version_packageA%' => '1.0.0', '%version_packageB%' => '1.8.0'),
                'comparison_dump_11.txt',
                'packageB',
            ),
        );
    }
}
