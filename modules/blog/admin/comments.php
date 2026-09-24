<?php
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

ensureChronicleInteractionSchema();
$csrfToken = getAdminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
        $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        if (!$commentId || !deleteChronicleComment((int) $commentId)) {
            throw new RuntimeException('Nie znaleziono wskazanego komentarza.');
        }
        $_SESSION['admin_message'] = 'Komentarz został usunięty.';
        $_SESSION['admin_message_type'] = 'success';
    } catch (Throwable $exception) {
        $_SESSION['admin_message'] = $exception->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }

    redirect(SITE_URL . '/admin/blog/comments.php');
    exit();
}

$pageTitle = 'Komentarze Kroniki - Panel administracyjny';
$comments = getAdminChronicleComments();
$adminMessage = $_SESSION['admin_message'] ?? '';
$adminMessageType = $_SESSION['admin_message_type'] ?? 'success';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
?>
<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>
    <div class="admin-container">
        <?php require_once dirname(__DIR__, 3) . '/admin/includes/admin-header.php'; ?>
        <div class="admin-content">
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Komentarze Kroniki</h1>
                    <p>Wpisy są widoczne od razu, a tutaj można usuwać wyłącznie komentarze naruszające zasady.</p>
                </div>
                <div class="admin-page-actions">
                    <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary"><i class="fas fa-list"></i> Artykuły</a>
                </div>
            </div>

            <?php if ($adminMessage !== ''): ?>
                <div class="alert alert-<?= $adminMessageType === 'success' ? 'success' : 'danger' ?>"><span class="alert-message"><?= sanitize($adminMessage) ?></span></div>
            <?php endif; ?>

            <div class="dashboard-section">
                <div class="section-header"><h2>Ostatnie komentarze <span style="color: #999; font-size: .9rem; font-weight: normal;">(<?= count($comments) ?>)</span></h2></div>
                <?php if ($comments !== []): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr><th>Wpis</th><th>Autor</th><th>Komentarz</th><th>Data</th><th>Polubienia</th><th>Akcje</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td><a href="<?= getChroniclePostUrl($comment['post_slug']) ?>" target="_blank" rel="noopener noreferrer"><?= sanitize(truncateText($comment['post_title'], 55)) ?></a></td>
                                        <td><strong><?= sanitize($comment['author_name']) ?></strong></td>
                                        <td><?= nl2br(sanitize(truncateText($comment['content'], 220)), false) ?></td>
                                        <td><?= formatDate($comment['created_at'], 'd.m.Y H:i') ?></td>
                                        <td><?= (int) $comment['likes_count'] ?></td>
                                        <td class="actions-col"><button type="submit" form="chronicle-comment-delete-<?= (int) $comment['id'] ?>" class="action-btn delete-btn" title="Usuń komentarz" aria-label="Usuń komentarz autora <?= sanitize($comment['author_name']) ?>" onclick="return confirm('Usunąć ten komentarz?')"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card-body text-center py-4"><h3>Brak komentarzy</h3><p>Po opublikowaniu komentarze pojawią się tutaj.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php foreach ($comments as $comment): ?>
        <form id="chronicle-comment-delete-<?= (int) $comment['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/comments.php" hidden>
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
        </form>
    <?php endforeach; ?>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
