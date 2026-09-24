<?php
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$limit = 6;
ensureAdModerationSchema();
ensureHomepageAdPositionSchema();

global $pdo;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save') {
            saveHomepageAdPositions(is_array($_POST['positions'] ?? null) ? $_POST['positions'] : [], $limit);
            $_SESSION['admin_message'] = 'Kolejność strony głównej została zapisana.';
            $_SESSION['admin_message_type'] = 'success';
        } elseif ($action === 'reset') {
            saveHomepageAdPositions([], $limit);
            $_SESSION['admin_message'] = 'Przywrócono automatyczny dobór ogłoszeń na stronie głównej.';
            $_SESSION['admin_message_type'] = 'success';
        }
    } catch (Throwable $exception) {
        $_SESSION['admin_message'] = $exception->getMessage();
        $_SESSION['admin_message_type'] = 'warning';
    }

    redirect(SITE_URL . '/admin/ads/homepage-order.php');
    exit();
}

$manualPositions = getHomepageManualPositions($limit);
$previewAds = getHomepageAds($limit);
$availableStatement = $pdo->query("SELECT a.*, ac.name AS category_name
    FROM ads a
    JOIN ad_categories ac ON a.category_id = ac.id
    WHERE a.is_active = 1 AND a.submission_status = 'approved'
    ORDER BY a.title ASC, a.id ASC");
$availableAds = $availableStatement->fetchAll();
$pageTitle = 'Kolejność strony głównej - Panel administracyjny';
$csrfToken = getAdminCsrfToken();
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
                    <h1>Kolejność strony głównej</h1>
                    <p>Ustaw ręcznie wybrane karty. Każde wolne miejsce nadal wypełnia automatyczny dobór ogłoszeń.</p>
                </div>
                <div class="admin-page-actions">
                    <form method="post" class="inline-form" onsubmit="return confirm('Przywrócić automatyczny dobór na wszystkich pozycjach?')">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="action" value="reset">
                        <button class="btn btn-secondary" type="submit"><i class="fas fa-random"></i> Przywróć automatykę</button>
                    </form>
                </div>
            </div>

            <?php if ($adminMessage !== ''): ?>
                <div class="alert alert-<?= $adminMessageType === 'warning' ? 'warning' : 'success' ?>">
                    <span class="alert-message"><?= sanitize($adminMessage) ?></span>
                </div>
            <?php endif; ?>

            <div class="dashboard-section" style="margin-bottom: 1.5rem;">
                <div class="section-header"><h2>Ustawienia pozycji</h2></div>
                <div class="card-body">
                    <p class="form-help">Wybierz ogłoszenie dla konkretnego miejsca albo pozostaw „Automatycznie”. Jedno ogłoszenie może wystąpić tylko raz. Zmiana kolejności nie wpływa na strony kategorii.</p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="action" value="save">
                        <div class="homepage-order-settings">
                            <?php for ($position = 1; $position <= $limit; $position++): ?>
                                <?php $manualAd = $manualPositions[$position] ?? null; ?>
                                <section class="homepage-order-setting">
                                    <div class="homepage-order-number"><?= $position ?></div>
                                    <div class="homepage-order-field">
                                        <label for="homepage-position-<?= $position ?>">Pozycja <?= $position ?></label>
                                        <select class="form-control select-control" id="homepage-position-<?= $position ?>" name="positions[<?= $position ?>]">
                                            <option value="0">Automatycznie</option>
                                            <?php foreach ($availableAds as $ad): ?>
                                                <option value="<?= (int) $ad['id'] ?>" <?= $manualAd && (int) $manualAd['id'] === (int) $ad['id'] ? 'selected' : '' ?>>
                                                    <?= sanitize(truncateText($ad['title'], 72)) ?> · <?= sanitize($ad['category_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="homepage-order-state <?= $manualAd ? 'is-manual' : '' ?>">
                                        <?= $manualAd ? 'Ręcznie' : 'Automatycznie' ?>
                                    </div>
                                </section>
                            <?php endfor; ?>
                        </div>
                        <div class="admin-form-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Zapisz kolejność</button></div>
                    </form>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header"><h2>Podgląd strony głównej</h2></div>
                <div class="homepage-order-preview-grid">
                    <?php foreach ($previewAds as $position => $ad): ?>
                        <article class="homepage-order-preview-card">
                            <div class="homepage-order-preview-label">Pozycja <?= $position + 1 ?> · <?= !empty($ad['homepage_position']) ? 'Ręcznie' : 'Automatycznie' ?></div>
                            <?php if (!empty($ad['image'])): ?>
                                <img src="<?= getImageUrl($ad['image']) ?>" alt="<?= sanitize($ad['title']) ?>">
                            <?php endif; ?>
                            <strong><?= sanitize(truncateText($ad['title'], 80)) ?></strong>
                            <span><?= sanitize($ad['category_name']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
