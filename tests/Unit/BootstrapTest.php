<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WalletLedger\Domain\Shared\Exception\DomainException;

final class BootstrapTest extends TestCase
{
    public function test_domain_exception_namespace_is_autoloaded(): void
    {
        $exception = new class ('domain error') extends DomainException {};

        self::assertSame('domain error', $exception->getMessage());
    }
}
