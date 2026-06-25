<?php

declare(strict_types=1);

namespace WalletLedger\Application\Shared\Clock;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
