<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
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

    /**
     * In group 18 because it is a dependency
     *
     * @group 18
     */
    public function testInitialCountIsZero()
    {
        $this->assertEquals(0, count($this->collection));
    }

    /**
     * In group 18 because it is a dependency
     *
     * @group 18
     * @depends testInitialCountIsZero
     */
    public function testAddRouteAddsRouteToCollection()
    {
        $route = new Route('foo', 'foo bar');
        $this->collection->addRoute($route);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));
        return $this->collection;
    }

    /**
     * @depends testInitialCountIsZero
     */
    public function testCanAddRouteUsingSpecification()
    {
        $spec = [
            'name' => 'foo',
            'route' => 'foo [<bar>]',
            'description' => 'This is the full description',
            'short_description' => 'This is the short description',
            'options_descriptions' => [
                '<bar>' => 'The bar description',
            ],
            'constraints' => [
                'bar' => '/^[a-z0-9_.-]+$/',
            ],
            'defaults' => [
                'bar' => false,
            ],
            'aliases' => [
                'baz' => 'bar',
            ],
            'filters' => [],
            'validators' => [],
        ];
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

        $order = [];
        foreach ($this->collection as $route) {
            $order[] = $route->getName();
        }
        $this->assertEquals(['alpha', 'bar', 'foo', 'zeta'], $order);
    }

    public function testMatchReturnsFirstRouteToMatch()
    {
        $this->collection->addRoute(new Route('foo', 'foo bar'));
        $this->collection->addRoute(new Route('bar', 'bar'));
        $this->collection->addRoute(new Route('zeta', 'zeta'));
        $this->collection->addRoute(new Route('alpha', 'alpha'));

        $route = $this->collection->match(['foo', 'bar']);
        $this->assertInstanceOf('ZF\Console\Route', $route);
        $this->assertEquals('foo', $route->getName());
    }

    public function testMatchReturnsFalseIfNoRoutesMatch()
    {
        $this->collection->addRoute(new Route('foo', 'foo bar'));
        $this->collection->addRoute(new Route('bar', 'bar'));
        $this->collection->addRoute(new Route('zeta', 'zeta'));
        $this->collection->addRoute(new Route('alpha', 'alpha'));

        $this->assertFalse($this->collection->match(['bogus', 'input']));
    }

    public function filtersAndValidators()
    {
        return [
            'class_names' => [
                'Zend\Filter\StringToLower',
                'Zend\Validator\NotEmpty',
                'Zend\Filter\StringToLower',
                'Zend\Validator\NotEmpty',
            ],
            'functions' => [
                'trim',
                'assert',
                'Zend\Filter\Callback',
                'Zend\Validator\Callback',
            ],
            'closures' => [
                function () {
                },
                function () {
                },
                'Zend\Filter\Callback',
                'Zend\Validator\Callback',
            ],
        ];
    }

    /**
     * @dataProvider filtersAndValidators
     */
    public function testCanSpecifyFiltersAndValidatorsByClassName(
        $filter,
        $validator,
        $expectedFilter,
        $expectedValidator
    ) {
        $spec = [
            'name'  => 'foo',
            'route' => 'foo [<bar>]',
            'description' => 'This is the full description',
            'short_description' => 'This is the short description',
            'filters' => [
                'bar' => $filter,
            ],
            'validators' => [
                'bar' => $validator,
            ],
        ];
        $this->collection->addRouteSpec($spec);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));

        $route = $this->collection->getRoute('foo');

        $r = new ReflectionObject($route);

        $p = $r->getProperty('filters');
        $p->setAccessible(true);
        $filters = $p->getValue($route);
        $this->assertArrayHasKey('bar', $filters);
        $this->assertInstanceOf($expectedFilter, $filters['bar']);

        $p = $r->getProperty('validators');
        $p->setAccessible(true);
        $validators = $p->getValue($route);
        $this->assertArrayHasKey('bar', $validators);
        $this->assertInstanceOf($expectedValidator, $validators['bar']);
    }

    /**
     * @group 5
     */
    public function testAddingRouteSpecificationWithoutRouteUsesNameAsRoute()
    {
        $spec = [
            'name' => 'foo',
        ];
        $this->collection->addRouteSpec($spec);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));

        $route = $this->collection->getRoute('foo');
        $this->assertEquals('foo', $route->getRoute());
    }

    /**
     * @group 5
     */
    public function testRouteNamePrependedToCommandByDefaultWhenNameDoesNotMatchInitialRouteSequence()
    {
        $spec = [
            'name' => 'foo',
            'route' => '<bar>',
        ];
        $this->collection->addRouteSpec($spec);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));

        $route = $this->collection->getRoute('foo');
        $this->assertEquals('foo <bar>', $route->getRoute());
    }

    /**
     * @group 5
     */
    public function testRouteNameNotPrependedToCommandWhenNameMatchesInitialRouteSequence()
    {
        $spec = [
            'name' => 'foo',
            'route' => 'foo <bar>',
        ];
        $this->collection->addRouteSpec($spec);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));

        $route = $this->collection->getRoute('foo');
        $this->assertEquals('foo <bar>', $route->getRoute());
    }

    /**
     * @group 5
     */
    public function testRouteNameNotPrependedToCommandWhenFlagSaysNotTo()
    {
        $spec = [
            'name' => 'foo',
            'route' => '<bar>',
            'prepend_command_to_route' => false,
        ];
        $this->collection->addRouteSpec($spec);
        $this->assertEquals(1, count($this->collection));
        $this->assertTrue($this->collection->hasRoute('foo'));

        $route = $this->collection->getRoute('foo');
        $this->assertEquals('<bar>', $route->getRoute());
    }

    /**
     * @group 18
     * @depends testAddRouteAddsRouteToCollection
     */
    public function testCanRemoveAPreviouslyRegisteredRoute($collection)
    {
        $collection->removeRoute('foo');
        $this->assertFalse($collection->hasRoute('foo'));
    }

    /**
     * @group 18
     */
    public function testAttemptingToRemoveAnUnregisteredRouteRaisesAnException()
    {
        $this->setExpectedException('DomainException', 'registered');
        $this->collection->removeRoute('does-not-exist');
    }
}
