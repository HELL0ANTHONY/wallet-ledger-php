<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Database;

use Override;
use PDO;
use Throwable;
use WalletLedger\Application\Shared\Transaction\TransactionManager;

final readonly class PdoTransactionManager implements TransactionManager
{
    public function __construct(
        private PDO $pdo,
    ) {}

    #[Override]
    public function transactional(callable $operation): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $operation();
        }

        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $throwable) {
            $this->pdo->rollBack();

            throw $throwable;
        }
    }
}
