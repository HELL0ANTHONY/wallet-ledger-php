<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class StoredIdempotentMutation
{
    public function __construct(
        public string $requestHash,
        public MutationOutput $output,
    ) {}
}
