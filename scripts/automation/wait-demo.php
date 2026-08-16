<?php

declare(strict_types=1);

$deadline = time() + 120;

/** @var list<array{name:string, command:list<string>}> $checks */
$checks = [
    [
        'name' => 'WEB-A',
        'command' => ['curl', '--fail', '--silent', 'http://127.0.0.1:18081/health'],
    ],
    [
        'name' => 'WEB-B',
        'command' => ['curl', '--fail', '--silent', 'http://127.0.0.1:18082/health'],
    ],
    [
        'name' => 'REDIS',
        'command' => ['docker', 'exec', 'forgeflow-redis', 'redis-cli', 'PING'],
    ],
    [
        'name' => 'DB-A',
        'command' => [
            'docker',
            'exec',
            'forgeflow-db-a',
            'sh',
            '-lc',
            'mariadb-admin -uroot -p"$MARIADB_ROOT_PASSWORD" ping',
        ],
    ],
    [
        'name' => 'DB-B',
        'command' => [
            'docker',
            'exec',
            'forgeflow-db-b',
            'sh',
            '-lc',
            'mariadb-admin -uroot -p"$MARIADB_ROOT_PASSWORD" ping',
        ],
    ],
];

/**
 * @param list<string> $command
 */
function commandExitCode(array $command): int
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        return 127;
    }

    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    return proc_close($process);
}

foreach ($checks as $check) {
    while (true) {
        if (commandExitCode($check['command']) === 0) {
            echo $check['name'] . '=ready' . PHP_EOL;
            break;
        }

        if (time() >= $deadline) {
            fwrite(STDERR, $check['name'] . '=not_ready' . PHP_EOL);
            exit(1);
        }

        usleep(500000);
    }
}
