<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class ResolutionAuthorizer
{
    /**
     * @param array<string, mixed> $resolution
     */
    public static function authorizeHealth(array $resolution, HealthCheckResolver $resolver): void
    {
        $operation = self::requiredString($resolution, 'operation');
        $target = self::requiredString($resolution, 'requested_target');
        $expected = $resolver->resolve($operation, $target);
        self::assertCriticalFieldsMatch($resolution, $expected);
    }

    /**
     * @param array<string, mixed> $resolution
     */
    public static function authorizeComparison(array $resolution, ComparisonResolver $resolver): void
    {
        $operation = self::requiredString($resolution, 'operation');
        $target = self::requiredString($resolution, 'requested_target');
        $expected = $resolver->resolve($operation, $target);
        self::assertCriticalFieldsMatch($resolution, $expected);
    }

    /**
     * @param array<string, mixed> $actual
     * @param array<string, mixed> $expected
     */
    private static function assertCriticalFieldsMatch(array $actual, array $expected): void
    {
        $fields = [
            'resolution_version',
            'operation',
            'category',
            'requested_target',
            'target_role',
            'inventory',
            'playbook',
            'risk',
            'resolved_hosts',
            'resolved_host_count',
            'maximum_hosts',
            'ansible_limit',
            'expected_result_count',
            'component',
            'left_host',
            'right_host',
        ];

        foreach ($fields as $field) {
            if (($actual[$field] ?? null) !== ($expected[$field] ?? null)) {
                throw new \RuntimeException("Resolution field '{$field}' does not match the approved value.");
            }
        }
    }

    /**
     * @param array<string, mixed> $value
     */
    private static function requiredString(array $value, string $key): string
    {
        $candidate = $value[$key] ?? null;
        if (!is_string($candidate) || $candidate === '') {
            throw new \RuntimeException("Resolution field '{$key}' is missing or invalid.");
        }
        return $candidate;
    }
}
