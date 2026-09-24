<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'Menu strony - Panel administracyjny';
$csrfToken = getAdminCsrfToken();
ensurePublicMenuSchema();
global $pdo;
$allowedKeys = ['ads', 'blog', 'chatroom', 'about', 'cooperation', 'report'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_menu'])) {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $items = $_POST['items'] ?? [];
    $stmt = $pdo->prepare('UPDATE public_menu_items SET label = ?, is_active = ?, display_order = ? WHERE id = ? AND menu_key = ?');

    foreach ($items as $id => $item) {
        $menuKey = (string) ($item['menu_key'] ?? '');
        if (!in_array($menuKey, $allowedKeys, true)) {
            continue;
        }
        $label = trim((string) ($item['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        $label = function_exists('mb_substr') ? mb_substr($label, 0, 120) : substr($label, 0, 120);
        $isActive = isset($item['is_active']) ? 1 : 0;
        $displayOrder = (int) ($item['display_order'] ?? 0);
        $stmt->execute([$label, $isActive, $displayOrder, (int) $id, $menuKey]);
    }

    $_SESSION['admin_message'] = 'Ustawienia menu zostały zapisane.';
    $_SESSION['admin_message_type'] = 'success';
    redirect(SITE_URL . '/admin/menu.php');
    exit();
}

$menuItems = $pdo->query('SELECT * FROM public_menu_items ORDER BY display_order ASC, id ASC')->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="admin-container">
        <?php require_once __DIR__ . '/includes/admin-header.php'; ?>
        <div class="admin-content">
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Menu strony</h1>
                    <p>Edytuj nazwy, widoczność i kolejność głównych pozycji menu.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="alert alert-<?= $_SESSION['admin_message_type'] ?? 'info' ?>">
                    <span class="alert-message"><?= sanitize($_SESSION['admin_message']) ?></span>
                    <button class="alert-close" type="button" onclick="this.parentElement.style.display='none'">×</button>
                </div>
                <?php unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); ?>
            <?php endif; ?>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Pozycje główne</h2>
                </div>
                <div class="card-body">
                    <p class="form-help">Kategorie Ogłoszeń i Kroniki miasta są zawsze pobierane automatycznie z aktywnych kategorii utworzonych w panelu. Tutaj zarządzasz wyłącznie pozycjami głównymi menu.</p>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="update_menu" value="1">
                        <div class="menu-settings-list">
                            <?php foreach ($menuItems as $item): ?>
                                <section class="menu-settings-row">
                                    <input type="hidden" name="items[<?= (int) $item['id'] ?>][menu_key]" value="<?= sanitize($item['menu_key']) ?>">
                                    <div class="form-group">
                                        <label for="menu-label-<?= (int) $item['id'] ?>">Nazwa pozycji</label>
                                        <input class="form-control" id="menu-label-<?= (int) $item['id'] ?>" type="text" name="items[<?= (int) $item['id'] ?>][label]" value="<?= sanitize($item['label']) ?>" maxlength="120" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="menu-order-<?= (int) $item['id'] ?>">Kolejność</label>
                                        <input class="form-control" id="menu-order-<?= (int) $item['id'] ?>" type="number" name="items[<?= (int) $item['id'] ?>][display_order]" value="<?= (int) $item['display_order'] ?>" step="10">
                                    </div>
                                    <label class="checkbox-label" for="menu-visible-<?= (int) $item['id'] ?>">
                                        <input id="menu-visible-<?= (int) $item['id'] ?>" type="checkbox" name="items[<?= (int) $item['id'] ?>][is_active]" <?= $item['is_active'] ? 'checked' : '' ?>>
                                        <span>Widoczna</span>
                                    </label>
                                </section>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-primary" type="submit">Zapisz menu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
