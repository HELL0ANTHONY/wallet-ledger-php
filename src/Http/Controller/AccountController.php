<?php

declare(strict_types=1);

namespace WalletLedger\Http\Controller;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WalletLedger\Application\Account\DTO\AccountOutput;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Account\DTO\GetAccountBalanceInput;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Account\UseCase\GetAccountBalance;
use WalletLedger\Application\Shared\Id\IdentifierGenerator;
use WalletLedger\Http\Request\JsonRequestParser;
use WalletLedger\Http\Response\JsonResponder;

final readonly class AccountController
{
    public function __construct(
        private CreateAccount $createAccount,
        private GetAccountBalance $getAccountBalance,
        private IdentifierGenerator $identifierGenerator,
        private JsonRequestParser $requestParser = new JsonRequestParser(),
        private JsonResponder $responder = new JsonResponder(),
    ) {}

    /**
     * @param array<string, string> $args
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($args);

        $payload = $this->requestParser->parse($request);
        $output = ($this->createAccount)(new CreateAccountInput(
            accountId: $this->identifierGenerator->generate('acc'),
            currency: $this->requestParser->string($payload, 'currency'),
        ));

        return $this->responder->respond($response, ['data' => $this->accountPayload($output)], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($request);

        $output = ($this->getAccountBalance)(new GetAccountBalanceInput(
            accountId: $this->routeArgument($args, 'id'),
        ));

        return $this->responder->respond($response, ['data' => $this->accountPayload($output)]);
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
     * @return array{account_id: string, balance: int, currency: string}
     */
    private function accountPayload(AccountOutput $output): array
    {
        return [
            'account_id' => $output->accountId,
            'balance' => $output->balance,
            'currency' => $output->currency,
        ];
    }
}
