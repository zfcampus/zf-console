<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Zend\Console\ColorInterface as Color;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;

class Router
{
    protected $console;

    protected $routeCollection;

    /**
     * @param ConsoleAdapter $console 
     * @param RouteCollection $routeCollection 
     */
    function __construct(ConsoleAdapter $console, RouteCollection $routeCollection)
    {
        $this->console = $console;
        $this->routeCollection = $routeCollection;
    }

    public function match(array $args)
    {
        $route = $this->routeCollection->match($args);
        if (! $route instanceof Route) {
            if (! empty($args)) {
                $this->console->write('Unrecognized command: ', Color::RED);
                $this->console->writeLine(implode(' ', $args));
                $this->console->writeLine('');
            }

            $this->showUsageMessage();
            return false;
        }
        return $route;
    }

    /**
     * @return RouteCollection
     */
    public function getRouteCollection()
    {
        return $this->routeCollection;
    }

    /**
     * Set routes to use
     *
     * Allows specifying an array of routes, which may be mixed Route instances or array
     * specifications for creating routes.
     * 
     * @param array $routes 
     * @return self
     */
    public function setRoutes(array $routes)
    {
        foreach ($routes as $route) {
            if ($route instanceof Route) {
                $this->routeCollection->addRoute($route);
                continue;
            }

            if (is_array($route)) {
                $this->routeCollection->addRouteSpec($route);
                continue;
            }
        }

        return $this;
    }

    /**
     * Displays a usage message for the router
     *
     * If a route name is provided, usage for that route only will be displayed;
     * otherwise, the name/short description for each will be present.
     * 
     * @param null|string $name 
     */
    public function showUsageMessage($name = null)
    {
        $console = $this->console;

        if ($name === null) {
            $console->writeLine('Available commands:', Color::GREEN);
            $console->writeLine('');
        }

        foreach ($this->routeCollection as $route) {
            if ($name === $route->getName()) {
                $this->showUsageMessageForRoute($route);
                return;
            }

            if ($name !== null) {
                continue;
            }

            $routeName = $route->getName();
            $tabs = ceil(( 15 - strlen($routeName) ) / 8);
            $console->write(' ' . $routeName, Color::GREEN);
            $console->writeLine(str_repeat("\t", $tabs) . $route->getShortDescription());
        }

        if ($name) {
            $this->showUnrecognizedRouteMessage($name);
            return;
        }
    }

    /**
     * Display the usage message for an individual route
     * 
     * @param Route $route 
     */
    protected function showUsageMessageForRoute(Route $route)
    {
        $console = $this->console;
        $console->writeLine('Usage:', Color::GREEN);
        $console->writeLine(' ' . $route->getRoute());
        $console->writeLine('');

        $options = $route->getOptionsDescription();
        if (! empty($options)) {
            $console->writeLine('Arguments:', Color::GREEN);
            foreach ($options as $name => $description) {
                $tabs = ceil(( 15 - strlen($name) ) / 8);
                $console->write(' ' . $name, Color::GREEN);
                $console->writeLine(str_repeat("\t", $tabs) . $description);
            }
            $console->writeLine('');
        }

        $description = $route->getDescription();
        if (! empty($description)) {
            $console->writeLine('Help:', Color::GREEN);
            $console->writeLine('');
            $console->writeLine($description);
        }
    }

    /**
     * Display an error message indicating a route name was not recognized
     * 
     * @param string $name 
     */
    protected function showUnrecognizedRouteMessage($name)
    {
        $console = $this->console;
        $console->writeLine('');
        $console->writeLine(sprintf('Unrecognized command "%s"', $name), Color::WHITE, Color::RED);
        $console->writeLine('');
    }
}
