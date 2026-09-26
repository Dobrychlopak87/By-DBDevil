<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pulse.php';

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

pulseEnsureSchema();
global $pdo;
$csrfToken = getAdminCsrfToken();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moderate_pulse'])) {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
        $id = filter_var($_POST['notice_id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');
        if (!$id || !pulseModerateNotice($id, $status)) {
            throw new RuntimeException('Nieprawidłowe dane moderacji.');
        }
        $message = $status === 'hidden'
            ? 'Komunikat został ukryty.'
            : ($status === 'deleted' ? 'Komunikat został usunięty.' : 'Komunikat został przywrócony do Pulsu.');
    } catch (Throwable $exception) {
        http_response_code(422);
        $message = 'Nie udało się zmienić statusu komunikatu.';
    }
}

$pageTitle = 'Puls miasta — moderacja';
$notices = pulseAllNotices();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-container">
<?php require_once __DIR__ . '/includes/admin-header.php'; ?>
<main class="admin-content">
    <div class="admin-page-header"><div class="admin-page-title"><h1>Puls miasta</h1><p>Wpisy mieszkańców pojawiają się natychmiast. Z tego miejsca można je ukryć, przywrócić albo usunąć.</p></div></div>
    <?php if ($message !== ''): ?><div class="alert alert-info" role="status"><?= sanitize($message) ?></div><?php endif; ?>
    <div class="dashboard-section"><div class="card-body"><p class="form-help">Wpisy aktywne wygasają po 12 godzinach. Ukrycie lub usunięcie od razu usuwa komunikat z publicznego tickera; usunięty wpis można później przywrócić.</p>
        <div class="pulse-admin-list">
        <?php foreach ($notices as $notice): ?>
            <article class="card pulse-admin-card">
                <div>
                    <strong><?= sanitize($notice['status']) ?></strong>
                    <p><?= sanitize($notice['message']) ?></p>
                    <small><?= sanitize((string) ($notice['signature'] ?? '')) ?><?= !empty($notice['phone']) ? ' · ' . sanitize((string) $notice['phone']) : '' ?> · <?= sanitize((string) $notice['published_at']) ?></small>
                </div>
                <form method="post" class="pulse-admin-actions" aria-label="Działania dla komunikatu Pulsu">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <input type="hidden" name="moderate_pulse" value="1">
                    <input type="hidden" name="notice_id" value="<?= (int) $notice['id'] ?>">
                    <?php if ($notice['status'] === 'published'): ?>
                        <button class="btn btn-secondary" type="submit" name="status" value="hidden">Ukryj</button>
                    <?php else: ?>
                        <button class="btn btn-secondary" type="submit" name="status" value="published">Przywróć</button>
                    <?php endif; ?>
                    <?php if ($notice['status'] !== 'deleted'): ?>
                        <button class="btn btn-primary" type="submit" name="status" value="deleted">Usuń</button>
                    <?php endif; ?>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if ($notices === []): ?><p>Brak komunikatów.</p><?php endif; ?>
        </div>
    </div></div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
