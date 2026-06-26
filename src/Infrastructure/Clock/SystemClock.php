<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Clock;

use DateTimeImmutable;
use Override;
use WalletLedger\Application\Shared\Clock\Clock;

final readonly class SystemClock implements Clock
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
