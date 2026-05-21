<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony;

use CommonPHP\Cache\CacheManager;
use CommonPHP\Drivers\Cache\Symfony\Exceptions\SymfonyCacheConfigurationException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Throwable;

final class SymfonyAdapterFactory
{
    public function create(?SymfonyCacheOptions $options = null): CacheManager
    {
        return new CacheManager($this->driver($options));
    }

    public function driver(?SymfonyCacheOptions $options = null): SymfonyCacheDriver
    {
        $options ??= new SymfonyCacheOptions();

        return new SymfonyCacheDriver(
            $this->adapter($options),
            new SymfonyCacheKeyMapper($options->keyPrefix),
        );
    }

    public function adapter(?SymfonyCacheOptions $options = null): AdapterInterface
    {
        $options ??= new SymfonyCacheOptions();

        try {
            return match ($options->adapter) {
                SymfonyCacheOptions::ADAPTER_ARRAY => new ArrayAdapter(
                    $options->defaultLifetime,
                    $options->storeSerialized,
                    $options->maxLifetime,
                    $options->maxItems,
                ),
                SymfonyCacheOptions::ADAPTER_FILESYSTEM => new FilesystemAdapter(
                    $options->namespace,
                    $options->defaultLifetime,
                    $options->directory,
                ),
                SymfonyCacheOptions::ADAPTER_PHP_FILES => new PhpFilesAdapter(
                    $options->namespace,
                    $options->defaultLifetime,
                    $options->directory,
                    $options->phpFilesAppendOnly,
                ),
            };
        } catch (Throwable $exception) {
            throw new SymfonyCacheConfigurationException(
                'Could not create Symfony cache adapter "' . $options->adapter . '".',
                previous: $exception,
            );
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function fromArray(array $config): CacheManager
    {
        return $this->create(SymfonyCacheOptions::fromArray($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public function driverFromArray(array $config): SymfonyCacheDriver
    {
        return $this->driver(SymfonyCacheOptions::fromArray($config));
    }

    /**
     * @param array<string, mixed> $config
     */
    public function adapterFromArray(array $config): AdapterInterface
    {
        return $this->adapter(SymfonyCacheOptions::fromArray($config));
    }

    public function array(int $defaultLifetime = 0): CacheManager
    {
        return $this->create(SymfonyCacheOptions::array(defaultLifetime: $defaultLifetime));
    }

    public function filesystem(?string $directory = null, string $namespace = ''): CacheManager
    {
        return $this->create(SymfonyCacheOptions::filesystem($directory, $namespace));
    }

    public function phpFiles(?string $directory = null, string $namespace = ''): CacheManager
    {
        return $this->create(SymfonyCacheOptions::phpFiles($directory, $namespace));
    }
}
