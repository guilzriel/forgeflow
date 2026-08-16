<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class ConfigComparison
{
    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     * @return array<string, array{left:string, right:string}>
     */
    public static function differences(array $left, array $right): array
    {
        $leftFlat = self::flatten(self::normalizedData($left));
        $rightFlat = self::flatten(self::normalizedData($right));
        $keys = array_values(array_unique(array_merge(array_keys($leftFlat), array_keys($rightFlat))));
        sort($keys, SORT_STRING);

        $differences = [];
        foreach ($keys as $key) {
            $leftValue = $leftFlat[$key] ?? '<missing>';
            $rightValue = $rightFlat[$key] ?? '<missing>';

            if ($leftValue === $rightValue) {
                continue;
            }

            $differences[$key] = ['left' => $leftValue, 'right' => $rightValue];
        }

        return $differences;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    public static function renderMarkdown(array $left, array $right): string
    {
        $fallbackComponent = self::stringField($right, 'component', 'UNKNOWN');
        $component = strtoupper(
            self::stringField($left, 'component', $fallbackComponent)
        );
        $leftHost = self::stringField($left, 'host', 'left');
        $rightHost = self::stringField($right, 'host', 'right');
        $differences = self::differences($left, $right);
        $result = $differences === [] ? 'CONFIGURATIONS MATCH' : 'DIFFERENCES FOUND';

        $lines = [
            '# ForgeFlow Configuration Comparison',
            '',
            "**Component:** {$component}",
            "**Left:** {$leftHost}",
            "**Right:** {$rightHost}",
            "**Result:** {$result}",
            '',
        ];

        if ($differences === []) {
            $lines[] = 'No meaningful configuration differences were found.';
            $lines[] = '';

            return implode(PHP_EOL, $lines);
        }

        $lines[] = '## Differences';
        $lines[] = '';
        $lines[] = "| Setting | {$leftHost} | {$rightHost} |";
        $lines[] = '|---|---|---|';

        foreach ($differences as $key => $values) {
            $lines[] = sprintf(
                '| %s | %s | %s |',
                self::escapeMarkdown($key),
                self::escapeMarkdown($values['left']),
                self::escapeMarkdown($values['right'])
            );
        }

        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string, mixed> $capture
     * @return array<string, mixed>
     */
    private static function normalizedData(array $capture): array
    {
        $component = self::stringField($capture, 'component', '');
        $data = $capture['data'] ?? [];

        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $data;

        if (
            $component === 'mysql'
            && isset($normalized['variables_tsv'])
            && is_string($normalized['variables_tsv'])
        ) {
            $settings = [];
            foreach (preg_split('/\R/', trim($normalized['variables_tsv'])) ?: [] as $line) {
                if ($line === '') {
                    continue;
                }

                $parts = explode("\t", $line, 2);
                if (count($parts) === 2) {
                    $settings[$parts[0]] = $parts[1];
                }
            }

            unset($normalized['variables_tsv']);
            $normalized['settings'] = $settings;
        }

        if (
            $component === 'apache'
            && isset($normalized['directives_tsv'])
            && is_string($normalized['directives_tsv'])
        ) {
            $settings = [];
            foreach (preg_split('/\R/', trim($normalized['directives_tsv'])) ?: [] as $line) {
                if ($line === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', trim($line), 2);
                if (is_array($parts) && count($parts) === 2) {
                    $settings[$parts[0]] = $parts[1];
                }
            }

            unset($normalized['directives_tsv']);
            $normalized['settings'] = $settings;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, string>
     */
    private static function flatten(array $value, string $prefix = ''): array
    {
        $result = [];

        foreach ($value as $key => $child) {
            $path = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($child) && !array_is_list($child)) {
                /** @var array<string, mixed> $child */
                $result += self::flatten($child, $path);
                continue;
            }

            if (is_array($child)) {
                $items = array_map(self::scalarToString(...), $child);
                sort($items, SORT_STRING);
                $result[$path] = implode("\n", $items);
                continue;
            }

            $result[$path] = self::scalarToString($child);
        }

        return $result;
    }

    private static function scalarToString(mixed $value): string
    {
        if ($value === null) {
            return '<null>';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new \RuntimeException('Configuration capture contains a non-scalar list value.');
    }

    /**
     * @param array<string, mixed> $source
     */
    private static function stringField(array $source, string $key, string $default): string
    {
        $value = $source[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    private static function escapeMarkdown(string $value): string
    {
        return str_replace(["\r", "\n", '|'], ['', '<br>', '\\|'], $value);
    }
}
