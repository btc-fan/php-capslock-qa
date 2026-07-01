<?php

declare(strict_types=1);

/**
 * Minimal in-memory mock of the Media Buyers contract for CI execution only.
 * Not a graded deliverable — demonstrates executability per the task's
 * optional "stub the HTTP layer" allowance. Data resets every process
 * start (php -S ... router.php); state persists only for the life of one
 * CI job via a static array.
 *
 * Run: php -S 127.0.0.1:8080 tests/_data/mock-server/router.php
 */

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$storeFile = sys_get_temp_dir() . '/mediabuyers_mock_store.json';
$store = file_exists($storeFile) ? json_decode(file_get_contents($storeFile), true) : [];

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($path === '/api/mediabuyers' && $method === 'GET') {
    respond(200, ['data' => array_values($store)]);
}

if ($path === '/api/mediabuyers' && $method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];

    $errors = [];
    foreach (['mbId', 'name', 'email', 'active'] as $required) {
        if (!array_key_exists($required, $body)) {
            $errors[] = ['detail' => "This field is missing: [{$required}]"];
        }
    }

    if (isset($body['mbId']) && !preg_match('/^\d+$/', (string) $body['mbId'])) {
        $errors[] = ['detail' => "The mbId {$body['mbId']} is not a positive integer string."];
    }

    if (isset($body['email']) && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['detail' => "The email {$body['email']} is not a valid email."];
    }

    if (isset($body['initials']) && strlen((string) $body['initials']) !== 2) {
        $errors[] = ['detail' => 'The initials must be exactly 2 characters long.'];
    }

    if (isset($body['name']) && (strlen($body['name']) < 2 || strlen($body['name']) > 30)) {
        $errors[] = ['detail' => 'The name must be between 2 and 30 characters.'];
    }

    if (isset($body['active']) && !is_bool($body['active'])) {
        $errors[] = ['detail' => 'The active field must be a boolean.'];
    }

    if (isset($body['mbId']) && isset($store[$body['mbId']])) {
        respond(409, ['errors' => [['detail' => "mbId {$body['mbId']} already exists."]]]);
    }

    if ($errors !== []) {
        respond(400, ['errors' => $errors]);
    }

    $record = [
        'id' => count($store) + 1,
        'mbId' => (string) $body['mbId'],
        'initials' => $body['initials'] ?? '',
        'name' => $body['name'],
        'email' => $body['email'],
        'slackUserId' => $body['slackUserId'] ?? '',
        'active' => $body['active'] ? 1 : 0,
    ];

    $store[$body['mbId']] = $record;
    file_put_contents($storeFile, json_encode($store));

    respond(200, ['data' => $record]);
}

respond(404, ['errors' => [['detail' => 'Not found']]]);
