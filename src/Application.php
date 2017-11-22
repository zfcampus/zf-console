<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use InvalidArgumentException;
use Traversable;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\Console as DefaultConsole;
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
     * Flag to specify if the application is in debug mode
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var callable
     */
    protected $exceptionHandler;

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
     * @var bool
     */
    protected $bannerDisabledForUserCommands = false;

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
     * @param DispatcherInterface $dispatcher Configured dispatcher mapping routes to callables
     */
    public function __construct(
        $name,
        $version,
        $routes,
        Console $console = null,
        DispatcherInterface $dispatcher = null
    ) {
        if (! is_array($routes) && ! $routes instanceof Traversable) {
            throw new InvalidArgumentException('Routes must be provided as an array or Traversable object');
        }

        $this->name       = $name;
        $this->version    = $version;

        if (null === $console) {
            $console = DefaultConsole::getInstance();
        }

        $this->console    = $console;

        if (null === $dispatcher) {
            $dispatcher = new Dispatcher();
        }

        $this->dispatcher = $dispatcher;

        $this->routeCollection = $routeCollection = new RouteCollection();
        $this->setRoutes($routes);

        $this->banner = [$this, 'showVersion'];

        if (! $routeCollection->hasRoute('help')) {
            $this->setupHelpCommand($routeCollection, $dispatcher);
        }

        if (! $routeCollection->hasRoute('version')) {
            $this->setupVersionCommand($routeCollection, $dispatcher);
        }

        if (! $routeCollection->hasRoute('autocomplete')) {
            $this->setupAutocompleteCommand($routeCollection, $dispatcher);
        }
    }

    /**
     * @return DispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
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
        $this->initializeExceptionHandler();
        $this->setProcessTitle();

        if ($args === null) {
            global $argv;
            $args = array_slice($argv, 1);
        }

        $result = $this->processRun($args);

        $this->showMessage($this->footer);

        return $result;
    }

    /**
     * Process run
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
    protected function processRun(array $args)
    {
        if (empty($args)) {
            $this->showMessage($this->banner);
            $this->showUsageMessage();
            return 0;
        }

        $route = $this->routeCollection->match($args);
        if (! $route instanceof Route) {
            $this->showMessage($this->banner);

            $name  = $args[0];
            $route = $this->routeCollection->getRoute($name);
            if (! $route instanceof Route) {
                $this->showUnmatchedRouteMessage($args);
                return 1;
            }

            $this->showUsageMessageForRoute($route, true);
            return 1;
        }

        if (! $this->bannerDisabledForUserCommands) {
            $this->showMessage($this->banner);
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
     * @param string|callable $messageOrCallable
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

        $maxSpaces = $this->calcMaxString($this->routeCollection->getRouteNames()) + 2;

        foreach ($this->routeCollection as $route) {
            if ($name === $route->getName()) {
                $this->showUsageMessageForRoute($route);
                return;
            }

            if ($name !== null) {
                continue;
            }

            $routeName = $route->getName();
            $spaces = $maxSpaces - strlen($routeName);
            $console->write(' ' . $routeName, Color::GREEN);
            $console->writeLine(str_repeat(' ', $spaces) . $route->getShortDescription());
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
     * @param null|string|callable $bannerOrCallable
     * @return self
     */
    public function setBanner($bannerOrCallable)
    {
        $this->validateMessage($bannerOrCallable);
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
     * @param null|string|callable $footerOrCallable
     * @return self
     */
    public function setFooter($footerOrCallable)
    {
        $this->validateMessage($footerOrCallable);
        $this->footer = $footerOrCallable;
        return $this;
    }

    /**
     * Sets the debug flag of the application
     *
     * @param boolean $flag
     *
     * @return $this
     */
    public function setDebug($flag)
    {
        $this->debug = (boolean) $flag;
        return $this;
    }

    /**
     * Sets exception handler to use the expection Message
     *
     * @param callable $handler
     * @return self
     */
    public function setExceptionHandler($handler)
    {
        if (! is_callable($handler)) {
            throw new InvalidArgumentException('Exception handler must be callable');
        }

        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * Gets the registered exception handler
     *
     * Lazy-instantiates an ExceptionHandler instance with the current console
     * instance if no handler has been specified.
     *
     * @return callable
     */
    public function getExceptionHandler()
    {
        if (! is_callable($this->exceptionHandler)) {
            $this->exceptionHandler = new ExceptionHandler($this->console);
        }
        return $this->exceptionHandler;
    }

    /**
     * Calculate the maximum string length for an array
     *
     * @param array $data
     *
     * @return int
     */
    protected function calcMaxString(array $data = [])
    {
        $maxLength = 0;

        foreach ($data as $name) {
            if (strlen($name) > $maxLength) {
                $maxLength = strlen($name);
            }
        }

        return $maxLength;
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
                $this->mapRouteHandler($route);
                continue;
            }
        }

        return $this;
    }

    /**
     * Remove a route by name
     *
     * @param String $name
     * @return self
     */
    public function removeRoute($name)
    {
        $this->routeCollection->removeRoute($name);
        return $this;
    }

    /**
     * Disables the banner for user commands. Still shows it before usage messages.
     *
     * @param bool $flag
     *
     * @return self
     */
    public function setBannerDisabledForUserCommands($flag = true)
    {
        $this->bannerDisabledForUserCommands = (bool) $flag;
        return $this;
    }

    /**
     * Whether or not to disable the banner in user commands. False by default.
     *
     * @return bool
     */
    public function isBannerDisabledForUserCommands()
    {
        return $this->bannerDisabledForUserCommands;
    }

    /**
     * Sets up the default help command
     *
     * Creates the route, and maps the command.
     *
     * @param RouteCollection $routeCollection
     * @param DispatcherInterface $dispatcher
     */
    protected function setupHelpCommand(RouteCollection $routeCollection, DispatcherInterface $dispatcher)
    {
        $help = new HelpCommand($this);
        $routeCollection->addRouteSpec([
            'name'                 => 'help',
            'route'                => '[<command>]',
            'description'          => "Display the help message for a given command.\n\n"
                                   . 'To display the list of available commands, '
                                   . 'call the script or help with no arguments.',
            'short_description'    => 'Get help for individual commands',
            'options_descriptions' => [
                'command' => 'Name of a command for which to get help',
            ],
            'constraints' => [
                'command' => '/^[^\s]+$/',
            ],
            'defaults' => [
                'help' => true,
            ],
        ]);

        $self = $this;           // PHP < 5.4 compat
        $banner = $this->banner; // PHP < 5.4 compat
        $footer = $this->footer; // PHP < 5.4 compat
        $dispatcher->map('help', function ($route, $console) use ($help, $self, $banner, $footer) {
            $help($route, $console);
            return 0;
        });
    }

    /**
     * Sets up the default version command
     *
     * Creates the route, and maps the command.
     *
     * @param RouteCollection $routeCollection
     * @param DispatcherInterface $dispatcher
     */
    protected function setupVersionCommand(RouteCollection $routeCollection, DispatcherInterface $dispatcher)
    {
        $routeCollection->addRouteSpec([
            'name' => 'version',
            'route' => '(--version|-v)',
            'description' => 'Display the version of the script.',
            'short_description' => 'Display the version of the script',
            'defaults' => [
                'version' => true,
            ],
            'prepend_command_to_route' => false,
        ]);

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
     * @param DispatcherInterface $dispatcher
     */
    protected function setupAutocompleteCommand(RouteCollection $routeCollection, DispatcherInterface $dispatcher)
    {
        $routeCollection->addRouteSpec([
                'name' => 'autocomplete',
                'description' => 'Shows how to activate autocompletion of this command for your login shell',
                'short_description' => 'Command autocompletion setup',
        ]);

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

        // Mac OS X does not support cli_set_process_title() due to security issues
        // Bug fix for issue https://github.com/zfcampus/zf-console/issues/21
        if (PHP_OS == 'Darwin') {
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

            $maxSpaces = $this->calcMaxString(array_keys($options)) + 2;

            foreach ($options as $name => $description) {
                $spaces = $maxSpaces - strlen($name);
                $console->write(' ' . $name, Color::GREEN);
                $console->writeLine(str_repeat(' ', $spaces) . $description);
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

    /**
     * Initialize the exception handler (if not in debug mode)
     */
    protected function initializeExceptionHandler()
    {
        if ($this->debug) {
            return;
        }

        set_exception_handler($this->getExceptionHandler());
    }

    /**
     * Map a route handler
     *
     * If a given route specification has a "handler" entry, and the dispatcher
     * does not currently have a handler for that command, map it.
     *
     * @param array $route
     */
    protected function mapRouteHandler(array $route)
    {
        if (! isset($route['handler'])) {
            return;
        }

        $command = $route['name'];
        if ($this->dispatcher->has($command)) {
            return;
        }

        $this->dispatcher->map($command, $route['handler']);
    }

    /**
     * @param mixed $stringOrCallable
     */
    protected function validateMessage($stringOrCallable)
    {
        if ($stringOrCallable !== null
            && ! is_string($stringOrCallable)
            && ! is_callable($stringOrCallable)
        ) {
            throw new InvalidArgumentException('Messages must be string or callable');
        }
    }
}
