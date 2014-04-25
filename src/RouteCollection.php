<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Zend\Console\RouteMatcher\RouteMatcherInterface;

class RouteCollection implements IteratorAggregate, RouteMatcherInterface
{
    protected $stack;

    protected $routes = array();

    /**
     * Implement IteratorAggregate
     * 
     * @return SplStack
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * @param Route $route 
     * @return self
     */
    public function addRoute(Route $route)
    {
        $name = $route->getName();
        if (isset($this->routes[$name])) {
            throw new DomainException(sprintf(
                'Failed adding route by name %s; a route by that name has already been registered',
                $name
            ));
        }

        $this->routes[$name] = $route;

        return $this;
    }

    /**
     * @param string $route
     * @param array $constraints 
     * @param array $defaults 
     * @param array $aliases 
     * @param null|array $filters 
     * @param null|array $validators 
     */
    public function addRouteSpec(array $spec)
    {
        if (! isset($spec['name'])) {
            throw new InvalidArgumentException('Route specification is missing a route name');
        }
        $name = $spec['name'];

        if (! isset($spec['route'])) {
            throw new InvalidArgumentException('Route specification is missing route');
        }
        $routeString = $spec['route'];

        $constraints        = (isset($spec['constraints']) && is_array($spec['constraints']))                   ? $spec['constraints']          : array();
        $defaults           = (isset($spec['defaults']) && is_array($spec['defaults']))                         ? $spec['defaults']             : array();
        $aliases            = (isset($spec['aliases']) && is_array($spec['aliases']))                           ? $spec['aliases']              : array();
        $filters            = (isset($spec['filters']) && is_array($spec['filters']))                           ? $spec['filters']              : null;
        $validators         = (isset($spec['validators']) && is_array($spec['validators']))                     ? $spec['validators']           : null;
        $description        = (isset($spec['description']) && is_string($spec['description']))                  ? $spec['description']          : '';
        $shortDescription   = (isset($spec['short_description']) && is_string($spec['short_description']))      ? $spec['short_description']    : '';
        $optionsDescription = (isset($spec['options_descriptions']) && is_array($spec['options_descriptions'])) ? $spec['options_descriptions'] : array();

        $route = new Route($name, $routeString, $constraints, $defaults, $aliases, $filters, $validators);
        $route->setDescription($description);
        $route->setShortDescription($shortDescription);
        $route->setOptionsDescription($optionsDescription);

        $this->addRoute($route);
        return $this;
    }

    /**
     * Determine if any route matches
     * 
     * @param  array|null $params 
     * @return false|Route
     */
    public function match($params)
    {
        if (! is_array($params) && null !== $params) {
            throw new InvalidArgumentException(sprintf(
                '%s expects an array of arguments (typically $argv) or a null value',
                __METHOD__
            ));
        }

        $params = (array) $params;

        foreach ($this as $route) {
            $matches = $route->match($params);
            if (is_array($matches)) {
                return $route;
            }
        }

        return false;
    }
}
