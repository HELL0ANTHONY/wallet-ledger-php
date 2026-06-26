<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Account\Exception;

use RuntimeException;
use WalletLedger\Domain\Account\AccountId;

final class AccountNotFound extends RuntimeException
{
    public static function forId(AccountId $id): self
    {
        return new self("Account not found: {$id->value}");
    }
}
