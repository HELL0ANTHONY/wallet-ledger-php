<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Support;

use Override;
use WalletLedger\Application\Shared\Id\IdentifierGenerator;

final class SequentialIdentifierGenerator implements IdentifierGenerator
{
    /**
     * @var array<string, int>
     */
    private array $counters = [];

    #[Override]
    public function generate(string $prefix): string
    {
        $this->counters[$prefix] = ($this->counters[$prefix] ?? 0) + 1;

        return "{$prefix}_{$this->counters[$prefix]}";
    }
}
