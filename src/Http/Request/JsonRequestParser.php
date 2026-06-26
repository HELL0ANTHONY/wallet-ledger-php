<?php

declare(strict_types=1);

namespace WalletLedger\Http\Request;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;

final readonly class JsonRequestParser
{
    /**
     *
     * @throws JsonException
     * @return array<string, mixed>
     */
    public function parse(ServerRequestInterface $request): array
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('JSON request body must be an object.');
        }

        return $this->normalizePayload($decoded);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function string(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException("Field '{$key}' must be a non-empty string.");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function int(array $payload, string $key): int
    {
        $value = $payload[$key] ?? null;
        if (!is_int($value)) {
            throw new InvalidArgumentException("Field '{$key}' must be an integer.");
        }

        return $value;
    }

    public function requiredHeader(ServerRequestInterface $request, string $name): string
    {
        $value = $request->getHeaderLine($name);
        if ($value === '') {
            throw new InvalidArgumentException("Header '{$name}' is required.");
        }

        return $value;
    }

    /**
     * @param array<mixed> $decoded
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $decoded): array
    {
        $payload = [];

        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('JSON request body must be an object.');
            }

            $payload[$key] = $value;
        }

        return $payload;
    }
}
