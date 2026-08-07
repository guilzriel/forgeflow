<?php

declare(strict_types=1);

$options = getopt('', ['url:', 'timeout::']);

$urlOption = $options['url'] ?? null;
$timeoutOption = $options['timeout'] ?? null;

$url = is_string($urlOption)
    ? $urlOption
    : 'http://127.0.0.1:8080/health';

$timeout = is_string($timeoutOption)
    ? (int) $timeoutOption
    : 60;

$deadline = time() + $timeout;

while (time() <= $deadline) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents(
        $url,
        false,
        $context
    );

    if ($response !== false) {
        $decoded = json_decode(
            $response,
            true
        );

        if (
            is_array($decoded)
            && ($decoded['status'] ?? null) === 'healthy'
            && ($decoded['runtime'] ?? null) === 'php'
        ) {
            fwrite(
                STDOUT,
                "ForgeFlow is healthy.\n"
            );

            exit(0);
        }
    }

    sleep(2);
}

fwrite(
    STDERR,
    "ForgeFlow health check timed out.\n"
);

exit(1);
