<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Shared\ValueObject;

use WalletLedger\Domain\Shared\Exception\InvalidIdentifier;

use function preg_match;

abstract readonly class StringIdentifier
{
    final public function __construct(
        public string $value,
    ) {
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,127}$/', $this->value) !== 1) {
            throw InvalidIdentifier::forValue($this->value);
        }
    }

    final public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }
}
