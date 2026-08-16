<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\HealthCheckResolver;
use ForgeFlow\Automation\JsonFile;
use ForgeFlow\Automation\OperationCatalog;
use ForgeFlow\Automation\TargetCatalog;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);

try {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'];
    $arguments = CliArguments::parse($argv);
    $operation = CliArguments::requireString($arguments, 'operation');
    $target = CliArguments::requireString($arguments, 'target');
    $output = CliArguments::requireString($arguments, 'output');
    $githubOutput = CliArguments::optionalString($arguments, 'github-output');

    $resolver = new HealthCheckResolver(
        new OperationCatalog($root . '/automation/catalog/operations.json'),
        new TargetCatalog($root . '/automation/catalog/targets.json')
    );
    $resolution = $resolver->resolveByLabels($operation, $target);
    JsonFile::write($output, $resolution);

    echo 'health_check_resolution=passed' . PHP_EOL;
    echo 'operation=' . (string) $resolution['operation'] . PHP_EOL;
    echo 'target=' . (string) $resolution['requested_target'] . PHP_EOL;
    echo 'resolved_host_count=' . (string) $resolution['resolved_host_count'] . PHP_EOL;
    echo 'ansible_limit=' . (string) $resolution['ansible_limit'] . PHP_EOL;
    echo 'expected_result_count=' . (string) $resolution['expected_result_count'] . PHP_EOL;

    if ($githubOutput !== '') {
        $lines = [
            'operation=' . (string) $resolution['operation'],
            'operation_label=' . (string) $resolution['operation_label'],
            'target=' . (string) $resolution['requested_target'],
            'playbook=' . (string) $resolution['playbook'],
            'inventory=' . (string) $resolution['inventory'],
            'ansible_limit=' . (string) $resolution['ansible_limit'],
            'expected_count=' . (string) $resolution['expected_result_count'],
        ];
        if (file_put_contents($githubOutput, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND) === false) {
            throw new RuntimeException('Unable to write GitHub output.');
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'health_check_resolution=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
