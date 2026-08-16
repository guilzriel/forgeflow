<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\ComparisonResolver;
use ForgeFlow\Automation\HealthCheckResolver;
use ForgeFlow\Automation\JsonFile;
use ForgeFlow\Automation\OperationCatalog;
use ForgeFlow\Automation\ResolutionAuthorizer;
use ForgeFlow\Automation\TargetCatalog;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);

try {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'];
    $arguments = CliArguments::parse($argv);
    $type = CliArguments::requireString($arguments, 'type');
    $resolutionPath = CliArguments::requireString($arguments, 'resolution');
    $resolution = JsonFile::read($resolutionPath);

    $operations = new OperationCatalog($root . '/automation/catalog/operations.json');
    $targets = new TargetCatalog($root . '/automation/catalog/targets.json');

    if ($type === 'health') {
        ResolutionAuthorizer::authorizeHealth(
            $resolution,
            new HealthCheckResolver($operations, $targets)
        );
    } elseif ($type === 'comparison') {
        ResolutionAuthorizer::authorizeComparison(
            $resolution,
            new ComparisonResolver($operations, $targets)
        );
    } else {
        throw new InvalidArgumentException('Authorization type must be health or comparison.');
    }

    echo 'resolution_authorization=passed' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'resolution_authorization=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
