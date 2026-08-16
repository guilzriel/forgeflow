<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\HealthCheckResolver;
use ForgeFlow\Automation\OperationCatalog;
use ForgeFlow\Automation\TargetCatalog;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);

try {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'];
    $arguments = CliArguments::parse($argv);
    $resultDir = CliArguments::requireString($arguments, 'result-dir');

    $resolver = new HealthCheckResolver(
        new OperationCatalog($root . '/automation/catalog/operations.json'),
        new TargetCatalog($root . '/automation/catalog/targets.json')
    );

    $checks = [
        ['health.connectivity', '[DEMO] All services'],
        ['health.apache', '[DEMO-WEB] All web nodes'],
        ['health.php_fpm', '[DEMO-WEB] All web nodes'],
        ['health.application', '[DEMO-WEB] All web nodes'],
        ['health.redis', '[DEMO-REDIS] Redis'],
        ['health.mysql', '[DEMO-DB] All database nodes'],
        ['health.storage', '[DEMO-STORAGE] Shared storage'],
    ];

    $nonZero = 0;
    foreach ($checks as [$operation, $target]) {
        $resolution = $resolver->resolve($operation, $target);
        echo PHP_EOL . '===== ' . (string) $resolution['operation_label'] . ' =====' . PHP_EOL;
        $command = sprintf(
            'ansible-playbook -i %s %s --limit %s -e %s',
            escapeshellarg($root . '/' . (string) $resolution['inventory']),
            escapeshellarg($root . '/' . (string) $resolution['playbook']),
            escapeshellarg((string) $resolution['ansible_limit']),
            escapeshellarg('health_result_dir=' . $resultDir)
        );
        $exitCode = 0;
        passthru($command, $exitCode);
        if ($exitCode !== 0) {
            $nonZero++;
            fwrite(STDERR, "ansible_nonzero={$operation}:{$exitCode}" . PHP_EOL);
        }
    }

    echo 'full_environment_components_executed=' . count($checks) . PHP_EOL;
    echo 'ansible_nonzero_count=' . $nonZero . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'full_environment_runner=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
