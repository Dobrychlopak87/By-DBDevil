<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

ensurePollSchema();
archiveExpiredPolls();
$pollId = max(0, (int) ($_GET['id'] ?? 0));
$poll = $pollId > 0 ? getAdminArchivedPollById($pollId) : null;
if ($poll === null) {
    $_SESSION['admin_message'] = 'Nie znaleziono wskazanej ankiety w archiwum.';
    $_SESSION['admin_message_type'] = 'warning';
    redirect(SITE_URL . '/admin/polls.php?tab=archive');
    exit();
}

$results = getPollResults($pollId);
$pageTitle = 'Wyniki ankiety - Panel administracyjny';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="admin-container">
        <?php require_once __DIR__ . '/includes/admin-header.php'; ?>
        <div class="admin-content">
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <a class="admin-back-link" href="<?= SITE_URL ?>/admin/polls.php?tab=archive"><i class="fas fa-arrow-left"></i> Wróć do archiwum</a>
                    <h1>Wyniki ankiety</h1>
                    <p>Utrwalony wynik zakończonej ankiety mieszkańców.</p>
                </div>
            </div>

            <div class="dashboard-section poll-results-card">
                <div class="card-body">
                    <h2><?= sanitize($poll['question']) ?></h2>
                    <div class="poll-results-meta">
                        <span><i class="fas fa-calendar-check"></i> Zakończona: <?= formatDate($poll['archived_at'], 'd.m.Y H:i') ?></span>
                        <span><i class="fas fa-chart-bar"></i> Łącznie: <?= (int) $results['total_votes'] ?> <?= (int) $results['total_votes'] === 1 ? 'głos' : 'głosów' ?></span>
                    </div>

                    <div class="poll-results-list">
                        <?php foreach ($results['options'] as $option): ?>
                            <div class="poll-result-row">
                                <div class="poll-result-label"><span><?= sanitize($option['option_text']) ?></span><strong><?= (int) $option['votes'] ?> · <?= (int) $option['percentage'] ?>%</strong></div>
                                <div class="poll-result-track" role="progressbar" aria-label="<?= sanitize($option['option_text']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $option['percentage'] ?>"><span style="width: <?= (int) $option['percentage'] ?>%"></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
