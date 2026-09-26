<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/calendar/contract.php';
if (!isAdminLoggedIn()) redirect(SITE_URL . '/admin/login.php');
ensureCalendarSchema();
global $pdo;
$csrfToken = getAdminCsrfToken();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $lookup = $pdo->prepare('SELECT e.id, e.chronicle_post_id FROM calendar_events e WHERE e.id = ? LIMIT 1');
        $lookup->execute([$id]);
        $event = $lookup->fetch();
        if (!$event) throw new RuntimeException('Nie znaleziono wydarzenia.');
        $postId = (int) ($event['chronicle_post_id'] ?? 0);
        if ($action === 'approve' && $postId > 0) {
            $statement = $pdo->prepare("UPDATE blog_posts SET is_active = 1, submission_status = 'approved' WHERE id = ?");
            $statement->execute([$postId]);
            $_SESSION['admin_message'] = 'Wydarzenie i wpis Kroniki zostały opublikowane.';
        } elseif ($action === 'reject' && $postId > 0) {
            $statement = $pdo->prepare("UPDATE blog_posts SET is_active = 0, submission_status = 'rejected' WHERE id = ?");
            $statement->execute([$postId]);
            $_SESSION['admin_message'] = 'Wydarzenie zostało odrzucone.';
        } elseif ($action === 'deactivate' && $postId > 0) {
            $statement = $pdo->prepare('UPDATE blog_posts SET is_active = 0 WHERE id = ?');
            $statement->execute([$postId]);
            $_SESSION['admin_message'] = 'Wydarzenie zostało ukryte.';
        } elseif ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM calendar_events WHERE id = ?');
            $statement->execute([$id]);
            if ($postId > 0) {
                $statement = $pdo->prepare('DELETE FROM blog_posts WHERE id = ?');
                $statement->execute([$postId]);
            }
            $_SESSION['admin_message'] = 'Wydarzenie i jego wpis zostały usunięte.';
        } else {
            throw new RuntimeException('Nieprawidłowa operacja.');
        }
        $_SESSION['admin_message_type'] = 'success';
    } catch (Throwable $exception) {
        $_SESSION['admin_message'] = $exception->getMessage();
        $_SESSION['admin_message_type'] = 'warning';
    }
    redirect(SITE_URL . '/admin/calendar.php');
}
$events = $pdo->query("SELECT e.*, bp.title AS chronicle_title, bp.submission_status, bp.is_active,
        bp.author_signature, bp.source_type
    FROM calendar_events e
    LEFT JOIN blog_posts bp ON bp.id = e.chronicle_post_id
    ORDER BY CASE WHEN bp.submission_status = 'pending' THEN 0 ELSE 1 END, e.event_date DESC, e.id DESC")->fetchAll();
$pageTitle = 'Kalendarz - Panel administracyjny';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-container"><div class="admin-content"><?php require_once __DIR__ . '/includes/admin-header.php'; ?><div class="admin-page-header"><div class="admin-page-title"><h1>Kalendarz</h1><p>Moderuj wydarzenia oraz powiązane wpisy Kroniki Miasta.</p></div><div class="admin-page-actions"><a href="<?= SITE_URL ?>/calendar.php" class="btn btn-secondary" target="_blank" rel="noopener">Zobacz kalendarz</a></div></div><?php if (isset($_SESSION['admin_message'])): ?><div class="alert alert-<?= sanitize($_SESSION['admin_message_type'] ?? 'info') ?>"><span class="alert-message"><?= sanitize($_SESSION['admin_message']) ?></span></div><?php unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); endif; ?><div class="dashboard-section"><div class="section-header"><h2>Wydarzenia (<?= count($events) ?>)</h2></div><div class="latest-items-list"><?php if (!$events): ?><p class="no-items">Brak wydarzeń.</p><?php else: foreach ($events as $event): ?><div class="latest-item"><div class="latest-item-info"><h4><?= sanitize((string) ($event['chronicle_title'] ?: $event['title'])) ?></h4><p class="category-tag"><?= sanitize($event['event_date']) ?><?= $event['event_time'] ? ' · ' . sanitize($event['event_time']) : '' ?> · <?= sanitize($event['category']) ?></p><p class="item-date"><?= sanitize($event['location'] ?? '') ?> · <?= sanitize($event['submission_status'] ?? 'legacy') ?><?= !empty($event['author_signature']) ? ' · ' . sanitize($event['author_signature']) : '' ?></p></div><div class="latest-item-actions"><?php if (($event['submission_status'] ?? '') === 'pending'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $event['id'] ?>"><button class="action-btn view-btn" name="action" value="approve" type="submit">Zatwierdź</button><button class="action-btn reject-btn" name="action" value="reject" type="submit">Odrzuć</button></form><?php elseif (($event['is_active'] ?? 1)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $event['id'] ?>"><button class="action-btn" name="action" value="deactivate" type="submit">Ukryj</button></form><?php endif; ?><form method="post" onsubmit="return confirm('Czy na pewno usunąć wydarzenie i powiązany wpis?');"><input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $event['id'] ?>"><button class="action-btn delete-btn" type="submit" name="action" value="delete">Usuń</button></form></div></div><?php endforeach; endif; ?></div></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
