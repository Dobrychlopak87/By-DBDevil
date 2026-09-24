<?php
declare(strict_types=1);

/** @param array<string, mixed> $payload */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function json_error(string $message, int $status = 400, ?string $code = null): never
{
    $payload = ['ok' => false, 'error' => $message];
    if ($code !== null && $code !== '') {
        $payload['code'] = $code;
    }
    json_response($payload, $status);
}
