<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\UseCase;

use WalletLedger\Application\Ledger\DTO\LedgerEntriesOutput;
use WalletLedger\Application\Ledger\DTO\ListAccountLedgerEntriesInput;
use WalletLedger\Application\Ledger\Mapper\LedgerEntryOutputMapper;
use WalletLedger\Application\Ledger\Repository\LedgerEntryRepository;
use WalletLedger\Domain\Account\AccountId;

use function array_map;

final readonly class ListAccountLedgerEntries
{
    public function __construct(
        private LedgerEntryRepository $ledgerEntries,
        private LedgerEntryOutputMapper $ledgerEntryOutputMapper = new LedgerEntryOutputMapper(),
    ) {}

    public function __invoke(ListAccountLedgerEntriesInput $input): LedgerEntriesOutput
    {
        $entries = $this->ledgerEntries->listByAccount(new AccountId($input->accountId));

        return new LedgerEntriesOutput(
            entries: array_map($this->ledgerEntryOutputMapper->map(...), $entries),
        );
    }
}
