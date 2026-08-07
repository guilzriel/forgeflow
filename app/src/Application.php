<?php

declare(strict_types=1);

namespace ForgeFlow;

final class Application
{
    /**
     * @return array<string, string>
     */
    public function health(): array
    {
        return [
            'service' => 'forgeflow',
            'runtime' => 'php',
            'status' => 'healthy',
            'version' => $this->environmentValue(
                'FORGEFLOW_VERSION',
                'development'
            ),
        ];
    }

    private function environmentValue(string $name, string $default): string
    {
        $value = getenv($name);

        if ($value === false || trim($value) === '') {
            return $default;
        }

        return $value;
    }
}
