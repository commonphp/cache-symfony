# Testing

## Required dev dependencies

This package uses PHPUnit 13 for its test suite. `composer.json` already lists:

- `phpunit/phpunit:^13.1`

If PHPUnit is missing from a clone, install it with:

```bash
composer require --dev phpunit/phpunit:^13.1
```

## Running tests

Install dependencies for this repository, then run PHPUnit from this repository root:

```bash
composer install
vendor/bin/phpunit
```

On Windows, use `vendor\bin\phpunit.bat`.

## Notes

The test suite covers the CommonPHP cache driver contract, Symfony reserved-key mapping, driver-scoped clearing through key prefixes, expired item removal, configuration validation, and factory-created adapters.

Filesystem adapter tests use a temporary directory under the operating system temp path.
