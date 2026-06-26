<?php

declare(strict_types=1);

namespace WalletLedger\Http\Controller;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WalletLedger\Application\Ledger\DTO\DepositFundsInput;
use WalletLedger\Application\Ledger\DTO\LedgerEntriesOutput;
use WalletLedger\Application\Ledger\DTO\LedgerEntryOutput;
use WalletLedger\Application\Ledger\DTO\ListAccountLedgerEntriesInput;
use WalletLedger\Application\Ledger\DTO\MutationOutput;
use WalletLedger\Application\Ledger\DTO\TransferFundsInput;
use WalletLedger\Application\Ledger\DTO\WithdrawFundsInput;
use WalletLedger\Application\Ledger\UseCase\DepositFunds;
use WalletLedger\Application\Ledger\UseCase\ListAccountLedgerEntries;
use WalletLedger\Application\Ledger\UseCase\TransferFunds;
use WalletLedger\Application\Ledger\UseCase\WithdrawFunds;
use WalletLedger\Http\Request\JsonRequestParser;
use WalletLedger\Http\Response\JsonResponder;

use function array_map;

final readonly class LedgerController
{
    public function __construct(
        private DepositFunds $depositFunds,
        private WithdrawFunds $withdrawFunds,
        private TransferFunds $transferFunds,
        private ListAccountLedgerEntries $listAccountLedgerEntries,
        private JsonRequestParser $requestParser = new JsonRequestParser(),
        private JsonResponder $responder = new JsonResponder(),
    ) {}

    /**
     * @param array<string, string> $args
     */
    public function deposit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $payload = $this->requestParser->parse($request);
        $output = ($this->depositFunds)(new DepositFundsInput(
            accountId: $this->routeArgument($args, 'id'),
            amount: $this->requestParser->int($payload, 'amount'),
            currency: $this->requestParser->string($payload, 'currency'),
            idempotencyKey: $this->requestParser->requiredHeader($request, 'Idempotency-Key'),
        ));

        return $this->responder->respond($response, ['data' => $this->mutationPayload($output)], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function withdraw(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $payload = $this->requestParser->parse($request);
        $output = ($this->withdrawFunds)(new WithdrawFundsInput(
            accountId: $this->routeArgument($args, 'id'),
            amount: $this->requestParser->int($payload, 'amount'),
            currency: $this->requestParser->string($payload, 'currency'),
            idempotencyKey: $this->requestParser->requiredHeader($request, 'Idempotency-Key'),
        ));

        return $this->responder->respond($response, ['data' => $this->mutationPayload($output)], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function transfer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($args);

        $payload = $this->requestParser->parse($request);
        $output = ($this->transferFunds)(new TransferFundsInput(
            fromAccountId: $this->requestParser->string($payload, 'from_account_id'),
            toAccountId: $this->requestParser->string($payload, 'to_account_id'),
            amount: $this->requestParser->int($payload, 'amount'),
            currency: $this->requestParser->string($payload, 'currency'),
            idempotencyKey: $this->requestParser->requiredHeader($request, 'Idempotency-Key'),
        ));

        return $this->responder->respond($response, ['data' => $this->mutationPayload($output)], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($request);

        $output = ($this->listAccountLedgerEntries)(new ListAccountLedgerEntriesInput(
            accountId: $this->routeArgument($args, 'id'),
        ));

        return $this->responder->respond($response, ['data' => $this->entriesPayload($output)]);
    }

    /**
     * @param array<string, string> $args
     */
    private function routeArgument(array $args, string $name): string
    {
        $value = $args[$name] ?? null;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("Route argument '{$name}' is required.");
        }

        return $value;
    }

    /**
     * @return array{operation_id: string, balance: int, currency: string, ledger_entries: list<array<string, int|string>>}
     */
    private function mutationPayload(MutationOutput $output): array
    {
        return [
            'operation_id' => $output->operationId,
            'balance' => $output->balance,
            'currency' => $output->currency,
            'ledger_entries' => array_map($this->entryPayload(...), $output->ledgerEntries),
        ];
    }

    /**
     * @return array{entries: list<array<string, int|string>>}
     */
    private function entriesPayload(LedgerEntriesOutput $output): array
    {
        return [
            'entries' => array_map($this->entryPayload(...), $output->entries),
        ];
    }

    /**
     * @return array{
     *     ledger_entry_id: string,
     *     account_id: string,
     *     operation_id: string,
     *     type: string,
     *     amount: int,
     *     currency: string,
     *     balance_after: int,
     *     created_at: string
     * }
     */
    private function entryPayload(LedgerEntryOutput $entry): array
    {
        return [
            'ledger_entry_id' => $entry->ledgerEntryId,
            'account_id' => $entry->accountId,
            'operation_id' => $entry->operationId,
            'type' => $entry->type,
            'amount' => $entry->amount,
            'currency' => $entry->currency,
            'balance_after' => $entry->balanceAfter,
            'created_at' => $entry->createdAt,
        ];
    }
}
