<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\ComparisonResolver;
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

    $resolver = new ComparisonResolver(
        new OperationCatalog($root . '/automation/catalog/operations.json'),
        new TargetCatalog($root . '/automation/catalog/targets.json')
    );
    $resolution = $resolver->resolveByLabels($operation, $target);
    JsonFile::write($output, $resolution);

    echo 'comparison_resolution=passed' . PHP_EOL;
    echo 'operation=' . (string) $resolution['operation'] . PHP_EOL;
    echo 'component=' . (string) $resolution['component'] . PHP_EOL;
    echo 'left_host=' . (string) $resolution['left_host'] . PHP_EOL;
    echo 'right_host=' . (string) $resolution['right_host'] . PHP_EOL;

    if ($githubOutput !== '') {
        $lines = [
            'operation=' . (string) $resolution['operation'],
            'operation_label=' . (string) $resolution['operation_label'],
            'component=' . (string) $resolution['component'],
            'target=' . (string) $resolution['requested_target'],
            'playbook=' . (string) $resolution['playbook'],
            'inventory=' . (string) $resolution['inventory'],
            'left_host=' . (string) $resolution['left_host'],
            'right_host=' . (string) $resolution['right_host'],
            'ansible_limit=' . (string) $resolution['ansible_limit'],
        ];
        if (file_put_contents($githubOutput, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND) === false) {
            throw new RuntimeException('Unable to write GitHub output.');
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'comparison_resolution=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
