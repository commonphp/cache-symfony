<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony;

use CommonPHP\Drivers\Cache\Symfony\Exceptions\SymfonyCacheConfigurationException;

final readonly class SymfonyCacheOptions
{
    public const string ADAPTER_ARRAY = 'array';

    public const string ADAPTER_FILESYSTEM = 'filesystem';

    public const string ADAPTER_PHP_FILES = 'php_files';

    public const string DEFAULT_KEY_PREFIX = 'commonphp.';

    private const array SUPPORTED_ADAPTERS = [
        self::ADAPTER_ARRAY,
        self::ADAPTER_FILESYSTEM,
        self::ADAPTER_PHP_FILES,
    ];

    public string $adapter;

    public string $namespace;

    public int $defaultLifetime;

    public ?string $directory;

    public string $keyPrefix;

    public function __construct(
        string $adapter = self::ADAPTER_ARRAY,
        string $namespace = '',
        int $defaultLifetime = 0,
        ?string $directory = null,
        string $keyPrefix = self::DEFAULT_KEY_PREFIX,
        public bool $storeSerialized = true,
        public float $maxLifetime = 0.0,
        public int $maxItems = 0,
        public bool $phpFilesAppendOnly = false,
    ) {
        $this->adapter = self::normalizeAdapter($adapter);
        $this->namespace = trim($namespace);
        $this->defaultLifetime = $this->validateDefaultLifetime($defaultLifetime);
        $this->directory = $this->normalizeDirectory($directory);
        $this->keyPrefix = trim($keyPrefix);
        $this->validateArrayOptions();
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        return new self(
            adapter: self::stringOption($options, ['adapter', 'type'], self::ADAPTER_ARRAY),
            namespace: self::stringOption($options, ['namespace'], ''),
            defaultLifetime: self::intOption($options, ['defaultLifetime', 'default_lifetime'], 0),
            directory: self::nullableStringOption($options, ['directory', 'path']),
            keyPrefix: self::stringOption($options, ['keyPrefix', 'key_prefix'], self::DEFAULT_KEY_PREFIX),
            storeSerialized: self::boolOption($options, ['storeSerialized', 'store_serialized'], true),
            maxLifetime: self::floatOption($options, ['maxLifetime', 'max_lifetime'], 0.0),
            maxItems: self::intOption($options, ['maxItems', 'max_items'], 0),
            phpFilesAppendOnly: self::boolOption(
                $options,
                ['phpFilesAppendOnly', 'php_files_append_only', 'append_only'],
                false,
            ),
        );
    }

    public static function array(
        int $defaultLifetime = 0,
        string $keyPrefix = self::DEFAULT_KEY_PREFIX,
        bool $storeSerialized = true,
        float $maxLifetime = 0.0,
        int $maxItems = 0,
    ): self {
        return new self(
            adapter: self::ADAPTER_ARRAY,
            defaultLifetime: $defaultLifetime,
            keyPrefix: $keyPrefix,
            storeSerialized: $storeSerialized,
            maxLifetime: $maxLifetime,
            maxItems: $maxItems,
        );
    }

    public static function filesystem(
        ?string $directory = null,
        string $namespace = '',
        int $defaultLifetime = 0,
        string $keyPrefix = self::DEFAULT_KEY_PREFIX,
    ): self {
        return new self(
            adapter: self::ADAPTER_FILESYSTEM,
            namespace: $namespace,
            defaultLifetime: $defaultLifetime,
            directory: $directory,
            keyPrefix: $keyPrefix,
        );
    }

    public static function phpFiles(
        ?string $directory = null,
        string $namespace = '',
        int $defaultLifetime = 0,
        string $keyPrefix = self::DEFAULT_KEY_PREFIX,
        bool $appendOnly = false,
    ): self {
        return new self(
            adapter: self::ADAPTER_PHP_FILES,
            namespace: $namespace,
            defaultLifetime: $defaultLifetime,
            directory: $directory,
            keyPrefix: $keyPrefix,
            phpFilesAppendOnly: $appendOnly,
        );
    }

    private static function normalizeAdapter(string $adapter): string
    {
        $adapter = strtolower(str_replace('-', '_', trim($adapter)));

        $adapter = match ($adapter) {
            'file', 'files' => self::ADAPTER_FILESYSTEM,
            'phpfiles', 'php_file', 'phpfilesadapter' => self::ADAPTER_PHP_FILES,
            default => $adapter,
        };

        if (!in_array($adapter, self::SUPPORTED_ADAPTERS, true)) {
            throw new SymfonyCacheConfigurationException(
                'Unsupported Symfony cache adapter "' . $adapter . '".',
            );
        }

        return $adapter;
    }

    private function normalizeDirectory(?string $directory): ?string
    {
        if ($directory === null) {
            return null;
        }

        $directory = trim($directory);

        return $directory === '' ? null : $directory;
    }

    private function validateDefaultLifetime(int $defaultLifetime): int
    {
        if ($defaultLifetime < 0) {
            throw new SymfonyCacheConfigurationException('Symfony cache default lifetime cannot be negative.');
        }

        return $defaultLifetime;
    }

    private function validateArrayOptions(): void
    {
        if ($this->maxLifetime < 0) {
            throw new SymfonyCacheConfigurationException('Symfony array cache max lifetime cannot be negative.');
        }

        if ($this->maxItems < 0) {
            throw new SymfonyCacheConfigurationException('Symfony array cache max items cannot be negative.');
        }
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private static function value(array $options, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $options)) {
                return $options[$key];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private static function stringOption(array $options, array $keys, string $default): string
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_string($value)) {
            throw new SymfonyCacheConfigurationException('Symfony cache option "' . $keys[0] . '" must be a string.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private static function nullableStringOption(array $options, array $keys): ?string
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new SymfonyCacheConfigurationException(
                'Symfony cache option "' . $keys[0] . '" must be a string or null.',
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private static function intOption(array $options, array $keys, int $default): int
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_int($value)) {
            throw new SymfonyCacheConfigurationException(
                'Symfony cache option "' . $keys[0] . '" must be an integer.',
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private static function floatOption(array $options, array $keys, float $default): float
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_int($value) && !is_float($value)) {
            throw new SymfonyCacheConfigurationException('Symfony cache option "' . $keys[0] . '" must be numeric.');
        }

        return (float) $value;
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $keys
     */
    private static function boolOption(array $options, array $keys, bool $default): bool
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_bool($value)) {
            throw new SymfonyCacheConfigurationException('Symfony cache option "' . $keys[0] . '" must be a boolean.');
        }

        return $value;
    }
}
