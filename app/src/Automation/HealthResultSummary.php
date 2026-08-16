<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class HealthResultSummary
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function loadDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new \RuntimeException("Health result directory does not exist: {$directory}");
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($files, SORT_STRING);
        $results = [];

        foreach ($files as $file) {
            $result = JsonFile::read($file);

            if (!isset($result['check'], $result['component'], $result['host'], $result['status'])) {
                throw new \RuntimeException("Malformed health evidence file: {$file}");
            }

            $results[] = $result;
        }

        if ($results === []) {
            throw new \RuntimeException('No health evidence was produced.');
        }

        return $results;
    }

    /**
     * @param list<array<string, mixed>> $results
     * @return list<array<string, mixed>>
     */
    public static function enforceExpectedCount(array $results, int $expectedCount): array
    {
        if ($expectedCount < 1) {
            throw new \InvalidArgumentException('Expected health result count must be positive.');
        }

        $actualCount = count($results);
        if ($actualCount === $expectedCount) {
            return $results;
        }

        $results[] = [
            'check' => 'health.framework',
            'component' => 'framework',
            'host' => 'runner',
            'status' => 'FAIL',
            'message' => sprintf('Expected %d health results but found %d.', $expectedCount, $actualCount),
            'details' => [
                'expected_result_count' => $expectedCount,
                'actual_result_count' => $actualCount,
            ],
        ];

        return $results;
    }

    /**
     * @param list<array<string, mixed>> $results
     */
    public static function hasFailure(array $results): bool
    {
        foreach ($results as $result) {
            if (($result['status'] ?? null) === 'FAIL') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $results
     */
    public static function renderMarkdown(array $results, string $title = 'ForgeFlow Health Check'): string
    {
        $failed = self::hasFailure($results);
        $final = $failed ? 'FAIL' : 'PASS';

        /** @var array<string, array{PASS:int, FAIL:int, SKIP:int}> $counts */
        $counts = [];

        foreach ($results as $result) {
            $component = self::stringField($result, 'component', 'unknown');

            if (!isset($counts[$component])) {
                $counts[$component] = ['PASS' => 0, 'FAIL' => 0, 'SKIP' => 0];
            }

            $status = self::stringField($result, 'status', 'FAIL');

            if ($status === 'PASS') {
                $counts[$component]['PASS']++;
            } elseif ($status === 'SKIP') {
                $counts[$component]['SKIP']++;
            } else {
                $counts[$component]['FAIL']++;
            }
        }

        ksort($counts, SORT_STRING);

        $lines = [
            "# {$title}",
            '',
            "**Final result: {$final}**",
            '',
            '| Component | Passed | Failed | Skipped |',
            '|---|---:|---:|---:|',
        ];

        foreach ($counts as $component => $componentCounts) {
            $lines[] = sprintf(
                '| %s | %d | %d | %d |',
                $component,
                $componentCounts['PASS'],
                $componentCounts['FAIL'],
                $componentCounts['SKIP']
            );
        }

        /** @var array<string, list<array<string, mixed>>> $grouped */
        $grouped = [];

        foreach ($results as $result) {
            $component = self::stringField($result, 'component', 'unknown');
            $grouped[$component][] = $result;
        }

        ksort($grouped, SORT_STRING);

        foreach ($grouped as $component => $componentResults) {
            $lines[] = '';
            $lines[] = '<details>';
            $lines[] = "<summary>{$component}</summary>";
            $lines[] = '';
            $lines[] = '| Host | Status | Message |';
            $lines[] = '|---|---|---|';

            foreach ($componentResults as $result) {
                $lines[] = sprintf(
                    '| %s | %s | %s |',
                    self::escape(self::stringField($result, 'host', 'unknown')),
                    self::escape(self::stringField($result, 'status', 'FAIL')),
                    self::escape(self::stringField($result, 'message', ''))
                );
            }

            $lines[] = '';
            $lines[] = '</details>';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $source
     */
    private static function stringField(array $source, string $key, string $default): string
    {
        $value = $source[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    private static function escape(string $value): string
    {
        return str_replace(["\r", "\n", '|'], ['', '<br>', '\\|'], $value);
    }
}
