<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

use function is_string;

final class EnvironmentReader
{
    /**
     * @return array<string, string>
     */
    public function readGlobals(): array
    {
        $values = [];

        foreach (EnvironmentVariables::required() as $name) {
            $values[$name] = $this->readGlobal($name);
        }

        return $values;
    }

    private function readGlobal(string $name): string
    {
        $envValue = $_ENV[$name] ?? null;
        if (is_string($envValue) && $envValue !== '') {
            return $envValue;
        }

        $serverValue = $_SERVER[$name] ?? null;
        if (is_string($serverValue) && $serverValue !== '') {
            return $serverValue;
        }

        $processValue = getenv($name);
        if (is_string($processValue) && $processValue !== '') {
            return $processValue;
        }

        throw InvalidConfigurationException::missing($name);
    }
}
