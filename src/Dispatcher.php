<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;
use Zend\Console\ColorInterface as Color;

class Dispatcher implements DispatcherInterface
{
    protected $commandMap = [];

    /**
     * Container from which to pull command services when dispatching.
     *
     * @var null|ContainerInterface
     */
    protected $container;

    /**
     * @param null|ContainerInterface $container Container from which to pull
     *     command services when dispatching.
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function map($command, $callable)
    {
        if (! is_string($command) || empty($command)) {
            throw new InvalidArgumentException('Invalid command specified; must be a non-empty string');
        }

        if (is_callable($callable)) {
            $this->commandMap[$command] = $callable;
            return $this;
        }

        if (! is_string($callable)) {
            throw new InvalidArgumentException(
                'Invalid command callback specified; must be callable or a string class or service name'
            );
        }

        if (class_exists($callable)) {
            $this->commandMap[$command] = $callable;
            return $this;
        }

        if (! $this->container || ! $this->container->has($callable)) {
            throw new InvalidArgumentException(
                'Invalid command callback specified; must be callable or a string class or service name'
            );
        }

        $this->commandMap[$command] = $callable;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has($command)
    {
        return isset($this->commandMap[$command]);
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(Route $route, ConsoleAdapter $console)
    {
        $name = $route->getName();
        if (! isset($this->commandMap[$name])) {
            $console->writeLine('');
            $console->writeLine(sprintf('Unhandled command "%s" invoked', $name), Color::WHITE, Color::RED);
            $console->writeLine('');
            $console->writeLine('The command does not have a registered handler.');
            return 1;
        }

        $callable = $this->commandMap[$name];

        if (! is_callable($callable) && is_string($callable)) {
            $callable = ($this->container && $this->container->has($callable))
                ? $this->container->get($callable)
                : new $callable();

            if (! is_callable($callable)) {
                throw new RuntimeException(
                    sprintf('Invalid command class specified for "%s"; class must be invokable', $name)
                );
            }
            $this->commandMap[$name] = $callable;
        }

        $return = call_user_func($callable, $route, $console);
        return (int) $return;
    }
}
