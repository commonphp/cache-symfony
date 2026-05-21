<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony;

use CommonPHP\Cache\CacheKey;
use CommonPHP\Drivers\Cache\Symfony\Exceptions\SymfonyCacheConfigurationException;
use Symfony\Component\Cache\CacheItem as SymfonyCacheItem;
use Throwable;

final readonly class SymfonyCacheKeyMapper
{
    private const int DEFAULT_SLUG_LENGTH = 80;

    private string $prefix;

    public function __construct(
        string $prefix = SymfonyCacheOptions::DEFAULT_KEY_PREFIX,
        private int $slugLength = self::DEFAULT_SLUG_LENGTH,
    ) {
        if ($slugLength < 1) {
            throw new SymfonyCacheConfigurationException('Symfony cache key slug length must be greater than zero.');
        }

        $this->prefix = $this->validatePrefix($prefix);
    }

    public function map(CacheKey|string $key): string
    {
        $key = CacheKey::from($key);
        $value = $key->value();
        $slug = $this->slug($value);

        return $this->prefix . $slug . '.' . hash('sha256', $value);
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    private function validatePrefix(string $prefix): string
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return '';
        }

        try {
            SymfonyCacheItem::validateKey($prefix . 'key');
        } catch (Throwable $exception) {
            throw new SymfonyCacheConfigurationException(
                'Symfony cache key prefix contains reserved PSR-6 characters.',
                previous: $exception,
            );
        }

        return $prefix;
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9_.~-]+/', '_', $value);
        $slug = trim((string) $slug, '._-~');

        if ($slug === '') {
            $slug = 'key';
        }

        if (strlen($slug) <= $this->slugLength) {
            return $slug;
        }

        return rtrim(substr($slug, 0, $this->slugLength), '._-~') ?: 'key';
    }
}
