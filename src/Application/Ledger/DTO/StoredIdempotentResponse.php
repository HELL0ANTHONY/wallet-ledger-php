<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class StoredIdempotentResponse
{
    public function __construct(
        public int $responseCode,
        public string $responseBody,
    ) {}
}
