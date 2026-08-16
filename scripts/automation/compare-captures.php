<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\ConfigComparison;
use ForgeFlow\Automation\JsonFile;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'];
    $arguments = CliArguments::parse($argv);
    $leftPath = CliArguments::requireString($arguments, 'left');
    $rightPath = CliArguments::requireString($arguments, 'right');
    $output = CliArguments::requireString($arguments, 'output');

    $left = JsonFile::read($leftPath);
    $right = JsonFile::read($rightPath);
    $markdown = ConfigComparison::renderMarkdown($left, $right);

    if (file_put_contents($output, $markdown) === false) {
        throw new RuntimeException('Unable to write configuration comparison report.');
    }

    echo $markdown;
} catch (Throwable $exception) {
    fwrite(STDERR, 'config_comparison=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
