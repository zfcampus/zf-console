<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Zend\Console\RouteMatcher\DefaultRouteMatcher;

class Route extends DefaultRouteMatcher
{
    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var null|array
     */
    protected $matches;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $optionsDescription = array();

    /**
     * @var string
     */
    protected $route;

    /**
     * @var string
     */
    protected $shortDescription = '';

    /**
     * @param string $name
     * @param string $route Route string to match
     * @param array $constraints Argument constraints (optional)
     * @param array $defaults Argument default values (optional)
     * @param null|array $filters Filters to use for specific arguments (optional)
     * @param null|array $validators Filters to use for specific arguments (optional)
     */
    public function __construct(
        $name,
        $route,
        array $constraints = array(),
        array $defaults = array(),
        array $aliases = array(),
        array $filters = null,
        array $validators = null
    ) {
        $this->name = $name;
        $this->route = $route;
        parent::__construct($route, $constraints, $defaults, $aliases, $filters, $validators);
    }

    /**
     * Override match()
     *
     * If matched, set the matches in the route
     *
     * @param array $params
     * @return array|null
     */
    public function match($params)
    {
        $matches = parent::match($params);

        if (is_array($matches)) {
            $this->matches = $matches;
        }

        return $matches;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $descriptions
     * @return self
     */
    public function setOptionsDescription(array $descriptions)
    {
        $this->optionsDescription = $descriptions;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptionsDescription()
    {
        return $this->optionsDescription;
    }

    /**
     * @param string $description
     * @return self
     */
    public function setShortDescription($description)
    {
        $this->shortDescription = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * @return bool
     */
    public function isMatched()
    {
        return is_array($matches);
    }

    /**
     * @return null|array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * Was the parameter matched?
     *
     * @param string $param
     * @return bool
     */
    public function matchedParam($param)
    {
        if (! is_array($this->matches)) {
            return false;
        }
        return array_key_exists($param, $this->matches);
    }

    /**
     * Retrieve a matched parameter
     *
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public function getMatchedParam($param, $default = null)
    {
        if (! $this->matchedParam($param)) {
            return $default;
        }
        return $this->matches[$param];
    }
}
