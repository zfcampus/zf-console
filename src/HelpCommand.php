<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Zend\Console\Adapter\AdapterInterface as Console;

class HelpCommand
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @param Router $router 
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param Route $route 
     * @param Console $console
     * @return int
     */
    public function __invoke(Route $route, Console $console)
    {
        $command = $route->getMatchedParam('command', null);
        $this->router->showUsageMessage($command);
        return 0;
    }
}
