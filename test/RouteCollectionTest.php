<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\Console\Route;
use ZF\Console\RouteCollection;

class RouteCollectionTest extends TestCase
{
    public function setUp()
    {
        $this->collection = new RouteCollection();
    }

    public function testGetIteratorReturnsIterator()
    {
        $iterator = $this->collection->getIterator();
        $this->assertInstanceOf('Traversable', $iterator);
    }

    public function testInitialCountIsZero()
    {
        $this->assertEquals(0, count($this->collection));
    }

    /**
     * @depends testInitialCountIsZero
     */
    public function testAddRouteAddsRouteToCollection()
    {
        $route = new Route('foo', 'foo bar');
        $this->collection->addRoute($route);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));
    }

    /**
     * @depends testInitialCountIsZero
     */
    public function testCanAddRouteUsingSpecification()
    {
        $spec = array(
            'name' => 'foo',
            'route' => 'foo [<bar>]',
            'description' => 'This is the full description',
            'short_description' => 'This is the short description',
            'options_descriptions' => array(
                '<bar>' => 'The bar description',
            ),
            'constraints' => array(
                'bar' => '/^[a-z0-9_.-]+$/',
            ),
            'defaults' => array(
                'bar' => false,
            ),
            'aliases' => array(
                'baz' => 'bar',
            ),
            'filters' => array(),
            'validators' => array(),
        );
        $this->collection->addRouteSpec($spec);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));
    }

    public function testIterationIsByNaturalOrderOfNames()
    {
        $this->collection->addRoute(new Route('foo', 'foo bar'));
        $this->collection->addRoute(new Route('bar', 'bar'));
        $this->collection->addRoute(new Route('zeta', 'zeta'));
        $this->collection->addRoute(new Route('alpha', 'alpha'));

        $order = array();
        foreach ($this->collection as $route) {
            $order[] = $route->getName();
        }
        $this->assertEquals(array('alpha', 'bar', 'foo', 'zeta'), $order);
    }

    public function testMatchReturnsFirstRouteToMatch()
    {
        $this->collection->addRoute(new Route('foo', 'foo bar'));
        $this->collection->addRoute(new Route('bar', 'bar'));
        $this->collection->addRoute(new Route('zeta', 'zeta'));
        $this->collection->addRoute(new Route('alpha', 'alpha'));

        $route = $this->collection->match(array('foo', 'bar'));
        $this->assertInstanceOf('ZF\Console\Route', $route);
        $this->assertEquals('foo', $route->getName());
    }

    public function testMatchReturnsFalseIfNoRoutesMatch()
    {
        $this->collection->addRoute(new Route('foo', 'foo bar'));
        $this->collection->addRoute(new Route('bar', 'bar'));
        $this->collection->addRoute(new Route('zeta', 'zeta'));
        $this->collection->addRoute(new Route('alpha', 'alpha'));

        $this->assertFalse($this->collection->match(array('bogus', 'input')));
    }
}
