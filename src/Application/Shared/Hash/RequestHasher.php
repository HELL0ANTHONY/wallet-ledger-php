<?php

declare(strict_types=1);

namespace WalletLedger\Application\Shared\Hash;

use JsonException;

use function hash;
use function json_encode;

final readonly class RequestHasher
{
    /**
     * @param array<string, int|string> $values
     *
     * @throws JsonException
     */
    public function hash(array $values): string
    {
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }
}
