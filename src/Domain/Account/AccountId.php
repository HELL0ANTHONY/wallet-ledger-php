<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Account;

use WalletLedger\Domain\Shared\ValueObject\StringIdentifier;

final readonly class AccountId extends StringIdentifier {}
