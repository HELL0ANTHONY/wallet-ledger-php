<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Support;

use Override;
use WalletLedger\Application\Ledger\DTO\StoredIdempotentMutation;
use WalletLedger\Application\Ledger\Repository\IdempotencyRepository;
use WalletLedger\Domain\Ledger\IdempotencyKey;

final class InMemoryIdempotencyRepository implements IdempotencyRepository
{
    /**
     * @var array<string, StoredIdempotentMutation>
     */
    private array $mutations = [];

    #[Override]
    public function find(IdempotencyKey $key): ?StoredIdempotentMutation
    {
        return $this->mutations[$key->value] ?? null;
    }

    #[Override]
    public function save(IdempotencyKey $key, StoredIdempotentMutation $mutation): void
    {
        $this->mutations[$key->value] = $mutation;
    }
}
