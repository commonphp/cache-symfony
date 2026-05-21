<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony\Exceptions;

use CommonPHP\Cache\Exceptions\CacheDriverException;
use Throwable;

class SymfonyCacheDriverException extends CacheDriverException
{
    public static function forOperation(string $operation, ?string $key = null, ?Throwable $previous = null): self
    {
        $target = $key === null ? '' : ' for key "' . $key . '"';

        return new self('Symfony cache operation "' . $operation . '" failed' . $target . '.', previous: $previous);
    }

    public static function forUnexpectedValue(string $key, mixed $value): self
    {
        return new self(
            'Symfony cache item for key "' . $key . '" did not contain a CommonPHP cache item; got '
                . get_debug_type($value) . '.',
        );
    }

    public static function forMismatchedItem(string $requestedKey, string $storedKey): self
    {
        return new self(
            'Symfony cache item for key "' . $requestedKey . '" contained CommonPHP cache item "'
                . $storedKey . '".',
        );
    }
}
