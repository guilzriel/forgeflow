<?php

declare(strict_types=1);

use ForgeFlow\Automation\OperationCatalog;
use ForgeFlow\Automation\TargetCatalog;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);

try {
    $operations = new OperationCatalog($root . '/automation/catalog/operations.json');
    new TargetCatalog($root . '/automation/catalog/targets.json');

    foreach ($operations->all() as $id => $operation) {
        $playbookValue = $operation['playbook'] ?? null;

        if (!is_string($playbookValue) || $playbookValue === '') {
            throw new RuntimeException("Catalogue operation {$id} has an invalid playbook.");
        }

        $playbook = $root . '/' . $playbookValue;

        if (!is_file($playbook)) {
            throw new RuntimeException("Catalogue references missing playbook: {$id}");
        }
    }

    echo 'catalogue_validation=passed' . PHP_EOL;
    echo 'operation_count=' . count($operations->all()) . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'catalogue_validation=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
