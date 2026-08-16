<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class CliArguments
{
    /**
     * @param list<string> $argv
     * @return array<string, string|bool>
     */
    public static function parse(array $argv): array
    {
        $result = [];
        $count = count($argv);

        for ($index = 1; $index < $count; $index++) {
            $argument = $argv[$index];
            if (!str_starts_with($argument, '--')) {
                throw new \InvalidArgumentException("Unexpected argument: {$argument}");
            }

            $name = substr($argument, 2);
            if ($name === '') {
                throw new \InvalidArgumentException('Empty option name is not allowed.');
            }

            $next = $argv[$index + 1] ?? null;
            if ($next === null || str_starts_with($next, '--')) {
                $result[$name] = true;
                continue;
            }

            $result[$name] = $next;
            $index++;
        }

        return $result;
    }

    /**
     * @param array<string, string|bool> $arguments
     */
    public static function requireString(array $arguments, string $name): string
    {
        $value = $arguments[$name] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException("Missing required --{$name} value.");
        }

        return trim($value);
    }

    /**
     * @param array<string, string|bool> $arguments
     */
    public static function optionalString(array $arguments, string $name, string $default = ''): string
    {
        $value = $arguments[$name] ?? null;
        return is_string($value) ? trim($value) : $default;
    }

    /**
     * @param array<string, string|bool> $arguments
     */
    public static function flag(array $arguments, string $name): bool
    {
        return ($arguments[$name] ?? false) === true;
    }
}
