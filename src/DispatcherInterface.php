<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;

interface DispatcherInterface
{
    /**
     * Map a command name to its handler.
     *
     * @param string $command
     * @param callable|string $command A callable command, or a string service
     *     or class name to use as a handler.
     * @return self Should implement a fluent interface.
     */
    public function map($command, $callable);

    /**
     * Does the dispatcher have a handler for the given command?
     *
     * @param string $command
     * @return bool
     */
    public function has($command);

    /**
     * Dispatch a routed command to its handler.
     *
     * @param Route $route
     * @param ConsoleAdapter $console
     * @return int The exit status code from the command.
     */
    public function dispatch(Route $route, ConsoleAdapter $console);
}
