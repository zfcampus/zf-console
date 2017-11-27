<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Dispatcher;
use ZF\Console\Route;
use ZFTest\Console\TestAsset\FooCommand;

class DispatcherTest extends TestCase
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function setUp()
    {
        $this->route = $this->getMockBuilder(Route::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->console = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $this->dispatcher = new Dispatcher();
    }

    public function testMapRaisesInvalidArgumentForEmptyStringCommand()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid command specified');
        $this->dispatcher->map('', 'trim');
    }

    public function testMapRaisesInvalidArgumentExceptionForInvalidCallable()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid command callback');
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

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Invalid command class');
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

    public function testDispatchCanPullCommandFromContainer()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(FooCommand::class)->willReturn(true);
        $container->get(FooCommand::class)->willReturn(new FooCommand());

        $route = $this->prophesize(Route::class);
        $route->getName()->willReturn('foobar');

        $console = $this->prophesize(AdapterInterface::class);

        $dispatcher = new Dispatcher($container->reveal());
        $dispatcher->map('foobar', FooCommand::class);
        $dispatcher->dispatch($route->reveal(), $console->reveal());
    }
}
