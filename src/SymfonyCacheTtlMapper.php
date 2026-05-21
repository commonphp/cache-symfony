<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony;

use CommonPHP\Cache\CacheTtl;
use DateTimeImmutable;
use Psr\Cache\CacheItemInterface;

final class SymfonyCacheTtlMapper
{
    private const string FOREVER_EXPIRY = '@0';

    public function apply(CacheItemInterface $symfonyItem, CacheTtl $ttl): CacheItemInterface
    {
        if ($ttl->isForever()) {
            return $symfonyItem->expiresAt(new DateTimeImmutable(self::FOREVER_EXPIRY));
        }

        return $symfonyItem->expiresAt($ttl->expiresAt());
    }
}
