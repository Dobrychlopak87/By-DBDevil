<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pulse.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!pulseEnsureSchema()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'items' => pulseTickerItems()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
