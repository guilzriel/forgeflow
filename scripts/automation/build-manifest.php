<?php

declare(strict_types=1);

use ForgeFlow\Automation\CliArguments;
use ForgeFlow\Automation\ExecutionManifest;
use ForgeFlow\Automation\JsonFile;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'];
    $arguments = CliArguments::parse($argv);
    $resolutionPath = CliArguments::requireString($arguments, 'resolution');
    $output = CliArguments::requireString($arguments, 'output');
    $resolution = JsonFile::read($resolutionPath);

    $manifest = ExecutionManifest::build($resolution, [
        'source_revision' => getenv('GITHUB_SHA') ?: 'local',
        'source_ref' => getenv('GITHUB_REF') ?: 'local',
        'workflow' => getenv('GITHUB_WORKFLOW') ?: 'local',
        'run_id' => getenv('GITHUB_RUN_ID') ?: 'local',
        'run_attempt' => getenv('GITHUB_RUN_ATTEMPT') ?: '1',
        'requested_by' => getenv('GITHUB_ACTOR') ?: 'local',
    ]);
    JsonFile::write($output, $manifest);

    echo 'manifest_build=passed' . PHP_EOL;
    echo 'execution_id=' . (string) $manifest['execution_id'] . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'manifest_build=failed' . PHP_EOL);
    fwrite(STDERR, 'error=' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
