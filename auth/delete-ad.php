<?php
require_once __DIR__ . '/lib.php';
$user = auth_require_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/auth/my.php');
}
try {
    auth_verify_csrf($_POST['csrf_token'] ?? null);
    if (!auth_ensure_schema()) {
        throw new RuntimeException('Moduł kont jest chwilowo niedostępny.');
    }
    global $pdo;
    $statement = $pdo->prepare("UPDATE ads SET is_active = 0, submission_status = 'rejected' WHERE id = ? AND owner_id = ?");
    $statement->execute([(int) ($_POST['id'] ?? 0), (int) $user['id']]);
} catch (Throwable $exception) {
    $_SESSION['auth_error'] = $exception->getMessage();
}
redirect(SITE_URL . '/auth/my.php');