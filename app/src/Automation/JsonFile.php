<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class JsonFile
{
    /**
     * @return array<string, mixed>
     */
    public static function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException("JSON file is not readable: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Unable to read JSON file: {$path}");
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \RuntimeException("JSON root must be an object: {$path}");
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed>|list<mixed> $value
     */
    public static function write(string $path, array $value): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException("Unable to create directory: {$directory}");
        }

        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $encoded . PHP_EOL) === false) {
            throw new \RuntimeException("Unable to write JSON file: {$path}");
        }
    }
}
