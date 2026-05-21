# CommonPHP Symfony Cache Driver

Cache driver for CommonPHP that wraps Symfony Cache for driver-based cache storage.

## Requirements

- PHP `^8.5`
- `comphp/cache:^0.3`
- `symfony/cache`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/cache-symfony-cache
```

## Usage

```php
<?php

use CommonPHP\Cache\CacheManager;
use CommonPHP\Drivers\Cache\Symfony\SymfonyCacheDriver;
use CommonPHP\Drivers\Cache\Symfony\SymfonyCacheOptions;

$cache = new CacheManager(new SymfonyCacheDriver());

$cache->set('users.42', ['name' => 'Ada'], 300);

$user = $cache->get('users.42');

$filesystemCache = new CacheManager(new SymfonyCacheDriver(
    SymfonyCacheOptions::filesystem(
        directory: __DIR__ . '/var/cache',
        namespace: 'app_cache',
    ),
));
```

## Driver Notes

This driver lets CommonPHP Cache use Symfony Cache adapters while keeping the core cache package independent from Symfony-specific implementation details.

The driver stores CommonPHP `CacheItem` objects inside Symfony cache items. Cache keys are mapped to PSR-6-safe Symfony keys so CommonPHP keys can contain characters such as `/`, `{`, `}`, `(`, `)`, and `@`.

Supported built-in adapter options are `array`, `filesystem`, and `php_files`. You may also inject any Symfony `AdapterInterface` instance directly.

## Error Handling

Cache adapter, read, write, delete, clear, and configuration failures should throw CommonPHP cache driver exceptions instead of returning ambiguous false values where the operation cannot be completed.

## Documentation

- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
