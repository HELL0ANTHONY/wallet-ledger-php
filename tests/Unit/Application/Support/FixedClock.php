<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Support;

use DateTimeImmutable;
use Override;
use WalletLedger\Application\Shared\Clock\Clock;

final readonly class FixedClock implements Clock
{
    public function __construct(
        private DateTimeImmutable $now = new DateTimeImmutable('2026-06-25T12:00:00+00:00'),
    ) {}

    #[Override]
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
