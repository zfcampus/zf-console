# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.0 - 2016-07-11

### Added

- [#28](https://github.com/zfcampus/zf-console/pull/28) adds the ability to
  provide an `Interop\Container\ContainerInterface` instance to
  `ZF\Console\Dispatcher` during instantiation; when present, `dispatch()` will
  attempt to look up command callables via the container. This allows simpler
  configuration:

  ```php
  $dispatch = new Dispatcher($container);

  $routes = [
      [
          'name' => 'hello',
          'handler' => HelloCommand::class,
      ],
  ];

  $app = new Application('App', 1.0, $routes, null, $dispatcher);
  ```

  (vs wrapping the handler in a closure.)
- [#29](https://github.com/zfcampus/zf-console/pull/29) adds the ability to
  disable output of the banner in two ways:

  ```php
  $application->setBannerDisabledForUserCommands(true);
  $application->setBanner(null);
  ```

  You may also now disable a previously enabled footer by passing a null
  value:

  ```php
  $application->setFooter(null);
  ```
- [#30](https://github.com/zfcampus/zf-console/pull/30) adds
  `ZF\Console\DispatcherInterface`, which defines the methods `map()`, `has()`,
  and `dispatch()`; `Dispatcher` now implements the interface. By providing an
  interface, consumers may now provide their own implementation when desired.
- [#35](https://github.com/zfcampus/zf-console/pull/35) adds support for v3
  components from Zend Framework, retaining backwards compatibility with v2
  releases.

### Deprecated

- Nothing.

### Removed

- [#35](https://github.com/zfcampus/zf-console/pull/35) removes support for PHP 5.5.

### Fixed

- [#34](https://github.com/zfcampus/zf-console/pull/34) updates the
  `ExceptionHandler` to allow handling either exceptions or PHP 7
  `Throwable`s.

## 1.2.1 - 2016-07-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#26](https://github.com/zfcampus/zf-console/pull/26) fixes the
  `Route::isMatched()` implementation to use the stored `$matches` property.
