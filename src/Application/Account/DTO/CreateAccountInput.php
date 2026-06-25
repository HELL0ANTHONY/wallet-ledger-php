<?php

declare(strict_types=1);

namespace WalletLedger\Application\Account\DTO;

final readonly class CreateAccountInput
{
    public function __construct(
        public string $accountId,
        public string $currency,
    ) {}
}
