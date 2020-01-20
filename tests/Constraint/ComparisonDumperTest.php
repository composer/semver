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

use Composer\Semver\VersionParser;
use PHPUnit\Framework\TestCase;

class ComparisonDumperTest extends TestCase
{
    /**
     * @dataProvider dump
     *
     * @param string $constraint
     * @param string $version
     * @param string $expectedDumpComparisonFile
     * @param $shouldEvaluateTrue bool
     */
    public function testDump($constraint, $version, $expectedDumpComparisonFile, $shouldEvaluateTrue)
    {
        $versionParser = new VersionParser();
        $dumper = new ComparisonDumper();
        $dumped = $dumper->dump($versionParser->parseConstraints($constraint));

        $this->assertStringEqualsFile(__DIR__ . '/../Fixtures/' . $expectedDumpComparisonFile, $dumped);

        $evaluated = eval(str_replace('%version%', $version, $dumped));
        $this->assertSame($shouldEvaluateTrue, $evaluated);
    }

    /**
     * @return array
     */
    public function dump()
    {
        return array(
            array('1.0', '1.0', 'comparison_dump_1.txt', true),
            array('>1.0', '1.0', 'comparison_dump_2.txt', false),
            array('>1.0', '1.1', 'comparison_dump_3.txt', true),
            array('<1.0', '0.8', 'comparison_dump_4.txt', true),
            array('<1.0', '1.1', 'comparison_dump_5.txt', false),
            array('^7.2', '7.0', 'comparison_dump_6.txt', false),
            array('^7.2', '7.3.1', 'comparison_dump_7.txt', true),
            array('<1.0 || >1.0', '1.0.0', 'comparison_dump_8.txt', true),
        );
    }
}
