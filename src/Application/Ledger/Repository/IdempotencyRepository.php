<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\Repository;

use WalletLedger\Application\Ledger\DTO\StoredIdempotentMutation;
use WalletLedger\Domain\Ledger\IdempotencyKey;

interface IdempotencyRepository
{
    public function find(IdempotencyKey $key): ?StoredIdempotentMutation;

    public function save(IdempotencyKey $key, StoredIdempotentMutation $mutation): void;
}
