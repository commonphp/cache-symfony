# CommonPHP Symfony Cache Driver

Cache driver for CommonPHP that wraps Symfony Cache for driver-based cache storage.

## Requirements

- PHP `^8.5`
- `comphp/cache:^0.3`
- `symfony/cache`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/cache-symphony
```

## Usage

```php
<?php

// TODO: Write usage
```

## Driver Notes

This driver is intended to let CommonPHP Cache use Symfony Cache adapters while keeping the core cache package independent from Symfony-specific implementation details.

If this package is intended to wrap Symfony Cache, consider whether the package name should be `comphp/cache-symfony` before publishing.

## Error Handling

Cache adapter, read, write, delete, clear, and configuration failures should throw CommonPHP cache driver exceptions instead of returning ambiguous false values where the operation cannot be completed.

## Documentation

- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
