<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony;

use CommonPHP\Cache\CacheItem;
use CommonPHP\Cache\CacheKey;
use CommonPHP\Cache\Contracts\AbstractCacheDriver;
use CommonPHP\Drivers\Cache\Symfony\Exceptions\SymfonyCacheDriverException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Throwable;

final class SymfonyCacheDriver extends AbstractCacheDriver
{
    private AdapterInterface $adapter;

    private SymfonyCacheKeyMapper $keyMapper;

    private SymfonyCacheTtlMapper $ttlMapper;

    /**
     * @param array<string, mixed>|AdapterInterface|SymfonyCacheOptions|null $adapter
     */
    public function __construct(
        array|AdapterInterface|SymfonyCacheOptions|null $adapter = null,
        ?SymfonyCacheKeyMapper $keyMapper = null,
        ?SymfonyCacheTtlMapper $ttlMapper = null,
        ?SymfonyAdapterFactory $factory = null,
    ) {
        $options = null;

        if ($adapter instanceof AdapterInterface) {
            $this->adapter = $adapter;
        } else {
            $options = is_array($adapter)
                ? SymfonyCacheOptions::fromArray($adapter)
                : ($adapter ?? new SymfonyCacheOptions());

            $this->adapter = ($factory ?? new SymfonyAdapterFactory())->adapter($options);
        }

        $this->keyMapper = $keyMapper ?? new SymfonyCacheKeyMapper(
            $options?->keyPrefix ?? SymfonyCacheOptions::DEFAULT_KEY_PREFIX,
        );
        $this->ttlMapper = $ttlMapper ?? new SymfonyCacheTtlMapper();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getSymfonyAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getKeyMapper(): SymfonyCacheKeyMapper
    {
        return $this->keyMapper;
    }

    public function fetch(CacheKey|string $key): ?CacheItem
    {
        $cacheKey = $this->key($key);
        $symfonyKey = $this->keyMapper->map($cacheKey);

        try {
            $symfonyItem = $this->adapter->getItem($symfonyKey);
        } catch (Throwable $exception) {
            throw SymfonyCacheDriverException::forOperation('fetch', $cacheKey->value(), $exception);
        }

        if (!$symfonyItem->isHit()) {
            return null;
        }

        $item = $symfonyItem->get();

        if (!$item instanceof CacheItem) {
            throw SymfonyCacheDriverException::forUnexpectedValue($cacheKey->value(), $item);
        }

        if (!$item->key()->equals($cacheKey)) {
            throw SymfonyCacheDriverException::forMismatchedItem($cacheKey->value(), $item->keyName());
        }

        if ($item->isExpired()) {
            $this->delete($cacheKey);

            return null;
        }

        return $item;
    }

    public function store(CacheItem $item): void
    {
        if ($item->isExpired()) {
            $this->delete($item->key());

            return;
        }

        $symfonyKey = $this->keyMapper->map($item->key());

        try {
            $symfonyItem = $this->adapter->getItem($symfonyKey);
            $symfonyItem->set($item);
            $this->ttlMapper->apply($symfonyItem, $item->ttl());

            $saved = $this->adapter->save($symfonyItem);
        } catch (Throwable $exception) {
            throw SymfonyCacheDriverException::forOperation('store', $item->keyName(), $exception);
        }

        if (!$saved) {
            throw SymfonyCacheDriverException::forOperation('store', $item->keyName());
        }
    }

    public function delete(CacheKey|string $key): void
    {
        $cacheKey = $this->key($key);
        $symfonyKey = $this->keyMapper->map($cacheKey);

        try {
            $deleted = $this->adapter->deleteItem($symfonyKey);
        } catch (Throwable $exception) {
            throw SymfonyCacheDriverException::forOperation('delete', $cacheKey->value(), $exception);
        }

        if (!$deleted) {
            throw SymfonyCacheDriverException::forOperation('delete', $cacheKey->value());
        }
    }

    public function clear(): void
    {
        try {
            $cleared = $this->adapter->clear($this->keyMapper->prefix());
        } catch (Throwable $exception) {
            throw SymfonyCacheDriverException::forOperation('clear', previous: $exception);
        }

        if (!$cleared) {
            throw SymfonyCacheDriverException::forOperation('clear');
        }
    }

    public function has(CacheKey|string $key): bool
    {
        return $this->fetch($key) !== null;
    }
}
