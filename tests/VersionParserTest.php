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

use Composer\Semver\Constraint\EmptyConstraint;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

class VersionParserTest extends TestCase
{
    /**
     * @dataProvider numericAliasVersions
     */
    public function testParseNumericAliasPrefix($input, $expected)
    {
        $parser = new VersionParser();
        $this->assertSame($expected, $parser->parseNumericAliasPrefix($input));
    }

    public function numericAliasVersions()
    {
        return array(
            array('0.x-dev', '0.'),
            array('1.0.x-dev', '1.0.'),
            array('1.x-dev', '1.'),
            array('1.2.x-dev', '1.2.'),
            array('1.2-dev', '1.2.'),
            array('1-dev', '1.'),
            array('dev-develop', false),
            array('dev-master', false),
        );
    }

    /**
     * @dataProvider successfulNormalizedVersions
     */
    public function testNormalizeSucceeds($input, $expected)
    {
        $parser = new VersionParser();
        $this->assertSame($expected, $parser->normalize($input));
    }

    public function successfulNormalizedVersions()
    {
        return array(
            'none' => array('1.0.0', '1.0.0.0'),
            'none/2' => array('1.2.3.4', '1.2.3.4'),
            'parses state' => array('1.0.0RC1dev', '1.0.0.0-RC1-dev'),
            'CI parsing' => array('1.0.0-rC15-dev', '1.0.0.0-RC15-dev'),
            'delimiters' => array('1.0.0.RC.15-dev', '1.0.0.0-RC15-dev'),
            'RC uppercase' => array('1.0.0-rc1', '1.0.0.0-RC1'),
            'patch replace' => array('1.0.0.pl3-dev', '1.0.0.0-patch3-dev'),
            'forces w.x.y.z' => array('1.0-dev', '1.0.0.0-dev'),
            'forces w.x.y.z/2' => array('0', '0.0.0.0'),
            'parses long' => array('10.4.13-beta', '10.4.13.0-beta'),
            'parses long/2' => array('10.4.13beta2', '10.4.13.0-beta2'),
            'parses long/semver' => array('10.4.13beta.2', '10.4.13.0-beta2'),
            'expand shorthand' => array('10.4.13-b', '10.4.13.0-beta'),
            'expand shorthand/2' => array('10.4.13-b5', '10.4.13.0-beta5'),
            'strips leading v' => array('v1.0.0', '1.0.0.0'),
            'parses dates y-m as classical' => array('2010.01', '2010.01.0.0'),
            'parses dates w/ . as classical' => array('2010.01.02', '2010.01.02.0'),
            'parses dates y.m.Y as classical' => array('2010.1.555', '2010.1.555.0'),
            'parses dates y.m.Y/2 as classical' => array('2010.10.200', '2010.10.200.0'),
            'strips v/datetime' => array('v20100102', '20100102'),
            'parses dates w/ -' => array('2010-01-02', '2010.01.02'),
            'parses numbers' => array('2010-01-02.5', '2010.01.02.5'),
            'parses dates y.m.Y' => array('2010.1.555', '2010.1.555.0'),
            'parses datetime' => array('20100102-203040', '20100102.203040'),
            'parses dt+number' => array('20100102203040-10', '20100102203040.10'),
            'parses dt+patch' => array('20100102-203040-p1', '20100102.203040-patch1'),
            'parses master' => array('dev-master', '9999999-dev'),
            'parses trunk' => array('dev-trunk', '9999999-dev'),
            'parses branches' => array('1.x-dev', '1.9999999.9999999.9999999-dev'),
            'parses arbitrary' => array('dev-feature-foo', 'dev-feature-foo'),
            'parses arbitrary/2' => array('DEV-FOOBAR', 'dev-FOOBAR'),
            'parses arbitrary/3' => array('dev-feature/foo', 'dev-feature/foo'),
            'parses arbitrary/4' => array('dev-feature+issue-1', 'dev-feature+issue-1'),
            'ignores aliases' => array('dev-master as 1.0.0', '9999999-dev'),
            'semver metadata/2' => array('1.0.0-beta.5+foo', '1.0.0.0-beta5'),
            'semver metadata/3' => array('1.0.0+foo', '1.0.0.0'),
            'semver metadata/4' => array('1.0.0-alpha.3.1+foo', '1.0.0.0-alpha3.1'),
            'semver metadata/5' => array('1.0.0-alpha2.1+foo', '1.0.0.0-alpha2.1'),
            'semver metadata/6' => array('1.0.0-alpha-2.1-3+foo', '1.0.0.0-alpha2.1-3'),
            // not supported for BC 'semver metadata/7' => array('1.0.0-0.3.7', '1.0.0.0-0.3.7'),
            // not supported for BC 'semver metadata/8' => array('1.0.0-x.7.z.92', '1.0.0.0-x.7.z.92'),
            'metadata w/ alias' => array('1.0.0+foo as 2.0', '1.0.0.0'),
        );
    }

    /**
     * @dataProvider failingNormalizedVersions
     * @expectedException \UnexpectedValueException
     */
    public function testNormalizeFails($input)
    {
        $parser = new VersionParser();
        $parser->normalize($input);
    }

    public function failingNormalizedVersions()
    {
        return array(
            'empty ' => array(''),
            'invalid chars' => array('a'),
            'invalid type' => array('1.0.0-meh'),
            'too many bits' => array('1.0.0.0.0'),
            'non-dev arbitrary' => array('feature-foo'),
            'metadata w/ space' => array('1.0.0+foo bar'),
            'maven style release' => array('1.0.1-SNAPSHOT'),
        );
    }

    /**
     * @dataProvider successfulNormalizedBranches
     */
    public function testNormalizeBranch($input, $expected)
    {
        $parser = new VersionParser();
        $this->assertSame((string) $expected, (string) $parser->normalizeBranch($input));
    }

    public function successfulNormalizedBranches()
    {
        return array(
            'parses x' => array('v1.x', '1.9999999.9999999.9999999-dev'),
            'parses *' => array('v1.*', '1.9999999.9999999.9999999-dev'),
            'parses digits' => array('v1.0', '1.0.9999999.9999999-dev'),
            'parses digits/2' => array('2.0', '2.0.9999999.9999999-dev'),
            'parses long x' => array('v1.0.x', '1.0.9999999.9999999-dev'),
            'parses long *' => array('v1.0.3.*', '1.0.3.9999999-dev'),
            'parses long digits' => array('v2.4.0', '2.4.0.9999999-dev'),
            'parses long digits/2' => array('2.4.4', '2.4.4.9999999-dev'),
            'parses master' => array('master', '9999999-dev'),
            'parses trunk' => array('trunk', '9999999-dev'),
            'parses arbitrary' => array('feature-a', 'dev-feature-a'),
            'parses arbitrary/2' => array('FOOBAR', 'dev-FOOBAR'),
            'parses arbitrary/3' => array('feature+issue-1', 'dev-feature+issue-1'),
        );
    }

    public function testParseConstraintsIgnoresStabilityFlag()
    {
        $parser = new VersionParser();
        $this->assertSame((string) new Constraint('=', '1.0.0.0'), (string) $parser->parseConstraints('1.0@dev'));
    }

    public function testParseConstraintsIgnoresReferenceOnDevVersion()
    {
        $parser = new VersionParser();
        $this->assertSame((string) new Constraint('=', '1.0.9999999.9999999-dev'), (string) $parser->parseConstraints('1.0.x-dev#abcd123'));
        $this->assertSame((string) new Constraint('=', '1.0.9999999.9999999-dev'), (string) $parser->parseConstraints('1.0.x-dev#trunk/@123'));
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testParseConstraintsFailsOnBadReference()
    {
        $parser = new VersionParser();
        $this->assertSame((string) new Constraint('=', '1.0.0.0'), (string) $parser->parseConstraints('1.0#abcd123'));
        $this->assertSame((string) new Constraint('=', '1.0.0.0'), (string) $parser->parseConstraints('1.0#trunk/@123'));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Invalid operator "~>", you probably meant to use the "~" operator
     */
    public function testParseConstraintsNudgesRubyDevsTowardsThePathOfRighteousness()
    {
        $parser = new VersionParser();
        $parser->parseConstraints('~>1.2');
    }

    /**
     * @dataProvider simpleConstraints
     *
     * @param string $input
     * @param Constraint $expected
     */
    public function testParseConstraintsSimple($input, $expected)
    {
        $parser = new VersionParser();
        $this->assertSame((string) $expected, (string) $parser->parseConstraints($input));
    }

    /**
     * @return array
     */
    public function simpleConstraints()
    {
        return array(
            'match any' => array('*', new EmptyConstraint()),
            'match any/2' => array('*.*',  new EmptyConstraint()),
            'match any/2v' => array('v*.*', new EmptyConstraint()),
            'match any/3' => array('*.x.*', new EmptyConstraint()),
            'match any/4' => array('x.X.x.*', new EmptyConstraint()),
            'not equal' => array('<>1.0.0', new Constraint('<>', '1.0.0.0')),
            'not equal/2' => array('!=1.0.0', new Constraint('!=', '1.0.0.0')),
            'greater than' => array('>1.0.0', new Constraint('>', '1.0.0.0')),
            'lesser than' => array('<1.2.3.4', new Constraint('<', '1.2.3.4-dev')),
            'less/eq than' => array('<=1.2.3', new Constraint('<=', '1.2.3.0')),
            'great/eq than' => array('>=1.2.3', new Constraint('>=', '1.2.3.0-dev')),
            'equals' => array('=1.2.3', new Constraint('=', '1.2.3.0')),
            'double equals' => array('==1.2.3', new Constraint('=', '1.2.3.0')),
            'no op means eq' => array('1.2.3', new Constraint('=', '1.2.3.0')),
            'completes version' => array('=1.0', new Constraint('=', '1.0.0.0')),
            'shorthand beta' => array('1.2.3b5', new Constraint('=', '1.2.3.0-beta5')),
            'shorthand alpha' => array('1.2.3a1', new Constraint('=', '1.2.3.0-alpha1')),
            'shorthand patch' => array('1.2.3p1234', new Constraint('=', '1.2.3.0-patch1234')),
            'shorthand patch/2' => array('1.2.3pl1234', new Constraint('=', '1.2.3.0-patch1234')),
            'accepts spaces' => array('>= 1.2.3', new Constraint('>=', '1.2.3.0-dev')),
            'accepts spaces/2' => array('< 1.2.3', new Constraint('<', '1.2.3.0-dev')),
            'accepts spaces/3' => array('> 1.2.3', new Constraint('>', '1.2.3.0')),
            'accepts master' => array('>=dev-master', new Constraint('>=', '9999999-dev')),
            'accepts master/2' => array('dev-master', new Constraint('=', '9999999-dev')),
            'accepts arbitrary' => array('dev-feature-a', new Constraint('=', 'dev-feature-a')),
            'regression #550' => array('dev-some-fix', new Constraint('=', 'dev-some-fix')),
            'regression #935' => array('dev-CAPS', new Constraint('=', 'dev-CAPS')),
            'ignores aliases' => array('dev-master as 1.0.0', new Constraint('=', '9999999-dev')),
            'lesser than override' => array('<1.2.3.4-stable', new Constraint('<', '1.2.3.4')),
            'great/eq than override' => array('>=1.2.3.4-stable', new Constraint('>=', '1.2.3.4')),
        );
    }

    /**
     * @dataProvider wildcardConstraints
     *
     * @param string $input
     * @param Constraint $min
     * @param Constraint $max
     */
    public function testParseConstraintsWildcard($input, $min, $max)
    {
        $parser = new VersionParser();
        if ($min) {
            $expected = new MultiConstraint(array($min, $max));
        } else {
            $expected = $max;
        }

        $this->assertSame((string) $expected, (string) $parser->parseConstraints($input));
    }

    /**
     * @return array
     */
    public function wildcardConstraints()
    {
        return array(
            array('v2.*', new Constraint('>=', '2.0.0.0-dev'), new Constraint('<', '3.0.0.0-dev')),
            array('2.*.*', new Constraint('>=', '2.0.0.0-dev'), new Constraint('<', '3.0.0.0-dev')),
            array('20.*', new Constraint('>=', '20.0.0.0-dev'), new Constraint('<', '21.0.0.0-dev')),
            array('20.*.*', new Constraint('>=', '20.0.0.0-dev'), new Constraint('<', '21.0.0.0-dev')),
            array('2.0.*', new Constraint('>=', '2.0.0.0-dev'), new Constraint('<', '2.1.0.0-dev')),
            array('2.x', new Constraint('>=', '2.0.0.0-dev'), new Constraint('<', '3.0.0.0-dev')),
            array('2.x.x', new Constraint('>=', '2.0.0.0-dev'), new Constraint('<', '3.0.0.0-dev')),
            array('2.2.x', new Constraint('>=', '2.2.0.0-dev'), new Constraint('<', '2.3.0.0-dev')),
            array('2.10.X', new Constraint('>=', '2.10.0.0-dev'), new Constraint('<', '2.11.0.0-dev')),
            array('2.1.3.*', new Constraint('>=', '2.1.3.0-dev'), new Constraint('<', '2.1.4.0-dev')),
            array('0.*', null, new Constraint('<', '1.0.0.0-dev')),
            array('0.*.*', null, new Constraint('<', '1.0.0.0-dev')),
            array('0.x', null, new Constraint('<', '1.0.0.0-dev')),
            array('0.x.x', null, new Constraint('<', '1.0.0.0-dev')),
        );
    }

    /**
     * @dataProvider tildeConstraints
     *
     * @param string $input
     * @param Constraint $min
     * @param Constraint $max
     */
    public function testParseTildeWildcard($input, $min, $max)
    {
        $parser = new VersionParser();
        if ($min) {
            $expected = new MultiConstraint(array($min, $max));
        } else {
            $expected = $max;
        }

        $this->assertSame((string) $expected, (string) $parser->parseConstraints($input));
    }

    /**
     * @return array
     */
    public function tildeConstraints()
    {
        return array(
            array('~v1', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('~1.0', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('~1.0.0', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '1.1.0.0-dev')),
            array('~1.2', new Constraint('>=', '1.2.0.0-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('~1.2.3', new Constraint('>=', '1.2.3.0-dev'), new Constraint('<', '1.3.0.0-dev')),
            array('~1.2.3.4', new Constraint('>=', '1.2.3.4-dev'), new Constraint('<', '1.2.4.0-dev')),
            array('~1.2-beta',new Constraint('>=', '1.2.0.0-beta'), new Constraint('<', '2.0.0.0-dev')),
            array('~1.2-b2', new Constraint('>=', '1.2.0.0-beta2'), new Constraint('<', '2.0.0.0-dev')),
            array('~1.2-BETA2', new Constraint('>=', '1.2.0.0-beta2'), new Constraint('<', '2.0.0.0-dev')),
            array('~1.2.2-dev', new Constraint('>=', '1.2.2.0-dev'), new Constraint('<', '1.3.0.0-dev')),
            array('~1.2.2-stable', new Constraint('>=', '1.2.2.0-stable'), new Constraint('<', '1.3.0.0-dev')),
        );
    }

    /**
     * @dataProvider caretConstraints
     *
     * @param string $input
     * @param Constraint $min
     * @param Constraint $max
     */
    public function testParseCaretWildcard($input, $min, $max)
    {
        $parser = new VersionParser();
        if ($min) {
            $expected = new MultiConstraint(array($min, $max));
        } else {
            $expected = $max;
        }

        $this->assertSame((string) $expected, (string) $parser->parseConstraints($input));
    }

    /**
     * @return array
     */
    public function caretConstraints()
    {
        return array(
            array('^v1', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('^0', new Constraint('>=', '0.0.0.0-dev'), new Constraint('<', '1.0.0.0-dev')),
            array('^0.0', new Constraint('>=', '0.0.0.0-dev'), new Constraint('<', '0.1.0.0-dev')),
            array('^1.2', new Constraint('>=', '1.2.0.0-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('^1.2.3-beta.2', new Constraint('>=', '1.2.3.0-beta2'), new Constraint('<', '2.0.0.0-dev')),
            array('^1.2.3.4', new Constraint('>=', '1.2.3.4-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('^1.2.3', new Constraint('>=', '1.2.3.0-dev'), new Constraint('<', '2.0.0.0-dev')),
            array('^0.2.3', new Constraint('>=', '0.2.3.0-dev'), new Constraint('<', '0.3.0.0-dev')),
            array('^0.2', new Constraint('>=', '0.2.0.0-dev'), new Constraint('<', '0.3.0.0-dev')),
            array('^0.2.0', new Constraint('>=', '0.2.0.0-dev'), new Constraint('<', '0.3.0.0-dev')),
            array('^0.0.3', new Constraint('>=', '0.0.3.0-dev'), new Constraint('<', '0.0.4.0-dev')),
            array('^0.0.3-alpha', new Constraint('>=', '0.0.3.0-alpha'), new Constraint('<', '0.0.4.0-dev')),
            array('^0.0.3-dev', new Constraint('>=', '0.0.3.0-dev'), new Constraint('<', '0.0.4.0-dev')),
        );
    }

    /**
     * @dataProvider hyphenConstraints
     *
     * @param string $input
     * @param Constraint $min
     * @param Constraint $max
     */
    public function testParseHyphen($input, $min, $max)
    {
        $parser = new VersionParser();
        if ($min) {
            $expected = new MultiConstraint(array($min, $max));
        } else {
            $expected = $max;
        }

        $this->assertSame((string) $expected, (string) $parser->parseConstraints($input));
    }

    /**
     * @return array
     */
    public function hyphenConstraints()
    {
        return array(
            array('v1 - v2', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '3.0.0.0-dev')),
            array('1.2.3 - 2.3.4.5', new Constraint('>=', '1.2.3.0-dev'), new Constraint('<=', '2.3.4.5')),
            array('1.2-beta - 2.3', new Constraint('>=', '1.2.0.0-beta'), new Constraint('<', '2.4.0.0-dev')),
            array('1.2-beta - 2.3-dev', new Constraint('>=', '1.2.0.0-beta'), new Constraint('<=', '2.3.0.0-dev')),
            array('1.2-RC - 2.3.1', new Constraint('>=', '1.2.0.0-RC'), new Constraint('<=', '2.3.1.0')),
            array('1.2.3-alpha - 2.3-RC', new Constraint('>=', '1.2.3.0-alpha'), new Constraint('<=', '2.3.0.0-RC')),
            array('1 - 2.0', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '2.1.0.0-dev')),
            array('1 - 2.1', new Constraint('>=', '1.0.0.0-dev'), new Constraint('<', '2.2.0.0-dev')),
            array('1.2 - 2.1.0', new Constraint('>=', '1.2.0.0-dev'), new Constraint('<=', '2.1.0.0')),
            array('1.3 - 2.1.3', new Constraint('>=', '1.3.0.0-dev'), new Constraint('<=', '2.1.3.0')),
        );
    }

    public function testParseConstraintsMultiCollapsesContiguous()
    {
        $parser = new VersionParser();
        $first = new Constraint('>=', '2.5.0.0-dev');
        $second = new Constraint('<', '4.0.0.0-dev');
        $multi = new MultiConstraint(array($first, $second));
        $this->assertSame((string) $multi, (string) $parser->parseConstraints('^2.5 || ^3.0'));
    }

    public function testParseCaretConstraintsMultiDoesNotCollapseNonContiguousRange()
    {
        $parser = new VersionParser();
        $first = new MultiConstraint(array(
            new Constraint('>=', '0.2.0.0-dev'),
            new Constraint('<', '0.3.0.0-dev'),
        ));
        $second = new MultiConstraint(array(
            new Constraint('>=', '1.0.0.0-dev'),
            new Constraint('<', '2.0.0.0-dev'),
        ));
        $multi = new MultiConstraint(array($first, $second), false);
        $parsed = $parser->parseConstraints('^0.2 || ^1.0');
        $this->assertSame((string) $multi, (string) $parsed);
    }

    public function testDoNotCollapseContiguousRangeIfOtherConstraintsAlsoApply()
    {
        $parser = new VersionParser();

        $first = new MultiConstraint(array(
            new Constraint('>=', '0.1.0.0-dev'),
            new Constraint('<', '1.0.0.0-dev'),
        ));
        $second = new MultiConstraint(array(
            new Constraint('>=', '1.0.0.0-dev'),
            new Constraint('<', '2.0.0.0-dev'),
            new Constraint('!=', '1.0.1.0'),
        ));
        $multi = new MultiConstraint(array($first, $second), false);

        $version = new Constraint('=', '1.0.1.0');
        $this->assertFalse($multi->matches($version), 'Generated expectation should not allow version "1.0.1.0"');

        $parsed = $parser->parseConstraints('~0.1 || ~1.0 !=1.0.1');
        $this->assertFalse($parsed->matches($version), '"~0.1 || ~1.0 !=1.0.1" should not allow version "1.0.1.0"');

        $this->assertSame((string) $multi, (string) $parsed);
    }

    /**
     * @dataProvider multiConstraintProvider
     */
    public function testParseConstraintsMulti($constraint)
    {
        $parser = new VersionParser();
        $first = new Constraint('>', '2.0.0.0');
        $second = new Constraint('<=', '3.0.0.0');
        $multi = new MultiConstraint(array($first, $second));
        $this->assertSame((string) $multi, (string) $parser->parseConstraints($constraint));
    }

    /**
     * @return array
     */
    public function multiConstraintProvider()
    {
        return array(
            array('>2.0,<=3.0'),
            array('>2.0 <=3.0'),
            array('>2.0  <=3.0'),
            array('>2.0, <=3.0'),
            array('>2.0 ,<=3.0'),
            array('>2.0 , <=3.0'),
            array('>2.0   , <=3.0'),
            array('> 2.0   <=  3.0'),
            array('> 2.0  ,  <=  3.0'),
            array('  > 2.0  ,  <=  3.0 '),
        );
    }

    public function testParseConstraintsMultiWithStabilitySuffix()
    {
        $parser = new VersionParser();
        $first = new Constraint('>=', '1.1.0.0-alpha4');
        $second = new Constraint('<', '1.2.9999999.9999999-dev');
        $multi = new MultiConstraint(array($first, $second));
        $this->assertSame((string) $multi, (string) $parser->parseConstraints('>=1.1.0-alpha4,<1.2.x-dev'));

        $first = new Constraint('>=', '1.1.0.0-alpha4');
        $second = new Constraint('<', '1.2.0.0-beta2');
        $multi = new MultiConstraint(array($first, $second));
        $this->assertSame((string) $multi, (string) $parser->parseConstraints('>=1.1.0-alpha4,<1.2-beta2'));
    }

    /**
     * @dataProvider multiConstraintProvider2
     *
     * @param string $constraint
     */
    public function testParseConstraintsMultiDisjunctiveHasPrioOverConjuctive($constraint)
    {
        $parser = new VersionParser();
        $first = new Constraint('>', '2.0.0.0');
        $second = new Constraint('<', '2.0.5.0-dev');
        $third = new Constraint('>', '2.0.6.0');
        $multi1 = new MultiConstraint(array($first, $second));
        $multi2 = new MultiConstraint(array($multi1, $third), false);
        $this->assertSame((string) $multi2, (string) $parser->parseConstraints($constraint));
    }

    /**
     * @return array
     */
    public function multiConstraintProvider2()
    {
        return array(
            array('>2.0,<2.0.5 | >2.0.6'),
            array('>2.0,<2.0.5 || >2.0.6'),
            array('> 2.0 , <2.0.5 | >  2.0.6'),
        );
    }

    public function testParseConstraintsMultiWithStabilities()
    {
        $parser = new VersionParser();
        $first = new Constraint('>', '2.0.0.0');
        $second = new Constraint('<=', '3.0.0.0-dev');
        $multi = new MultiConstraint(array($first, $second));
        $this->assertSame((string) $multi, (string) $parser->parseConstraints('>2.0@stable,<=3.0@dev'));
    }

    /**
     * @dataProvider failingConstraints
     * @expectedException \UnexpectedValueException
     *
     * @param string $input
     */
    public function testParseConstraintsFails($input)
    {
        $parser = new VersionParser();
        $parser->parseConstraints($input);
    }

    /**
     * @return array
     */
    public function failingConstraints()
    {
        return array(
            'empty ' => array(''),
            'invalid version' => array('1.0.0-meh'),
            'operator abuse' => array('>2.0,,<=3.0'),
            'operator abuse/2' => array('>2.0 ,, <=3.0'),
            'operator abuse/3' => array('>2.0 ||| <=3.0'),
        );
    }

    /**
     * @dataProvider stabilityProvider
     *
     * @param string $expected
     * @param string $version
     */
    public function testParseStability($expected, $version)
    {
        $this->assertSame($expected, VersionParser::parseStability($version));
    }

    /**
     * @return array
     */
    public function stabilityProvider()
    {
        return array(
            array('stable', '1'),
            array('stable', '1.0'),
            array('stable', '3.2.1'),
            array('stable', 'v3.2.1'),
            array('dev', 'v2.0.x-dev'),
            array('dev', 'v2.0.x-dev#abc123'),
            array('dev', 'v2.0.x-dev#trunk/@123'),
            array('RC', '3.0-RC2'),
            array('dev', 'dev-master'),
            array('dev', '3.1.2-dev'),
            array('dev', 'dev-feature+issue-1'),
            array('stable', '3.1.2-p1'),
            array('stable', '3.1.2-pl2'),
            array('stable', '3.1.2-patch'),
            array('alpha', '3.1.2-alpha5'),
            array('beta', '3.1.2-beta'),
            array('beta', '2.0B1'),
            array('alpha', '1.2.0a1'),
            array('alpha', '1.2_a1'),
            array('RC', '2.0.0rc1'),
            array('alpha', '1.0.0-alpha11+cs-1.1.0'),
        );
    }
}
