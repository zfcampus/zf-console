<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use ArrayIterator;
use Countable;
use DomainException;
use InvalidArgumentException;
use IteratorAggregate;
use Zend\Console\RouteMatcher\RouteMatcherInterface;
use Zend\Filter\Callback as CallbackFilter;
use Zend\Filter\FilterInterface;
use Zend\Validator\Callback as CallbackValidator;
use Zend\Validator\ValidatorInterface;

class RouteCollection implements Countable, IteratorAggregate, RouteMatcherInterface
{
    /**
     * @var array
     */
    protected $routes = array();

    /**
     * Implement Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->routes);
    }

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
        ksort($this->routes, defined('SORT_NATURAL') ? constant('SORT_NATURAL') : SORT_STRING);

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
            $spec['route'] = $spec['name'];
        }
        $routeString = $this->prependRouteWithCommand(
            $name,
            $spec['route'],
            array_key_exists('prepend_command_to_route', $spec) ? $spec['prepend_command_to_route'] : true
        );

        $constraints        = (isset($spec['constraints']) && is_array($spec['constraints']))
            ? $spec['constraints']
            : array();
        $defaults           = (isset($spec['defaults']) && is_array($spec['defaults']))
            ? $spec['defaults']
            : array();
        $aliases            = (isset($spec['aliases']) && is_array($spec['aliases']))
            ? $spec['aliases']
            : array();
        $filters            = (isset($spec['filters']) && is_array($spec['filters']))
            ? $spec['filters']
            : null;
        $validators         = (isset($spec['validators']) && is_array($spec['validators']))
            ? $spec['validators']
            : null;
        $description        = (isset($spec['description']) && is_string($spec['description']))
            ? $spec['description']
            : '';
        $shortDescription   = (isset($spec['short_description']) && is_string($spec['short_description']))
            ? $spec['short_description']
            : '';
        $optionsDescription = (isset($spec['options_descriptions']) && is_array($spec['options_descriptions']))
            ? $spec['options_descriptions']
            : array();

        $filters    = $this->prepareFilters($filters);
        $validators = $this->prepareValidators($validators);

        $route = new Route($name, $routeString, $constraints, $defaults, $aliases, $filters, $validators);
        $route->setDescription($description);
        $route->setShortDescription($shortDescription);
        $route->setOptionsDescription($optionsDescription);

        $this->addRoute($route);
        return $this;
    }

    /**
     * @param String $name
     * @return self
     * @throws DomainException if the provided route does not exist
     */
    public function removeRoute($name)
    {
        if (! isset($this->routes[$name])) {
            throw new DomainException(sprintf(
                'Failed removing route by name %s; the route by that name has not been registered',
                $name
            ));
        }

        unset($this->routes[$name]);
        return $this;
    }

    /**
     * Does the named route exist?
     *
     * @param string $name
     * @return bool
     */
    public function hasRoute($name)
    {
        return array_key_exists($name, $this->routes);
    }

    /**
     * Retrieve a named route
     *
     * @param  string $name
     * @return null|Route
     */
    public function getRoute($name)
    {
        if (! $this->hasRoute($name)) {
            return null;
        }
        return $this->routes[$name];
    }

    /**
     * Retrieve all route names
     *
     * @return array
     */
    public function getRouteNames()
    {
        return array_keys($this->routes);
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

    /**
     * Prepare filters
     *
     * If a filter is a class name, instantiate it.
     *
     * If a filter is a callback, casts to Callback filter.
     *
     * If the filter is not valid, raises an exception.
     *
     * @param  null|array $filters
     * @return array|null
     * @throws DomainException
     */
    protected function prepareFilters(array $filters = null)
    {
        if (null === $filters) {
            return null;
        }

        foreach ($filters as $name => $filter) {
            if (is_string($filter) && class_exists($filter)) {
                $filter = new $filter();
            }

            if ($filter instanceof FilterInterface) {
                $filters[$name] = $filter;
                continue;
            }

            if (is_callable($filter)) {
                $filters[$name] = new CallbackFilter($filter);
                continue;
            }

            throw new DomainException(sprintf(
                'Invalid filter provided for "%s"; expected Callable or Zend\Filter\FilterInterface, received "%s"',
                $name,
                $this->getType($filter)
            ));
        }

        return $filters;
    }

    /**
     * Prepare validators
     *
     * If a validator is a class name, instantiate it.
     *
     * If a validator is a callback, casts to Callback validator.
     *
     * If the validator is not valid, raises an exception.
     *
     * @param  array $validators
     * @return array|null
     * @throws DomainException
     */
    protected function prepareValidators(array $validators = null)
    {
        if (null === $validators) {
            return null;
        }

        foreach ($validators as $name => $validator) {
            if (is_string($validator) && class_exists($validator)) {
                $validator = new $validator();
            }

            if ($validator instanceof ValidatorInterface) {
                $validators[$name] = $validator;
                continue;
            }

            if (is_callable($validator)) {
                $validators[$name] = new CallbackValidator($validator);
                continue;
            }

            throw new DomainException(sprintf(
                'Invalid validator provided for "%s"; expected Callable or '
                . 'Zend\Validator\ValidatorInterface, received "%s"',
                $name,
                $this->getType($validator)
            ));
        }

        return $validators;
    }

    /**
     * Get an item's type, for error reporting
     *
     * @param  mixed $subject
     * @return string
     */
    protected function getType($subject)
    {
        switch (true) {
            case (is_object($subject)):
                $type = get_class($subject);
                break;
            case (is_string($subject)):
                $type = $subject;
                break;
            default:
                $type = gettype($subject);
                break;
        }
        return $type;
    }

    /**
     * Prepend the route with the command
     *
     * If the route does not start with the command already, and the
     * `prepend_command_to_route` flag has not been toggled off, then prepend
     * the command to the route and return it.
     *
     * @param string $command
     * @param string $route
     * @param bool $prependFlag
     * @return string
     */
    protected function prependRouteWithCommand($command, $route, $prependFlag)
    {
        if (true !== $prependFlag) {
            return $route;
        }

        if (preg_match('/^(?:' . preg_quote($command) . ')(?:\s|$)/', $route)) {
            return $route;
        }

        return sprintf('%s %s', $command, $route);
    }
}
