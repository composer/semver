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
     * @param string $expectedDump
     * @param $shouldEvaluateTrue bool
     */
    public function testDump($constraint, $version, $expectedDump, $shouldEvaluateTrue)
    {
        $versionParser = new VersionParser();
        $dumper = new ComparisonDumper($versionParser);
        $dumped = $dumper->dump($versionParser->parseConstraints($constraint), $version);

        $this->assertSame($expectedDump, $dumped);

        $evaluated = eval('return ' . $expectedDump . ';');

        $this->assertSame($shouldEvaluateTrue, $evaluated);
    }

    /**
     * @return array
     */
    public function dump()
    {
        return array(
            array('1.0', '1.0', "version_compare('1.0.0.0', '1.0.0.0', '>=') && version_compare('1.0.0.0', '1.0.0.0', '<=')", true),
            array('>1.0', '1.0', "version_compare('1.0.0.0', '1.0.0.0', '>')", false),
            array('>1.0', '1.1', "version_compare('1.1.0.0', '1.0.0.0', '>')", true),
            array('<1.0', '0.8', "version_compare('0.8.0.0', '1.0.0.0-dev', '<')", true),
            array('<1.0', '1.1', "version_compare('1.1.0.0', '1.0.0.0-dev', '<')", false),
            array('^7.2', '7.0', "version_compare('7.0.0.0', '7.2.0.0-dev', '>=') && version_compare('7.0.0.0', '8.0.0.0-dev', '<')", false),
            array('^7.2', '7.3.1', "version_compare('7.3.1.0', '7.2.0.0-dev', '>=') && version_compare('7.3.1.0', '8.0.0.0-dev', '<')", true),
            array('<1.0 || >1.0', '1.0.0', "true", true),
        );
    }
}
