<?php

declare(strict_types=1);

namespace WalletLedger\Http\Response;

use JsonException;
use Psr\Http\Message\ResponseInterface;

use function json_encode;

final readonly class JsonResponder
{
    /**
     * @param array<string, mixed> $payload
     *
     * @throws JsonException
     */
    public function respond(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
