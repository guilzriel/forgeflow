<?php

declare(strict_types=1);

use ForgeFlow\Application;

require dirname(__DIR__) . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = is_string($requestUri) ? $requestUri : '/';

$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($parsedPath) ? $parsedPath : '/';

try {
    if ($path === '/' || $path === '/health') {
        http_response_code(200);
        $response = (new Application())->health();
    } else {
        http_response_code(404);
        $response = [
            'status' => 'not_found',
            'path' => $path,
        ];
    }
} catch (\Throwable) {
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => 'Unexpected application failure.',
    ];
}

echo json_encode(
    $response,
    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
echo PHP_EOL;
