<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

ensurePollSchema();
archiveExpiredPolls();

function pollAdminDateValue(?string $value): string
{
    if (!$value) {
        return '';
    }
    return (new DateTimeImmutable($value, new DateTimeZone('Europe/Warsaw')))->format('Y-m-d\TH:i');
}

function pollAdminStatus(array $poll): string
{
    if (!empty($poll['archived_at'])) {
        return 'Archiwum';
    }
    if ((int) $poll['is_active'] !== 1) {
        return 'Szkic';
    }

    $now = getPollNow();
    if (!empty($poll['starts_at']) && $poll['starts_at'] > $now) {
        return 'Zaplanowana';
    }
    return 'Aktywna';
}

$tab = ($_GET['tab'] ?? '') === 'archive' ? 'archive' : 'current';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save') {
            $pollId = (int) ($_POST['poll_id'] ?? 0);
            $pollId = savePoll(
                $pollId,
                (string) ($_POST['question'] ?? ''),
                is_array($_POST['options'] ?? null) ? $_POST['options'] : [],
                (string) ($_POST['starts_at'] ?? ''),
                (string) ($_POST['ends_at'] ?? ''),
                isset($_POST['is_active'])
            );
            $_SESSION['admin_message'] = 'Ankieta została zapisana.';
            $_SESSION['admin_message_type'] = 'success';
            redirect(SITE_URL . '/admin/polls.php?edit=' . $pollId);
            exit();
        }

        if ($action === 'toggle') {
            $pollId = (int) ($_POST['poll_id'] ?? 0);
            setPollActive($pollId, isset($_POST['is_active']));
            $_SESSION['admin_message'] = isset($_POST['is_active']) ? 'Ankieta została aktywowana.' : 'Ankieta została ukryta.';
            $_SESSION['admin_message_type'] = 'success';
            redirect(SITE_URL . '/admin/polls.php');
            exit();
        }

        if ($action === 'archive') {
            archivePoll((int) ($_POST['poll_id'] ?? 0));
            $_SESSION['admin_message'] = 'Ankieta została zakończona i przeniesiona do archiwum.';
            $_SESSION['admin_message_type'] = 'success';
            redirect(SITE_URL . '/admin/polls.php?tab=archive');
            exit();
        }

        if ($action === 'delete_archive') {
            deleteArchivedPoll((int) ($_POST['poll_id'] ?? 0));
            $_SESSION['admin_message'] = 'Ankieta została trwale usunięta z archiwum.';
            $_SESSION['admin_message_type'] = 'success';
            redirect(SITE_URL . '/admin/polls.php?tab=archive');
            exit();
        }

        throw new InvalidArgumentException('Nieprawidłowa operacja ankiety.');
    } catch (Throwable $exception) {
        $_SESSION['admin_message'] = $exception->getMessage();
        $_SESSION['admin_message_type'] = 'warning';
        $target = (int) ($_POST['poll_id'] ?? 0);
        $targetUrl = SITE_URL . '/admin/polls.php' . ($tab === 'archive' ? '?tab=archive' : ($target > 0 ? '?edit=' . $target : ''));
        redirect($targetUrl);
        exit();
    }
}

$editingId = $tab === 'current' ? max(0, (int) ($_GET['edit'] ?? 0)) : 0;
$editingPoll = $editingId > 0 ? getAdminPollById($editingId) : null;
if ($editingId > 0 && ($editingPoll === null || !empty($editingPoll['archived_at']))) {
    $_SESSION['admin_message'] = 'Nie znaleziono bieżącej ankiety.';
    $_SESSION['admin_message_type'] = 'warning';
    redirect(SITE_URL . '/admin/polls.php');
    exit();
}

$archiveQuery = $tab === 'archive' ? trim((string) ($_GET['q'] ?? '')) : '';
$polls = $tab === 'current' ? getAdminPolls() : [];
$archivedPolls = $tab === 'archive' ? getAdminPollArchive($archiveQuery) : [];
$archiveCount = count(getAdminPollArchive());
$pageTitle = 'Ankiety - Panel administracyjny';
$adminMessage = $_SESSION['admin_message'] ?? '';
$adminMessageType = $_SESSION['admin_message_type'] ?? 'success';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
$csrfToken = getAdminCsrfToken();

$optionValues = ['', '', '', '', ''];
if ($editingPoll) {
    foreach ($editingPoll['options'] as $index => $option) {
        $optionValues[$index] = $option['option_text'];
    }
}
$hasVotes = $editingPoll && (int) $editingPoll['votes_count'] > 0;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="admin-container">
        <?php require_once __DIR__ . '/includes/admin-header.php'; ?>
        <div class="admin-content">
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Ankiety</h1>
                    <p><?= $tab === 'archive' ? 'Przeglądaj zakończone ankiety oraz ich utrwalone wyniki.' : 'Twórz jedną aktywną ankietę dla mieszkańców w sektorze Kroniki Miasta.' ?></p>
                </div>
                <?php if ($tab === 'current'): ?>
                    <div class="admin-page-actions">
                        <a href="<?= SITE_URL ?>/admin/polls.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nowa ankieta</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($adminMessage !== ''): ?>
                <div class="alert alert-<?= $adminMessageType === 'warning' ? 'warning' : 'success' ?>">
                    <span class="alert-message"><?= sanitize($adminMessage) ?></span>
                </div>
            <?php endif; ?>

            <nav class="poll-admin-tabs" aria-label="Sekcje ankiet">
                <a href="<?= SITE_URL ?>/admin/polls.php" class="<?= $tab === 'current' ? 'is-active' : '' ?>">Bieżące ankiety</a>
                <a href="<?= SITE_URL ?>/admin/polls.php?tab=archive" class="<?= $tab === 'archive' ? 'is-active' : '' ?>">Archiwum <span><?= (int) $archiveCount ?></span></a>
            </nav>

            <?php if ($tab === 'archive'): ?>
                <div class="dashboard-section">
                    <div class="section-header"><h2>Archiwum ankiet</h2></div>
                    <div class="card-body">
                        <form method="get" class="poll-archive-filter">
                            <input type="hidden" name="tab" value="archive">
                            <label class="sr-only" for="poll-archive-query">Szukaj ankiety</label>
                            <input class="form-control" id="poll-archive-query" name="q" maxlength="160" value="<?= sanitize($archiveQuery) ?>" placeholder="Szukaj po pytaniu...">
                            <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Szukaj</button>
                            <?php if ($archiveQuery !== ''): ?><a class="btn btn-secondary" href="<?= SITE_URL ?>/admin/polls.php?tab=archive">Wyczyść</a><?php endif; ?>
                        </form>
                    </div>
                    <?php if ($archivedPolls): ?>
                        <div class="table-container">
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead><tr><th>Zakończona</th><th>Pytanie</th><th>Głosy</th><th>Status</th><th>Akcje</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($archivedPolls as $poll): ?>
                                            <tr>
                                                <td><?= formatDate($poll['archived_at'], 'd.m.Y H:i') ?></td>
                                                <td class="table-title-cell"><strong><?= sanitize($poll['question']) ?></strong></td>
                                                <td><?= (int) $poll['votes_count'] ?></td>
                                                <td><span class="status-badge inactive">Archiwum</span></td>
                                                <td class="actions-col">
                                                    <a class="action-btn view-btn" href="<?= SITE_URL ?>/admin/poll-results.php?id=<?= (int) $poll['id'] ?>" title="Zobacz wyniki"><i class="fas fa-chart-bar"></i></a>
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Trwale usunąć ankietę wraz z jej głosami?');">
                                                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="delete_archive">
                                                        <input type="hidden" name="poll_id" value="<?= (int) $poll['id'] ?>">
                                                        <button class="action-btn delete-btn" type="submit" title="Usuń trwale"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card"><div class="card-body text-center py-4"><h3>Brak ankiet w archiwum</h3><p>Zakończona ankieta pojawi się tutaj automatycznie po terminie lub ręcznym zakończeniu.</p></div></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-section" style="margin-bottom: 1.5rem;">
                    <div class="section-header">
                        <h2><?= $editingPoll ? 'Edytuj ankietę' : 'Nowa ankieta' ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if ($hasVotes): ?>
                            <div class="alert alert-warning"><span class="alert-message">W tej ankiecie oddano już głosy. Treści pytań i odpowiedzi nie można zmieniać.</span></div>
                            <div class="table-container"><div class="table-wrapper"><table class="table"><thead><tr><th>Odpowiedź</th><th>Głosy</th><th>Wynik</th></tr></thead><tbody><?php foreach ($editingPoll['options'] as $option): ?><tr><td><?= sanitize($option['option_text']) ?></td><td><?= (int) $option['votes'] ?></td><td><?= (int) $option['percentage'] ?>%</td></tr><?php endforeach; ?></tbody></table></div></div>
                        <?php else: ?>
                            <form method="post" class="admin-form-grid">
                                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                                <input type="hidden" name="action" value="save">
                                <input type="hidden" name="poll_id" value="<?= (int) ($editingPoll['id'] ?? 0) ?>">
                                <div class="form-group admin-form-wide"><label for="poll-question">Pytanie</label><input class="form-control" id="poll-question" name="question" maxlength="160" required value="<?= sanitize($editingPoll['question'] ?? '') ?>" placeholder="Np. Co powinno pojawiać się w serwisie częściej?"></div>
                                <div class="form-group admin-form-wide"><label>Odpowiedzi</label><div class="poll-option-fields"><?php foreach ($optionValues as $index => $value): ?><input class="form-control" name="options[]" maxlength="80" <?= $index < 2 ? 'required' : '' ?> value="<?= sanitize($value) ?>" placeholder="Odpowiedź <?= $index + 1 ?><?= $index >= 2 ? ' (opcjonalnie)' : '' ?>"><?php endforeach; ?></div><p class="form-help">Wpisz od 2 do 5 odpowiedzi. Po oddaniu pierwszego głosu treść ankiety jest blokowana.</p></div>
                                <div class="form-group"><label for="poll-starts-at">Start</label><input class="form-control" id="poll-starts-at" type="datetime-local" name="starts_at" value="<?= sanitize(pollAdminDateValue($editingPoll['starts_at'] ?? null)) ?>"><p class="form-help">Puste pole oznacza start od razu po aktywacji.</p></div>
                                <div class="form-group"><label for="poll-ends-at">Koniec</label><input class="form-control" id="poll-ends-at" type="datetime-local" name="ends_at" value="<?= sanitize(pollAdminDateValue($editingPoll['ends_at'] ?? null)) ?>"><p class="form-help">Puste pole oznacza zakończenie ręczne.</p></div>
                                <label class="checkbox-label admin-form-wide" for="poll-is-active"><input id="poll-is-active" type="checkbox" name="is_active" <?= !empty($editingPoll['is_active']) ? 'checked' : '' ?>><span>Aktywuj po zapisaniu. Aktywacja ukryje każdą inną aktywną ankietę.</span></label>
                                <div class="admin-form-actions admin-form-wide"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Zapisz ankietę</button></div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-section">
                    <div class="section-header"><h2>Bieżące ankiety</h2></div>
                    <?php if ($polls): ?>
                        <div class="table-container"><div class="table-wrapper"><table class="table"><thead><tr><th>Pytanie</th><th>Status</th><th>Okres</th><th>Głosy</th><th>Akcje</th></tr></thead><tbody>
                            <?php foreach ($polls as $poll): ?>
                                <?php $status = pollAdminStatus($poll); ?>
                                <tr>
                                    <td class="table-title-cell"><strong><?= sanitize($poll['question']) ?></strong></td>
                                    <td><span class="status-badge <?= $status === 'Aktywna' ? 'active' : 'inactive' ?>"><?= $status ?></span></td>
                                    <td><?= !empty($poll['starts_at']) ? formatDate($poll['starts_at'], 'd.m.Y H:i') : 'Od aktywacji' ?><br>— <?= !empty($poll['ends_at']) ? formatDate($poll['ends_at'], 'd.m.Y H:i') : 'Ręcznie' ?></td>
                                    <td><?= (int) $poll['votes_count'] ?></td>
                                    <td class="actions-col">
                                        <a class="action-btn edit-btn" href="<?= SITE_URL ?>/admin/polls.php?edit=<?= (int) $poll['id'] ?>" title="<?= (int) $poll['votes_count'] > 0 ? 'Wyniki ankiety' : 'Edytuj ankietę' ?>"><i class="fas <?= (int) $poll['votes_count'] > 0 ? 'fa-chart-bar' : 'fa-edit' ?>"></i></a>
                                        <form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="poll_id" value="<?= (int) $poll['id'] ?>"><?php if ((int) $poll['is_active'] === 1): ?><button class="action-btn" type="submit" title="Ukryj ankietę"><i class="fas fa-eye-slash"></i></button><?php else: ?><input type="hidden" name="is_active" value="1"><button class="action-btn approve-btn" type="submit" title="Aktywuj ankietę"><i class="fas fa-play"></i></button><?php endif; ?></form>
                                        <?php if ($status === 'Aktywna' || $status === 'Zaplanowana'): ?><form method="post" class="inline-form" onsubmit="return confirm('Zakończyć ankietę i przenieść ją do archiwum?');"><input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>"><input type="hidden" name="action" value="archive"><input type="hidden" name="poll_id" value="<?= (int) $poll['id'] ?>"><button class="action-btn" type="submit" title="Zakończ i archiwizuj"><i class="fas fa-box-archive"></i></button></form><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div></div>
                    <?php else: ?>
                        <div class="card"><div class="card-body text-center py-4"><h3>Brak bieżących ankiet</h3><p>Utwórz pierwszą ankietę widoczną w Kronice Miasta.</p></div></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
