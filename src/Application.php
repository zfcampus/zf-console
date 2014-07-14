<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use InvalidArgumentException;
use Traversable;
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
     * @var RouteCollection
     */
    protected $routeCollection;

    /**
     * @var string
     */
    protected $version;

    /**
     * The Message that will be used to display exception information
     * @var string
     */
    protected $exceptionMessage;

    /**
     * Flag to specify if the application is in debug mode
     * @var boolean
     */
    protected $debug = false;

    /**
     * Initialize the application
     *
     * Creates a RouteCollection and populates it with the $routes provided.
     *
     * Sets the banner to call showVersion().
     *
     * If no help command is defined, defines one.
     *
     * If no version command is defined, defines one.
     *
     * @param string $name Application name
     * @param string $version Application version
     * @param array|Traversable $routes Routes/route specifications to use for the application
     * @param Console $console Console adapter to use within the application
     * @param Dispatcher $dispatcher Configured dispatcher mapping routes to callables
     * @param string|callable message to be used for handling exceptions
     */
    public function __construct(
        $name,
        $version,
        $routes,
        Console $console,
        Dispatcher $dispatcher,
        $exceptionMessage = null
    ) {
        if (! is_array($routes) && ! $routes instanceof Traversable) {
            throw new InvalidArgumentException('Routes must be provided as an array or Traversable object');
        }

        $this->name       = $name;
        $this->version    = $version;
        $this->console    = $console;
        $this->dispatcher = $dispatcher;

        $this->routeCollection = $routeCollection = new RouteCollection();
        $this->setRoutes($routes);

        $this->banner = array($this, 'showVersion');

        if (! $routeCollection->hasRoute('help')) {
            $this->setupHelpCommand($routeCollection, $dispatcher);
        }

        if (! $routeCollection->hasRoute('version')) {
            $this->setupVersionCommand($routeCollection, $dispatcher);
        }

        if (! $routeCollection->hasRoute('autocomplete')) {
            $this->setupAutocompleteCommand($routeCollection, $dispatcher);
        }

        if($exceptionMessage === null) {
            // Set default exception Message
            $exceptionMessage = <<<EOT
======================================================================
   The application has thrown an exception!
======================================================================
 :className
 :message


EOT;

        }

        $this->setExceptionMessage($exceptionMessage);
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
        $this->setProcessTitle();

        if ($args === null) {
            global $argv;
            $args = array_slice($argv, 1);
        }

        if (empty($args)) {
            $this->showMessage($this->banner);
            $this->showUsageMessage();
            $this->showMessage($this->footer);
            return 0;
        }

        $route = $this->routeCollection->match($args);
        if (! $route instanceof Route) {
            $name  = count($args) ? $args[0] : false;
            $route = $this->routeCollection->getRoute($name);
            if (! $route instanceof Route) {
                $this->showUnmatchedRouteMessage($args);
                return 1;
            }

            $this->showUsageMessageForRoute($route, true);
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
     * Sets the debug flag of the application
     * @param boolean $flag
     */
    public function setDebug($flag)
    {
        $this->debug = (boolean)$flag;
        return $this;
    }

    /**
     * Sets exception handler to use the expection Message
     * @param string|callable $exceptionMessage
     * @return self
     */
    public function setExceptionMessage($exceptionMessage)
    {
        if (! is_string($exceptionMessage) && ! is_callable($exceptionMessage)) {
            throw new InvalidArgumentException('Exception message must be a string message or callable');
        }

        $this->exceptionMessage = $exceptionMessage;


        if($this->debug) {
            // in debug mode we don't set exception handler
            return $this;
        }

        if(is_callable($exceptionMessage)) {
            set_exception_handler($exceptionMessage);
        } else {
            set_exception_handler(array($this,'defaultExceptionHandler'));
        }

        return $this;
    }

    /**
     * Gets the exception Message that is used in the app
     * @return string|callable
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * Default Exception Handler
     * @param \Exception $exception
     */
    public function defaultExceptionHandler($exception)
    {
        $previous = '';
        $previousException = $exception->getPrevious();
        while($previousException) {
            $previous .= str_replace(
                    array(
                            ':className',
                            ':message',
                            ':code',
                            ':file',
                            ':line',
                            ':stack',
                    ),array(
                            get_class($previousException),
                            $previousException->getMessage(),
                            $previousException->getCode(),
                            $previousException->getFile(),
                            $previousException->getLine(),
                            $exception->getTraceAsString(),
                    ),
                    $this->previousMessage
            );
            $previousException = $previousException->getPrevious();
        }

        /* @var $exception \Exception */
        $message = str_replace(
                array(
                        ':className',
                        ':message',
                        ':code',
                        ':file',
                        ':line',
                        ':stack',
                        ':previous',
                ),array(
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode(),
                        $exception->getFile(),
                        $exception->getLine(),
                        $exception->getTraceAsString(),
                        $previous
                ),
                $this->exceptionMessage
        );

        $this->console->writeLine('Application exception: ', Color::RED);
        $this->console->write($message);
        exit($exception->getCode());
    }

    /**
     * Set routes to use
     *
     * Allows specifying an array of routes, which may be mixed Route instances or array
     * specifications for creating routes.
     *
     * @param array|Traversable $routes
     * @return self
     */
    protected function setRoutes($routes)
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
     * Sets up the default help command
     *
     * Creates the route, and maps the command.
     *
     * @param RouteCollection $routeCollection
     * @param Dispatcher $dispatcher
     */
    protected function setupHelpCommand(RouteCollection $routeCollection, Dispatcher $dispatcher)
    {
        $help = new HelpCommand($this);
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


    /**
     * Sets up the default autocomplete command
     *
     * Creates the route, and maps the command.
     *
     * @param RouteCollection $routeCollection
     * @param Dispatcher $dispatcher
     */
    protected function setupAutocompleteCommand(RouteCollection $routeCollection, Dispatcher $dispatcher)
    {
        $routeCollection->addRouteSpec(array(
                'name' => 'autocomplete',
                'route' => 'autocomplete',
                'description' => 'Shows how to activate autocompletion of this command for your login shell',
                'short_description' => 'Command autocompletion setup',
        ));

        $dispatcher->map('autocomplete', function ($route, $console) {
            ob_start();
            include __DIR__.'/../views/autocomplete.phtml';
            $content = ob_get_contents();
            ob_end_clean();

            return $console->write($content);
        });
    }

    /**
     * Set CLI process title (PHP versions >= 5.5)
     */
    protected function setProcessTitle()
    {
        if (version_compare(PHP_VERSION, '5.5', 'lt')) {
            return;
        }

        cli_set_process_title($this->name);
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

    /**
     * Display the usage message for an individual route
     *
     * @param Route $route
     */
    protected function showUsageMessageForRoute(Route $route, $log = false)
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
     * Show message indicating inability to match a route.
     *
     * @param array $args
     */
    protected function showUnmatchedRouteMessage(array $args)
    {
        $this->console->write('Unrecognized command: ', Color::RED);
        $this->console->writeLine(implode(' ', $args));
        $this->console->writeLine('');
        $this->showUsageMessage();
    }
}
