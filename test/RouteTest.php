<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit\Framework\TestCase;
use ZF\Console\Route;

class RouteTest extends TestCase
{
    public function testConstructorExpectsNameAndRoute()
    {
        $route = new Route('foo', 'foo bar');
        $this->assertEquals('foo', $route->getName());
        $this->assertEquals('foo bar', $route->getRoute());
    }

    public function testDescriptionIsEmptyByDefault()
    {
        $route = new Route('foo', 'foo bar');
        $this->assertEquals('', $route->getDescription());
    }

    public function testDescriptionIsMutable()
    {
        $route = new Route('foo', 'foo bar');
        $route->setDescription('foobarbazbat');
        $this->assertEquals('foobarbazbat', $route->getDescription());
    }

    public function testShortDescriptionIsEmptyByDefault()
    {
        $route = new Route('foo', 'foo bar');
        $this->assertEquals('', $route->getShortDescription());
    }

    public function testShortDescriptionIsMutable()
    {
        $route = new Route('foo', 'foo bar');
        $route->setShortDescription('foobarbazbat');
        $this->assertEquals('foobarbazbat', $route->getShortDescription());
    }

    public function testOptionsDescriptionIsEmptyArrayByDefault()
    {
        $route = new Route('foo', 'foo bar');
        $this->assertEquals([], $route->getOptionsDescription());
    }

    public function testOptionsDescriptionIsMutable()
    {
        $route = new Route('foo', 'foo bar');
        $options = [
            'foo' => 'foolalalala',
            'bar' => 'none',
        ];
        $route->setOptionsDescription($options);
        $this->assertEquals($options, $route->getOptionsDescription());
    }

    public function testMatchesAreNullByDefault()
    {
        $route = new Route('foo', 'foo bar');
        $this->assertNull($route->getMatches());
    }

    public function testMatchesArePopulatedOnSuccessfulMatch()
    {
        $route = new Route('foo', 'foo bar');
        $matches = $route->match(['foo', 'bar']);
        $this->assertInternalType('array', $matches);
        $this->assertSame($matches, $route->getMatches());
    }

    public function testIsMatchedReturnsFalseByDefault()
    {
        $route = new Route('foo', 'foo bar');
        $this->assertFalse($route->isMatched());
    }

    public function testIsMatchedReturnsTrueOnSuccesfulMatch()
    {
        $route = new Route('foo', 'foo bar');
        $route->match(['foo', 'bar']);
        $this->assertTrue($route->isMatched());
    }

    public function testMatchedParamReturnsTrueForParameterMatched()
    {
        $route = new Route('foo', 'foo <bar>');
        $matches = $route->match(['foo', 'BAR']);
        $this->assertTrue($route->matchedParam('bar'));
    }

    public function testGetMatchedParamReturnsValueForParameterMatched()
    {
        $route = new Route('foo', 'foo <bar>');
        $matches = $route->match(['foo', 'BAR']);
        $this->assertEquals('BAR', $route->getMatchedParam('bar'));
    }

    public function testGetMatchedParamReturnsDefaultValueIfParameterIsNotMatched()
    {
        $route = new Route('foo', 'foo [<bar>]');
        $matches = $route->match(['foo']);
        $this->assertNull($route->getMatchedParam('bar'));
    }
}
