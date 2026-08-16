<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\HealthResultSummary;
use ForgeFlow\Automation\JsonFile;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'];
    $arguments = CliArguments::parse($argv);
    $directory = CliArguments::requireString($arguments, 'dir');
    $markdownPath = CliArguments::requireString($arguments, 'markdown');
    $jsonPath = CliArguments::requireString($arguments, 'json');
    $title = CliArguments::optionalString($arguments, 'title', 'ForgeFlow Health Check');
    $expectedCountRaw = CliArguments::optionalString($arguments, 'expected-count');
    $failOnFailure = CliArguments::flag($arguments, 'fail-on-failure');

    $results = HealthResultSummary::loadDirectory($directory);
    if ($expectedCountRaw !== '') {
        if (!ctype_digit($expectedCountRaw)) {
            throw new InvalidArgumentException('Expected count must be a positive integer.');
        }
        $results = HealthResultSummary::enforceExpectedCount($results, (int) $expectedCountRaw);
    }
    $markdown = HealthResultSummary::renderMarkdown($results, $title);

    if (file_put_contents($markdownPath, $markdown) === false) {
        throw new RuntimeException('Unable to write Markdown health report.');
    }
    JsonFile::write($jsonPath, $results);

    echo $markdown;
    if ($failOnFailure && HealthResultSummary::hasFailure($results)) {
        exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'health_summary=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
