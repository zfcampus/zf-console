ZF\Console: Console Tool Helper
===============================

[![Build Status](https://travis-ci.org/zfcampus/zf-console.png)](https://travis-ci.org/zfcampus/zf-console)

Introduction
------------

`zf-console` provides functionality on top of `Zend\Console`, specifically a methodology for
creating standalone PHP console applications using `Zend\Console`'s `DefaultRouteMatcher`.
It includes built-in "help" and "version" commands, and colorization (via `Zend\Console`), as
well as support for shell autocompletion.

Requirements
------------
  
Please see the [composer.json](composer.json) file.

Installation
------------

Run the following `composer` command:

```console
$ composer require "zfcampus/zf-console:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-console": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Creating an application
-----------------------

Console applications written with `zf-console` consist of:

- Defining console routes
- Mapping route names to PHP callables
- Creating and running the application

### Defining console routes

Routes in `zf-console` are typically configuration driven. Each route is an associative array
consisting of the following members:

- **name** (required): The name of the route. Names MUST be unique across the application.
- **route** (optional): The "route", or console arguments, to match (more below); if not specified,
  **name** is utilized. Additionally, if the route does not start with **name**, **name** will be
  prepended to the route (unless you opt out of this feature).
- **description** (optional): A detailed help description for the given route.
- **short_description** (optional): A short help description for the given route, used in command
  summaries.
- **options_descriptions** (optional): An array of option name/description pairs, corresponding to
  the arguments the route matches.
- **constraints** (optional): An array of name/regex pairs to use when matching arguments,
  corresponding to the arguments in the route. If a regex fails for a given argument, the route will
  not match.
- **aliases** (optional): An array of alias/argument pairs; if an alias is provided in the
  arguments, it will be returned as the named argument on a successful match.
- **defaults** (optional): Default values to return on a successful match for arguments that were
  not matched.
- **filters** (optional): An array of name/`Zend\Filter\FilterInterface` pairs. The filter provided
  will be used to filter/normalize the named argument when matched.
- **validators** (optional): An array of name/`Zend\Validator\ValidatorInterface` pairs. The
  validator provided will be used to validate the named argument when matched; failure to validate
  will cause the route not to match.
- **handler** (optional): A PHP callable, or a class name of a class with no constructor arguments
  which is also invokable; if specified, and no command has been mapped in the `Dispatcher`, this
  handler will be used to handle the command when invoked.
- **prepend_command_to_route** (optional): A flag that, if specified, indicates whether or not the
  command name will be prepended to the route. Since this is the default behavior, only a value of
  boolean false makes sense here.

Alternately, you can create a `ZF\Console\Route` instance. The signature is similar:

```php
$route = new ZF\Console\Route(
    $name,
    $route,
    $constraints, // optional
    $defaults,    // optional
    $aliases,     // optional
    $filters,     // optional
    $validators   // optional
);
$route->setDescription($description);
$route->setShortDescription($shortDescription);
$route->setOptionsDescription($optionsDescription);
```

When defining routes, you will need to provide either an array or `Traversable` object of route
configuration arrays or `Route` instances (they can be mixed).

We suggest putting your routes in a configuration file:

```php
// config/routes.php

return array(
    array(
        'name'  => 'self-update',
        'description' => 'When executed via the Phar file, performs a self-update by querying
the package repository. If successful, it will report the new version.',
        'short_description' => 'Perform a self-update of the script',
    ),
    array(
        'name' => 'build',
        'route' => '<package> [--target=]',
        'description' => 'Build a package, using <package> as the package filename, and --target
as the application directory to be packaged.',
        'short_description' => 'Build a package',
        'options_descriptions' => array(
            '<package>' => 'Package filename to build',
            '--target'  => 'Name of the application directory to package; defaults to current working directory',
        ),
        'defaults' => array(
            'target' => getcwd(), // default to current working directory
        ),
        'handler' => 'My\Builder',
    ),
);
```

> #### On Routes
>
> `ZF\Console\Route` is an extension of `Zend\Console\RouteMatcher\DefaultRouteMatcher`, and follows
> its rules for route definitions and matching. In general, a route string will consist of:
>
> - Literal parameters (literal strings to match; e.g., `build`)
> - Literal flags (e.g., `--help`, `-h`, etc; flags do not have associated values)
> - Positional value parameters (named captures that do not use flags; e.g., `<email>`)
> - Value flag parameters (aka long options, _with_ associated values; e.g., '--target=')
>
> Most parameters may be made optional by surrounding them with brackets (e.g., `[--target=]`,
> `[<command>]`).
>
> For a full overview of how to create route specification strings, please review the [ZF2 console
> routes
> documentation](http://framework.zend.com/manual/2.3/en/modules/zend.console.routes.html).

> #### Route definitions
>
> Note that, by default, the route name will be prefixed to the `route` you pass. In the example
> above, the `build` route becomes `build <package> [--target=]`. If you wish to be explicit, you
> can include the command name in your route definition yourself, or pass the
> `prepend_command_to_route` flag with a boolean false value to disable prepending the command name.
>
> Prepending is done to make explicit the idea the mapping of the command name to the route -- which
> is particularly prudent when considering usage of the help system (which is command centric).

### Mapping routes to callables

In order to execute commands, you will need to map route names to code that will dispatch them.
`ZF\Console\Dispatcher` provides the ability to define such a map, via its `map()` method:

```php
$dispatcher = new ZF\Console\Dispatcher;
$dispatcher->map('some-command-name', $callable)
```

The `$callable` argument may be any PHP callable. Additionally, you may provide a string class name,
so long as that class can be instantiated without constructor arguments, and so long as it defines
an `__invoke()` method.

All callables should expect up to two arguments:

```php
function (\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console) {
}
```

Additionally, callables should return an integer status to use as the application's exit status; a
`0` indicates success, while anything else indicates a failure.

> #### Callables may be defined in route configuration
>
> As noted in the previous section, you can also provide the callable for handling the route via the
> `handler` key of your route configuration. The same rules apply to that argument as for the
> `map()` method.
>
> Any callables mapped directly to the `Dispatcher` instance will be preferred over those passed via
> configuration. 

### Creating and running the application

Creating the application consists of 

- Setting up or retrieving the list of routes
- Setting up the dispatch map
- Instantiating the application
- Running the application

For the following example, we'll assume that the classes `My\SelfUpdate` and `My\Build` are
autoloadable, and each define the method `__invoke()`.

```php
use My\SelfUpdate;
use Zend\Console\Console;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader

define('VERSION', '1.1.3');

$dispatcher = new Dispatcher();
$dispatcher->map('self-update', new SelfUpdate($version));
$dispatcher->map('build', 'My\Build');

$application = new Application(
    'Builder',
    VERSION,
    include __DIR__ . '/config/routes.php',
    Console::getInstance(),
    $dispatcher
);
$exit = $application->run();
exit($exit);
```

Features
--------

`zf-console` provides a number of features "out of the box." These include:

- Usage reporting
- Help message reporting
- Version reporting
- Shell autocompletion
- Exception handling

Usage reporting may be observed by executing an application with no arguments, or with only the
`help` argument:

```console
$ ./script.php
Builder, version 1.1.3

Available commands:

 autocomplete   Command autocompletion setup
 build          Build a package
 help           Get help for individual commands
 self-update    Perform a self-update of the script
 version        Display the version of the script
```

Help reporting for individual commands may be observed by executing `script help <command name>`:

```console
$ ./script.php help self-update
Builder, version 1.1.3

Usage:
 self-update

Help:
When executed via the Phar file, performs a self-update by querying
the package repository. If successful, it will report the new version.
```

> ### Name routes after the command
>
> We recommend naming routes after the command name. In part, this simplifies
> finding the matching route definition, but more importantly: if a user
> specifies the command, but does not specify valid arguments for it, the
> command will be used to provide a help usage message for that route.
>
> As an example, in the above, if I typed `script.php build` without any
> additional arguments, the usage message for the `build` command will be
> displayed, since the command and route name match.

Version reporting can be observed by executing `script --version` or `script -v`:

```console
$ ./script --version
Builder, version 1.1.3

```

You can override the default behavior in several ways.

First, you can override either of the `help` or `version` commands by mapping them in your
`Dispatcher` instance prior to creating your `Application` instance:

```php
$dispatcher->map('help', $myCustomHelpCommand);
$dispatcher->map('version', $myVersionCommand);
```

Second, you can set both custom banners and footers for the usage and help messages using the
`setBanner()` and/or `setFooter()` methods of the `Application` instance. Each accepts either a
string message, or a callable that to invoke in order to display the message; if using a callable,
it will be passed the `Console` instance as the sole argument.

```php
$application->setBanner('Some ASCI art for a banner!'); // string
$application->setBanner(function ($console) {           // callable
    $console->writeLine(
        $console->colorize('Builder', \Zend\Console\ColorInterface::BLUE)
        . ' - for building deployment packages'
    );
    $console->writeLine('');
    $console->writeLine('Usage:', \Zend\Console\ColorInterface::GREEN);
    $console->writeLine(' ' . basename(__FILE__) . ' command [options]');
    $console->writeLine('');
});

$application->setFooter('Copyright 2014 Zend Technologies');
```

### Autocompletion

Autocompletion is a useful feature of many login shells. `zf-console` provides autocompletion
support for bash, zsh, and any shell that understands autocompletion rules in a similar fashion.
Rules are generated per-script, using the `autocomplete` command:

```console
$ ./script autocomplete
```

Running this will output a shell script that you can save and add to your toolchain; the script
itself contains information on how to save it and add it to your shell. In most cases, this will
look something like:

```console
$ {script} autocomplete > > $HOME/bin/{script}_autocomplete.sh
$ echo "source \$HOME/bin/{script}_autocomplete.sh" > > $HOME/{your_shell_rc}
```

where `{script}` is the name of the command, and `{your_shell_rc}` is the location of your shell's
runtime configutation file (e.g., `.bashrc`, `.zshrc`).

Dispatcher Callables
--------------------

The `Dispatcher` will invoke the callable associated with a given route by calling it with two
arguments:

- The `ZF\Console\Route` instance that matched
- The `Zend\Console` adapter currently in use

In most cases, you will use the `Route` instance to gather arguments passed to the application, and
the `Console` instance to provide any feedback or to prompt for any additional information.

The `Route` instance contains several methods of interest:

- `getMatches()` will return an array of all named arguments matched.
- `matchedParam($name)` will tell you if a given argument was matched.
- `getMatchedParam($name, $default = null)` will return the value for the given argument as matched,
  and, if not matched, the `$default` value you provide.
- `getName()` will return the name of the route (which may be useful if you use the same callable
  for multiple routes).

Exception Handling
------------------

`zf-console` provides exception handling by default, via `ZF\Console\ExceptionHandler`. When your
console application raises an exception, this handler will provide a "pretty" view of the error,
instead of the full stack trace (unless you want to include the stack trace in your view!).

The default message looks like the following:

```console
======================================================================
   The application has thrown an exception!
======================================================================

 :className:
 :message
```

where `:className` will be filled with the exception's class name, and `message` will contain the
exception message, if any.

You may provide your own template if desired:

```php
$application->getExceptionHandler()->setMessageTemplate($template);
```

The following template variables are defined:

- `:className`
- `:message`
- `:code`
- `:file`
- `:line`
- `:stack`
- `:previous` (this is used to report previous exceptions in a trace)

If you want to provide your own exception handler, you may do so by providing any PHP callable to
the `setExceptionHandler()` method:

```php
$application->setExceptionHandler($handler);
```

### Debug mode

If you want normal PHP stack traces and error reporting, you can put the application into debug
mode:

```php
$application->setDebug(true);
```

Using zf-console in Zend Framework 2 Applications
-------------------------------------------------

While Zend Framework 2 integrates console functionality into the MVC, you may want to write scripts
that do not use the MVC. For instance, it may be easier to write an application-specific script
without going through the hoops of creating a controller, adding console configuration, etc.
However, you will likely still want access to services provided within modules, and also want the
ability to honor service and configuration overrides.

To do this, you will need to bootstrap your application first. We'll assume you're putting your
script in your application's `bin/` directory for this example.

```php
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

chdir(dirname(__DIR__));
require 'init_autoloader.php'; // grabs the Composer autoloader and/or ZF2 autoloader
$application = Zend\Mvc\Application::init(require 'config/application.config.php');
$services    = $application->getServiceManager();

$buildModel = $services->get('My\BuildModel');

$dispatcher = new Dispatcher();
$dispatcher->map('build', function ($route, $console) use ($buildModel) {
    $opts = $route->getMatches();
    $result = $buildModel->build($opts['package'], $opts['target']);
    if (! $result) {
        $console->writeLine('Error building package!', Color::WHITE, Color::RED);
        return 1;
    }

    $console->writeLine('Finished building package ' . $opts['package'], Color::GREEN);
    return 0;
});

$application = new Application(
    'Builder',
    VERSION,
    array(
        array(
            'name' => 'build',
            'route' => 'build <package> [--target=]',
            'description' => 'Build a package, using <package> as the package filename, and --target
    as the application directory to be packaged.',
            'short_description' => 'Build a package',
            'options_descriptions' => array(
                '<package>' => 'Package filename to build',
                '--target'  => 'Name of the application directory to package; defaults to current working directory',
            ),
            'defaults' => array(
                'target' => getcwd(), // default to current working directory
            ),
        ),
    ),
    Console::getInstance(),
    $dispatcher
);
$exit = $application->run();
exit($exit);
```

Essentially, you're calling `Zend\Mvc\Application::init()`, but not it's `run()` method. This
ensures all modules are bootstrapped, which means all configuration is loaded and merged, all
services are wired, and all listeners are attached. You then pull relevant services from the
`ServiceManager` and pass them to your console callbacks.

Best Practices
--------------

We recommend the following practices when creating applications using `zf-console`.

### Use `Zend\Console` to create output

Use `Zend\Console` to create any output you send. This ensures that the output works cross-platform
(including Unix-like systems and Windows). As examples:

```
$dispatcher->map('some-command', function ($route, $console) {
    $console->writeLine('Executing some-command!');
});
```

### Install your script via Composer

You can tell Composer to install your script in the `vendor/bin/` directory, making it trivial for
end-users to locate and execute your script within their own applications.

```JSON
{
    "require": {
        "php": ">=5.3.23",
        "zfcampus/zf-console": "~1.0-dev"
    },
    "bin": ["script.php"]
}
```

If you do this, be sure to name your script uniquely.

### Use filters or validators

`Zend\Console`'s RouteMatcher sub-component allows you to specify filters and/or validators for each
matched argument of a route. These let you provide normalization (filters) and more robust
validation logic when desired.

As an example, consider a common scenario of using comma-separated values for an argument; you could
split those into an array as follows:

```php
// config/routes.php

use Zend\Filter\Callback as CallbackFilter;

return array(
    array(
        'name' => 'filter',
        'route' => 'filter [--exclude=]',
        'default' => array(
            'exclude' => array(),
        ),
        'filters' => array(
            'exclude' => new CallbackFilter(function ($value) {
                if (! is_string($value)) {
                    return $value;
                }
                $exclude = explode(',', $value);
                array_walk($exclude, 'trim');
                return $exclude;
            }),
        ),
    )
);
```

Using filters and validators well, you can ensure that when your dispatch callbacks receive data, it
is already sanitized and ready to use.

#### Filters provided by zf-console

`zf-console` provides several filters for your convenience:

- `ZF\Console\Filter\Explode` allows you to specify a delimiter to use to "explode" a string value
  to an array of values. As an example:

  ```php
  // config/routes.php
  
  use ZF\Console\Filter\Explode as ExplodeFilter;
  
  return array(
      array(
          'name' => 'filter',
          'route' => 'filter [--exclude=]',
          'default' => array(
              'exclude' => array(),
          ),
          'filters' => array(
              'exclude' => new ExplodeFilter(','),
          ),
      )
  );
  ```

  The above would explode values provided to `--exclude` using a `,`; `--exclude=foo,bar,baz` would
  set `exclude` to `array('foo', 'bar', 'baz')`. By default, if no delimiter is provided, `,` is
  assumed.

- `ZF\Console\Filter\Json` allows you to specify a JSON-formatted string; it will then deserialize
  it to native PHP values.

  ```php
  // config/routes.php
  
  use ZF\Console\Filter\Json as JsonFilter;
  
  return array(
      array(
          'name' => 'filter',
          'route' => 'filter [--exclude=]',
          'default' => array(
              'exclude' => array(),
          ),
          'filters' => array(
              'exclude' => new JsonFilter(),
          ),
      )
  );
  ```

  The above would deserialize a JSON value provided to `--exclude`; `--exclude='["foo","bar","baz"]'` would
  set `exclude` to `array('foo', 'bar', 'baz')`.

- `ZF\Console\Filter\QueryString` allows you to specify a form-encoded string; it will then
  deserialize it to native PHP values.

  ```php
  // config/routes.php
  
  use ZF\Console\Filter\QueryString;
  
  return array(
      array(
          'name' => 'filter',
          'route' => 'filter [--exclude=]',
          'default' => array(
              'exclude' => array(),
          ),
          'filters' => array(
              'exclude' => new QueryString(),
          ),
      )
  );
  ```

  The above would deserialize a form-encoded value provided to `--exclude`;
  `--exclude='foo=bar&baz=bat'` would set `exclude` to `array('foo' => 'bar', 'baz' => 'bat')`.

Classes
-------

This library defines the following classes:

- `ZF\Console\Application`, which handles actual execution of the script, including usage reporting.
- `ZF\Console\Dispatcher`, which maps route names to PHP callables, and dispatches them when
  selected.
- `ZF\Console\HelpCommand`, which provides the default "help" logic for displaying command usage.
- `ZF\Console\Route`, an extension of `Zend\Console\RouteMatcher\DefaultRouteMatcher` that adds
  aggregation of route metadata, including the name and description.
- `ZF\Console\RouteCollection`, which implements `Zend\Console\RouteMatcher\RouteMatcherInterface`,
  aggregates `ZF\Console\Route` instances, and performs route matching.
- `ZF\Console\Filter\Explode`, which implements `Zend\Filter\FilterInterface`, and which [is
  described above](#filters-provided-by-zf-console).
- `ZF\Console\Filter\Json`, which implements `Zend\Filter\FilterInterface`, and which [is
  described above](#filters-provided-by-zf-console).
- `ZF\Console\Filter\QueryString`, which implements `Zend\Filter\FilterInterface`, and which [is
  described above](#filters-provided-by-zf-console).
