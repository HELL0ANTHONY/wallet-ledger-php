<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Persistence\SQLite;

use DateTimeImmutable;
use JsonException;
use Override;
use PDO;
use RuntimeException;
use WalletLedger\Application\Ledger\DTO\LedgerEntryOutput;
use WalletLedger\Application\Ledger\DTO\MutationOutput;
use WalletLedger\Application\Ledger\DTO\StoredIdempotentMutation;
use WalletLedger\Application\Ledger\Repository\IdempotencyRepository;
use WalletLedger\Domain\Ledger\IdempotencyKey;

use function is_array;
use function json_decode;
use function json_encode;

final readonly class SQLiteIdempotencyRepository implements IdempotencyRepository
{
    public function __construct(
        private PDO $pdo,
        private SQLiteRowReader $rowReader = new SQLiteRowReader(),
    ) {}

    /**
     * @throws JsonException
     */
    #[Override]
    public function find(IdempotencyKey $key): ?StoredIdempotentMutation
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
                SELECT request_hash, operation_id, balance, currency, ledger_entries_json
                FROM idempotency_keys
                WHERE key = :key
                SQL,
        );
        $statement->execute(['key' => $key->value]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        $ledgerEntries = $this->decodeLedgerEntries($this->rowReader->string($row, 'ledger_entries_json'));

        return new StoredIdempotentMutation(
            requestHash: $this->rowReader->string($row, 'request_hash'),
            output: new MutationOutput(
                operationId: $this->rowReader->string($row, 'operation_id'),
                balance: $this->rowReader->int($row, 'balance'),
                currency: $this->rowReader->string($row, 'currency'),
                ledgerEntries: $ledgerEntries,
            ),
        );
    }

    /**
     * @throws JsonException
     */
    #[Override]
    public function save(IdempotencyKey $key, StoredIdempotentMutation $mutation): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
                INSERT INTO idempotency_keys (
                    key,
                    request_hash,
                    operation_id,
                    balance,
                    currency,
                    ledger_entries_json,
                    created_at
                ) VALUES (
                    :key,
                    :request_hash,
                    :operation_id,
                    :balance,
                    :currency,
                    :ledger_entries_json,
                    :created_at
                )
                SQL,
        );

        $statement->execute([
            'key' => $key->value,
            'request_hash' => $mutation->requestHash,
            'operation_id' => $mutation->output->operationId,
            'balance' => $mutation->output->balance,
            'currency' => $mutation->output->currency,
            'ledger_entries_json' => json_encode($this->encodeLedgerEntries($mutation->output), JSON_THROW_ON_ERROR),
            'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    /**
     * @return list<array{
     *     ledgerEntryId: string,
     *     accountId: string,
     *     operationId: string,
     *     type: string,
     *     amount: int,
     *     currency: string,
     *     balanceAfter: int,
     *     createdAt: string
     * }>
     */
    private function encodeLedgerEntries(MutationOutput $output): array
    {
        $entries = [];

        foreach ($output->ledgerEntries as $entry) {
            $entries[] = [
                'ledgerEntryId' => $entry->ledgerEntryId,
                'accountId' => $entry->accountId,
                'operationId' => $entry->operationId,
                'type' => $entry->type,
                'amount' => $entry->amount,
                'currency' => $entry->currency,
                'balanceAfter' => $entry->balanceAfter,
                'createdAt' => $entry->createdAt,
            ];
        }

        return $entries;
    }

    /**
     *
     * @throws JsonException
     * @return list<LedgerEntryOutput>
     */
    private function decodeLedgerEntries(string $json): array
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid stored idempotency ledger entries.');
        }

        $entries = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                throw new RuntimeException('Invalid stored idempotency ledger entry.');
            }

            $entries[] = new LedgerEntryOutput(
                ledgerEntryId: $this->rowReader->string($entry, 'ledgerEntryId'),
                accountId: $this->rowReader->string($entry, 'accountId'),
                operationId: $this->rowReader->string($entry, 'operationId'),
                type: $this->rowReader->string($entry, 'type'),
                amount: $this->rowReader->int($entry, 'amount'),
                currency: $this->rowReader->string($entry, 'currency'),
                balanceAfter: $this->rowReader->int($entry, 'balanceAfter'),
                createdAt: $this->rowReader->string($entry, 'createdAt'),
            );
        }

        return $entries;
    }
}
