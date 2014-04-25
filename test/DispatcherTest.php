<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Dispatcher;
use ZF\Console\Route;

class DispatcherTest extends TestCase
{
    public function setUp()
    {
        $this->route = $this->getMockBuilder('ZF\Console\Route')
            ->disableOriginalConstructor()
            ->getMock();

        $this->console = $this->getMock('Zend\Console\Adapter\AdapterInterface');

        $this->dispatcher = new Dispatcher();
    }

    public function testMapRaisesInvalidArgumentForEmptyStringCommand()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid command specified');
        $this->dispatcher->map('', 'trim');
    }

    public function testMapRaisesInvalidArgumentExceptionForInvalidCallable()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid command callback');
        $this->dispatcher->map('trim', 'boo bar baz');
    }

    public function testMapImplementsFluentInterface()
    {
        $this->assertSame($this->dispatcher, $this->dispatcher->map('trim', 'trim'));
    }

    public function testDispatchReturnsOneWhenRouteIsNotInMap()
    {
        $this->route->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('bogus'));

        $this->console->expects($this->any())
            ->method('writeLine');

        $this->assertEquals(1, $this->dispatcher->dispatch($this->route, $this->console));
    }

    public function testDispatchRaisesExceptionWhenStringClassIsNotInvokable()
    {
        $this->dispatcher->map('test', 'SplStack');
        $this->route->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test'));

        $this->setExpectedException('RuntimeException', 'Invalid command class');
        $this->dispatcher->dispatch($this->route, $this->console);
    }

    public function testDispatchInvokesCallableWithRouteAndConsole()
    {
        $expectedRoute = $this->route;
        $expectedConsole = $this->console;
        $phpunit = $this;
        $this->dispatcher->map('test', function ($route, $console) use ($expectedRoute, $expectedConsole, $phpunit) {
            $phpunit->assertSame($expectedRoute, $route);
            $phpunit->assertSame($expectedConsole, $console);
        });

        $this->route->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test'));
        $this->dispatcher->dispatch($this->route, $this->console);
    }

    public function testDispatchReturnsCallableReturnIntegerOnSuccess()
    {
        $this->dispatcher->map('test', function ($route, $console) {
            return 2;
        });

        $this->route->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test'));
        $this->assertEquals(2, $this->dispatcher->dispatch($this->route, $this->console));
    }
}
