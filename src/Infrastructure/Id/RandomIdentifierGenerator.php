<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Id;

use Override;
use WalletLedger\Application\Shared\Id\IdentifierGenerator;

use function bin2hex;
use function random_bytes;

final readonly class RandomIdentifierGenerator implements IdentifierGenerator
{
    #[Override]
    public function generate(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(8));
    }
}
