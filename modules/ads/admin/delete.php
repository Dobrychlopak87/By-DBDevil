<?php
// Usuń ogłoszenie
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit;
}

try {
    verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
} catch (RuntimeException $exception) {
    http_response_code(403);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit;
}

ensureAdModerationSchema();
$ad = getAdById($id, true);

if (!$ad) {
    redirect(SITE_URL . '/admin/ads/index.php');
    exit;
}

global $pdo;

if (!empty($ad['image'])) {
    deleteFile($ad['image']);
}

$stmt = $pdo->prepare('DELETE FROM ads WHERE id = ?');
$stmt->execute([$id]);

$_SESSION['admin_message'] = 'Ogłoszenie zostało usunięte pomyślnie.';
$_SESSION['admin_message_type'] = 'success';

redirect(SITE_URL . '/admin/ads/index.php');
