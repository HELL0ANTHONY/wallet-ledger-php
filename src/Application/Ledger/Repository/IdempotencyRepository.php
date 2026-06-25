<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\Repository;

use WalletLedger\Application\Ledger\DTO\StoredIdempotentResponse;
use WalletLedger\Domain\Ledger\IdempotencyKey;

interface IdempotencyRepository
{
    public function find(IdempotencyKey $key): ?StoredIdempotentResponse;

    public function save(IdempotencyKey $key, StoredIdempotentResponse $response): void;
}
