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

class MultiConstraintTest extends \PHPUnit_Framework_TestCase
{
    protected $multiConstraint;
    protected $versionRequireStart;
    protected $versionRequireEnd;

    protected function setUp()
    {
        $this->multiConstraint = new MultiConstraint(array());
        $this->versionRequireStart = new Constraint('>', '1.0');
        $this->versionRequireEnd = new Constraint('<', '1.2');
    }

    protected function tearDown()
    {
        unset($this->multiConstraint);
        unset($this->versionRequireStart);
        unset($this->versionRequireEnd);
    }

    public function testIsConjunctive()
    {
        $result = $this->multiConstraint->isConjunctive();
        
        $this->assertTrue($result);
    }

    public function testIsDisjunctive()
    {
        $result = $this->multiConstraint->isDisjunctive();
        
        $this->assertFalse($result);
    }

    public function testMultiVersionMatchSucceeds()
    {
        $versionProvide = new Constraint('==', '1.1');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));

        $this->assertTrue($multiRequire->matches($versionProvide));
    }

    public function testMultiVersionProvidedMatchSucceeds()
    {
        $versionProvideStart = new Constraint('>=', '1.1');
        $versionProvideEnd = new Constraint('<', '2.0');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd));

        $this->assertTrue($multiRequire->matches($multiProvide));
    }

    public function testMultiVersionMatchSucceedsInsideForeachLoop()
    {
        $versionProvideStart = new Constraint('>', '1.0');
        $versionProvideEnd = new Constraint('<', '1.2');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd), false);
        $multiProvide = new MultiConstraint(array($versionProvideStart, $versionProvideEnd), false);

        $this->assertTrue($multiRequire->matches($multiProvide));
    }

    public function testMultiVersionMatchFails()
    {
        $versionProvide = new Constraint('==', '1.2');

        $multiRequire = new MultiConstraint(array($this->versionRequireStart, $this->versionRequireEnd));

        $this->assertFalse($multiRequire->matches($versionProvide));
    }

    public function testGetPrettyString()
    {
        $multiConstraint = new MultiConstraint(array());
        $expectedString = 'pretty-string';
        $multiConstraint->setPrettyString($expectedString);
        $result = $multiConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);

        $expectedString = '[]';
        $multiConstraint->setPrettyString(null);
        $result = $multiConstraint->getPrettyString();

        $this->assertSame($expectedString, $result);
    }
}
