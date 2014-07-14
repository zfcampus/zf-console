<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Console;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Application;
use ZF\Console\Dispatcher;
use ZF\Console\Route;

class ApplicationTest extends TestCase
{
    public function setUp()
    {
        $this->version = uniqid();
        $this->console = $this->getMock('Zend\Console\Adapter\AdapterInterface');
        $this->dispatcher = new Dispatcher();
        $this->application = new Application(
            'ZFConsoleApplication',
            $this->version,
            $this->getRoutes(),
            $this->console,
            $this->dispatcher
        );
    }

    public function getRoutes()
    {
        return array(
            array(
                'name'  => 'self-update',
                'route' => 'self-update',
                'description' => 'When executed via the Phar file, performs a self-update by querying
        the package repository. If successful, it will report the new version.',
                'short_description' => 'Perform a self-update of the script',
            ),
            array(
                'name' => 'build',
                'route' => 'build <package> [--target=]',
                'description' => 'Build a package, using <package> as the package filename, and --target
        as the application directory to be packaged.',
                'short_description' => 'Build a package',
                'options_descriptions' => array(
                    '<package>' => 'Package filename to build',
                    '--target'  => 'Name of the application directory to package; defaults to current working directory',
                ),
                'defaults' => array(
                    'target' => getcwd(), // default to current working directory
                ),
            ),
        );
    }

    public function testRunWithEmptyArgumentsShowsUsageMessage()
    {
        $this->console->expects($this->atLeastOnce())
            ->method('colorize');

        $this->console->expects($this->atLeastOnce())
            ->method('writeLine');

        $this->console->expects($this->atLeastOnce())
            ->method('write');

        $this->application->run(array());
    }

    public function testRunThatDoesNotMatchRoutesDisplaysUnmatchedRouteMessage()
    {
        $this->console->expects($this->at(0))
            ->method('write')
            ->with($this->stringContains('Unrecognized command:'));

        $this->application->run(array('should', 'not', 'match'));
    }

    public function testRunThatMatchesInvokesCallableForMatchedRoute()
    {
        $phpunit = $this;
        $this->dispatcher->map('self-update', function ($route, $console) use ($phpunit) {
            $phpunit->assertEquals('self-update', $route->getName());
            return 2;
        });

        $this->assertEquals(2, $this->application->run(array('self-update')));
    }

    public function testRunThatMatchesFirstArgumentToARouteButFailsRoutingDisplaysHelpMessageForRoute()
    {
        $this->console->expects($writeLineSpy = $this->any())
            ->method('writeLine');
        $this->console->expects($writeSpy = $this->any())
            ->method('write');
        $return = $this->application->run(array('build'));

        $this->assertEquals(1, $return);

        $writeLines = $writeLineSpy->getInvocations();
        $this->assertGreaterThanOrEqual(3, count($writeLines));
        $this->assertContains('Usage:', $writeLines[0]->toString());
        $this->assertContains('build ', $writeLines[1]->toString());

        $writes = $writeSpy->getInvocations();
        $this->assertGreaterThanOrEqual(2, count($writes));
        $this->assertContains('<package>', $writes[0]->toString());
        $this->assertContains('--target', $writes[1]->toString());
    }
}
