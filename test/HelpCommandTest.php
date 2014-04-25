<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Application;
use ZF\Console\HelpCommand;
use ZF\Console\Route;

class HelpCommandTest extends TestCase
{
    public function setUp()
    {
        $this->application = $this->getMockBuilder('ZF\Console\Application')
            ->disableOriginalConstructor()
            ->getMock();

        $this->route = $this->getMockBuilder('ZF\Console\Route')
            ->disableOriginalConstructor()
            ->getMock();

        $this->console = $this->getMock('Zend\Console\Adapter\AdapterInterface');

        $this->command = new HelpCommand($this->application);
    }

    public function testCanInvokeWithoutAMatchedCommand()
    {
        $this->application->expects($this->once())
            ->method('showUsageMessage')
            ->with($this->equalTo(null));

        $this->route->expects($this->once())
            ->method('getMatchedParam')
            ->with(
                $this->equalTo('command'),
                $this->equalTo(null)
            )
            ->will($this->returnValue(null));

        $this->assertEquals(0, $this->command->__invoke($this->route, $this->console));
    }

    public function testCanInvokeWithAMatchedCommand()
    {
        $this->application->expects($this->once())
            ->method('showUsageMessage')
            ->with($this->equalTo('foo'));

        $this->route->expects($this->once())
            ->method('getMatchedParam')
            ->with(
                $this->equalTo('command'),
                $this->equalTo(null)
            )
            ->will($this->returnValue('foo'));

        $this->assertEquals(0, $this->command->__invoke($this->route, $this->console));
    }
}
