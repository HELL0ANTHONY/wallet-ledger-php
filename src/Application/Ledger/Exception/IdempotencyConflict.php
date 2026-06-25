<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\Exception;

use WalletLedger\Application\Shared\Exception\ApplicationException;
use WalletLedger\Domain\Ledger\IdempotencyKey;

final class IdempotencyConflict extends ApplicationException
{
    public static function forKey(IdempotencyKey $key): self
    {
        return new self("Idempotency key was already used with a different request: {$key->value}");
    }
}
