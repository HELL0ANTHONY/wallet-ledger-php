<?php

declare(strict_types=1);

namespace WalletLedger\Application\Shared\Id;

interface IdentifierGenerator
{
    public function generate(string $prefix): string;
}
