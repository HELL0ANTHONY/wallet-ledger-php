<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\UseCase;

use WalletLedger\Application\Account\Repository\AccountRepository;
use WalletLedger\Application\Ledger\DTO\MutationOutput;
use WalletLedger\Application\Ledger\DTO\StoredIdempotentMutation;
use WalletLedger\Application\Ledger\DTO\TransferFundsInput;
use WalletLedger\Application\Ledger\Exception\IdempotencyConflict;
use WalletLedger\Application\Ledger\Mapper\LedgerEntryOutputMapper;
use WalletLedger\Application\Ledger\Repository\IdempotencyRepository;
use WalletLedger\Application\Ledger\Repository\LedgerEntryRepository;
use WalletLedger\Application\Shared\Clock\Clock;
use WalletLedger\Application\Shared\Hash\RequestHasher;
use WalletLedger\Application\Shared\Id\IdentifierGenerator;
use WalletLedger\Application\Shared\Transaction\TransactionManager;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Domain\Ledger\IdempotencyKey;
use WalletLedger\Domain\Ledger\LedgerEntryId;
use WalletLedger\Domain\Ledger\OperationId;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Money;

final readonly class TransferFunds
{
    public function __construct(
        private AccountRepository $accounts,
        private LedgerEntryRepository $ledgerEntries,
        private IdempotencyRepository $idempotency,
        private TransactionManager $transactionManager,
        private FinancialLedger $financialLedger,
        private IdentifierGenerator $identifierGenerator,
        private Clock $clock,
        private RequestHasher $requestHasher = new RequestHasher(),
        private LedgerEntryOutputMapper $ledgerEntryOutputMapper = new LedgerEntryOutputMapper(),
    ) {}

    public function __invoke(TransferFundsInput $input): MutationOutput
    {
        $idempotencyKey = new IdempotencyKey($input->idempotencyKey);
        $requestHash = $this->requestHasher->hash([
            'amount' => $input->amount,
            'currency' => $input->currency,
            'from_account_id' => $input->fromAccountId,
            'to_account_id' => $input->toAccountId,
            'type' => 'transfer',
        ]);

        return $this->transactionManager->transactional(function () use ($input, $idempotencyKey, $requestHash): MutationOutput {
            $stored = $this->idempotency->find($idempotencyKey);
            if ($stored instanceof StoredIdempotentMutation) {
                if ($stored->requestHash !== $requestHash) {
                    throw IdempotencyConflict::forKey($idempotencyKey);
                }

                return $stored->output;
            }

            $fromAccount = $this->accounts->get(new AccountId($input->fromAccountId));
            $toAccount = $this->accounts->get(new AccountId($input->toAccountId));
            $operationId = new OperationId($this->identifierGenerator->generate('op'));

            $entries = $this->financialLedger->transfer(
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                debitEntryId: new LedgerEntryId($this->identifierGenerator->generate('led')),
                creditEntryId: new LedgerEntryId($this->identifierGenerator->generate('led')),
                operationId: $operationId,
                amount: Money::positive($input->amount, new Currency($input->currency)),
                createdAt: $this->clock->now(),
            );

            $this->accounts->save($fromAccount);
            $this->accounts->save($toAccount);
            $this->ledgerEntries->append($entries->debit);
            $this->ledgerEntries->append($entries->credit);

            $output = new MutationOutput(
                operationId: $operationId->value,
                balance: $entries->debit->balanceAfter->amount,
                currency: $entries->debit->balanceAfter->currency->code,
                ledgerEntries: [
                    $this->ledgerEntryOutputMapper->map($entries->debit),
                    $this->ledgerEntryOutputMapper->map($entries->credit),
                ],
            );

            $this->idempotency->save($idempotencyKey, new StoredIdempotentMutation($requestHash, $output));

            return $output;
        });
    }
}
