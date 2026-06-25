<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Support;

use Override;
use WalletLedger\Application\Shared\Transaction\TransactionManager;

final class RecordingTransactionManager implements TransactionManager
{
    public int $transactions = 0;

    #[Override]
    public function transactional(callable $operation): mixed
    {
        ++$this->transactions;

        return $operation();
    }
}
