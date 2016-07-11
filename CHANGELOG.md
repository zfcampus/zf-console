# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.0 - TBD

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

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#26](https://github.com/zfcampus/zf-console/pull/26) fixes the
  `Route::isMatched()` implementation to use the stored `$matches` property.
