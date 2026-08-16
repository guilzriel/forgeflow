<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class ExecutionManifest
{
    /** @var list<string> */
    private const FORBIDDEN_KEY_PARTS = [
        'password',
        'secret',
        'token',
        'private_key',
        'credential',
        'authorization',
    ];

    /**
     * @param array<string, mixed> $resolution
     * @param array<string, string> $origin
     * @return array{
     *   manifest_version:int,
     *   execution_id:string,
     *   status:string,
     *   operation:string,
     *   requested_target:string,
     *   resolved_hosts:list<string>,
     *   resolved_host_count:int,
     *   playbook:string,
     *   inventory:string,
     *   risk:string,
     *   source_revision:string,
     *   source_ref:string,
     *   workflow:string,
     *   run_id:string,
     *   run_attempt:string,
     *   requested_by:string,
     *   created_at:string
     * }
     */
    public static function build(array $resolution, array $origin): array
    {
        $manifest = [
            'manifest_version' => 1,
            'execution_id' => self::uuidV4(),
            'status' => 'planned',
            'operation' => self::requireString($resolution, 'operation'),
            'requested_target' => self::requireString($resolution, 'requested_target'),
            'resolved_hosts' => self::requireStringList($resolution, 'resolved_hosts'),
            'resolved_host_count' => self::requireInt($resolution, 'resolved_host_count'),
            'playbook' => self::requireString($resolution, 'playbook'),
            'inventory' => self::requireString($resolution, 'inventory'),
            'risk' => self::requireString($resolution, 'risk'),
            'source_revision' => $origin['source_revision'] ?? 'local',
            'source_ref' => $origin['source_ref'] ?? 'local',
            'workflow' => $origin['workflow'] ?? 'local',
            'run_id' => $origin['run_id'] ?? 'local',
            'run_attempt' => $origin['run_attempt'] ?? '1',
            'requested_by' => $origin['requested_by'] ?? 'local',
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        self::assertSecretFree($manifest);

        return $manifest;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    public static function assertSecretFree(array $value): void
    {
        foreach ($value as $key => $child) {
            if (is_string($key)) {
                $normalized = strtolower(str_replace(['-', ' '], '_', $key));

                foreach (self::FORBIDDEN_KEY_PARTS as $forbidden) {
                    if (str_contains($normalized, $forbidden)) {
                        throw new \RuntimeException("Manifest contains forbidden key: {$key}");
                    }
                }
            }

            if (is_array($child)) {
                /** @var array<array-key, mixed> $child */
                self::assertSecretFree($child);
            }
        }
    }

    /**
     * @param array<string, mixed> $source
     */
    private static function requireString(array $source, string $key): string
    {
        $value = $source[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw new \RuntimeException("Resolution field {$key} must be a non-empty string.");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $source
     */
    private static function requireInt(array $source, string $key): int
    {
        $value = $source[$key] ?? null;

        if (!is_int($value)) {
            throw new \RuntimeException("Resolution field {$key} must be an integer.");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $source
     * @return list<string>
     */
    private static function requireStringList(array $source, string $key): array
    {
        $value = $source[$key] ?? null;

        if (!is_array($value) || !array_is_list($value)) {
            throw new \RuntimeException("Resolution field {$key} must be a list.");
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw new \RuntimeException("Resolution field {$key} contains an invalid value.");
            }
            $result[] = $item;
        }

        return $result;
    }

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
