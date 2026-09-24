<?php
// Przełącz status ogłoszenia
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
$action = (string) ($_POST['action'] ?? '');
$allowedActions = ['activate', 'approve', 'reject', 'deactivate', 'feature', 'unfeature'];

if ($id === false || $id === null || $id < 1 || !in_array($action, $allowedActions, true)) {
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

switch ($action) {
    case 'activate':
        $stmt = $pdo->prepare('UPDATE ads SET submission_status = \'approved\', is_active = 1 WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = 'Ogłoszenie zostało opublikowane.';
        $_SESSION['admin_message_type'] = 'success';
        break;

    case 'approve':
        $stmt = $pdo->prepare('UPDATE ads SET submission_status = \'approved\', is_active = 1 WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = 'Zgłoszenie zostało zaakceptowane i opublikowane.';
        $_SESSION['admin_message_type'] = 'success';
        break;

    case 'reject':
        $stmt = $pdo->prepare('UPDATE ads SET submission_status = \'rejected\', is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = 'Zgłoszenie zostało odrzucone i pozostaje niewidoczne publicznie.';
        $_SESSION['admin_message_type'] = 'warning';
        break;

    case 'deactivate':
        $stmt = $pdo->prepare('UPDATE ads SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = 'Ogłoszenie zostało deaktywowane.';
        $_SESSION['admin_message_type'] = 'warning';
        break;

    case 'feature':
        $stmt = $pdo->prepare('UPDATE ads SET is_featured = 1 WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = 'Ogłoszenie zostało oznaczone jako wyróżnione.';
        $_SESSION['admin_message_type'] = 'success';
        break;

    case 'unfeature':
        $stmt = $pdo->prepare('UPDATE ads SET is_featured = 0 WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = 'Ogłoszenie zostało odznaczone jako wyróżnione.';
        $_SESSION['admin_message_type'] = 'warning';
        break;
}

redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/ads/index.php');
