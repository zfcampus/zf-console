<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;

/**
 * Create and execute console applications.
 */
class Application
{
    /**
     * @var null|string|callable
     */
    protected $banner;

    /**
     * @var Console
     */
    protected $console;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var null|string|callable
     */
    protected $footer;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string
     */
    protected $version;

    /**
     * Initialize the application
     *
     * Creates a RouteCollection, populates it with the $routes provided, and
     * seeds a Router instance with both the RouteCollection and the Console
     * instance.
     *
     * Sets the banner to call showVersion().
     *
     * If no help command is defined, defines one.
     *
     * If no version command is defined, defines one.
     *
     * @param string $name Application name
     * @param string $version Application version
     * @param array $routes Routes/route specifications to use for the application
     * @param Console $console Console adapter to use within the application
     * @param Dispatcher $dispatcher Configured dispatcher mapping routes to callables
     */
    public function __construct(
        $name,
        $version,
        array $routes,
        Console $console,
        Dispatcher $dispatcher
    ) {
        $this->name       = $name;
        $this->version    = $version;
        $this->console    = $console;
        $this->dispatcher = $dispatcher;

        $routeCollection = new RouteCollection();
        $this->router = new Router($console, $routeCollection);
        $this->router->setRoutes($routes);

        $this->banner = array($this, 'showVersion');

        if (! $routeCollection->hasRoute('help')) {
            $this->setupHelpCommand($this->router, $routeCollection, $dispatcher);
        }

        if (! $routeCollection->hasRoute('version')) {
            $this->setupVersionCommand($routeCollection, $dispatcher);
        }
    }

    /**
     * Run the application
     *
     * If no arguments are provided, pulls them from $argv, stripping the
     * script argument first.
     *
     * If the argument list is empty, displays a usage message.
     *
     * If arguments are provided, but no routes match, displays a usage message
     * and returns a status of 1.
     *
     * Otherwise, attempts to dispatch the matched command, returning the
     * execution status.
     * 
     * @param array $args 
     * @return int
     */
    public function run(array $args = null)
    {
        if ($args === null) {
            global $argv;
            $args = array_slice($argv, 1);
        }

        if (empty($args)) {
            $this->showMessage($this->banner);
            $this->router->showUsageMessage();
            $this->showMessage($this->footer);
            return 0;
        }

        $route = $this->router->match($args);
        if (! $route instanceof Route) {
            return 1;
        }

        return $this->dispatcher->dispatch($route, $this->console);
    }

    /**
     * Display the application version
     * 
     * @param Console $console 
     * @return int
     */
    public function showVersion(Console $console)
    {
        $console->writeLine(
            $console->colorize($this->name . ',', Color::GREEN)
            . ' version '
            . $console->colorize($this->version, Color::BLUE)
        );
        $console->writeLine('');
        return 0;
    }


    /**
     * Display a message (banner or footer)
     *
     * If the message is a string and not callable, uses the composed console
     * instance to render it.
     *
     * If the message is a callable, calls it with the composed console
     * instance as an argument.
     * 
     * @param string|callable $message 
     */
    public function showMessage($messageOrCallable)
    {
        if (is_string($messageOrCallable) && ! is_callable($messageOrCallable)) {
            $this->console->writeLine($messageOrCallable);
            return;
        }

        if (is_callable($messageOrCallable)) {
            call_user_func($messageOrCallable, $this->console);
        }
    }

    /**
     * Set the banner to display.
     *
     * Used whenever the application is called without arguments.
     *
     * If the default help implementation is used, also displayed with help
     * messages.
     * 
     * @param string|callable $bannerOrCallable 
     * @return self
     */
    public function setBanner($bannerOrCallable)
    {
        if (! is_string($bannerOrCallable) && ! is_callable($bannerOrCallable)) {
            throw new InvalidArgumentException('Banner must be a string message or callable');
        }
        $this->banner = $bannerOrCallable;
        return $this;
    }

    /**
     * Set the footer to display.
     *
     * Used whenever the application is called without arguments.
     *
     * If the default help implementation is used, also displayed with help
     * messages.
     * 
     * @param string|callable $footerOrCallable 
     * @return self
     */
    public function setFooter($footerOrCallable)
    {
        if (! is_string($footerOrCallable) && ! is_callable($footerOrCallable)) {
            throw new InvalidArgumentException('Footer must be a string message or callable');
        }
        $this->footer = $footerOrCallable;
        return $this;
    }

    /**
     * Sets up the default help command
     *
     * Creates the route, and maps the command.
     * 
     * @param Router $router 
     * @param RouteCollection $routeCollection 
     * @param Dispatcher $dispatcher 
     */
    protected function setupHelpCommand(Router $router, RouteCollection $routeCollection, Dispatcher $dispatcher)
    {
        $help = new HelpCommand($this->router);
        $routeCollection->addRouteSpec(array(
            'name'                 => 'help',
            'route'                => 'help [<command>]',
            'description'          => "Display the help message for a given command.\n\nTo display the list of available commands, call the script or help with no arguments.",
            'short_description'    => 'Get help for individual commands',
            'options_descriptions' => array(
                'command' => 'Name of a command for which to get help',
            ),
            'constraints' => array(
                'command' => '/^[^\s]+$/',
            ),
            'defaults' => array(
                'help' => true,
            ),
        ));

        $self = $this;           // PHP < 5.4 compat
        $banner = $this->banner; // PHP < 5.4 compat
        $footer = $this->footer; // PHP < 5.4 compat
        $dispatcher->map('help', function ($route, $console) use ($help, $self, $banner, $footer) {
            $self->showMessage($banner);
            $help($route, $console);
            $self->showMessage($footer);
            return 0;
        });
    }

    /**
     * Sets up the default version command
     *
     * Creates the route, and maps the command.
     * 
     * @param RouteCollection $routeCollection 
     * @param Dispatcher $dispatcher 
     */
    protected function setupVersionCommand(RouteCollection $routeCollection, Dispatcher $dispatcher)
    {
        $routeCollection->addRouteSpec(array(
            'name' => 'version',
            'route' => '(--version|-v)',
            'description' => 'Display the version of the script.',
            'short_description' => 'Display the version of the script',
            'defaults' => array(
                'version' => true,
            ),
        ));

        $self = $this; // PHP < 5.4 compat
        $dispatcher->map('version', function ($route, $console) use ($self) {
            return $self->showVersion($console);
        });
    }
}
