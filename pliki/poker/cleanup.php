<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$providedKey = (string)($_GET['key'] ?? '');
$expectedKey = (string)($config['cleanup_key'] ?? '');
if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(array('ok' => false, 'message' => 'Brak dostępu.'), JSON_UNESCAPED_UNICODE));
}

$before = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE auth_type = 'guest'")->fetchColumn();
purgeInactiveGuests($pdo);
$after = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE auth_type = 'guest'")->fetchColumn();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'ok' => true,
    'removed_guests' => max(0, $before - $after),
    'checked_at' => gmdate('c'),
), JSON_UNESCAPED_UNICODE);
