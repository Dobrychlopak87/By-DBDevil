<?php
// Panel administracyjny - Lista ogłoszeń
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

// Pobierz wszystkie ogłoszenia
global $pdo;
ensureAdModerationSchema();

// Filtrowanie
$categoryFilter = $_GET['category'] ?? null;
$statusFilter = $_GET['status'] ?? null;
$moderationFilter = $_GET['moderation'] ?? 'all';
$searchQuery = $_GET['search'] ?? null;
$validModerationFilters = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($moderationFilter, $validModerationFilters, true)) {
    $moderationFilter = 'all';
}

// Buduj zapytanie
$sql = "SELECT a.*, ac.name as category_name, ac.slug as category_slug 
        FROM ads a 
        JOIN ad_categories ac ON a.category_id = ac.id 
        WHERE 1=1";

$params = [];

if ($categoryFilter && $categoryFilter !== 'all') {
    $sql .= " AND ac.slug = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter && $statusFilter !== 'all') {
    $statusValue = $statusFilter === 'active' ? 1 : 0;
    $sql .= " AND a.is_active = ?";
    $params[] = $statusValue;
}

if ($moderationFilter !== 'all') {
    $sql .= " AND a.submission_status = ?";
    $params[] = $moderationFilter;
}

if ($searchQuery) {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR a.location LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$sql .= " ORDER BY a.is_featured DESC, a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll();

// Pobierz kategorie ogłoszeń
$adCategories = getAdCategories();

// Pobierz statystyki
$totalAds = $pdo->query("SELECT COUNT(*) as count FROM ads")->fetch()['count'];
$activeAds = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE is_active = 1")->fetch()['count'];
$inactiveAds = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE is_active = 0")->fetch()['count'];
$pendingAds = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE submission_status = 'pending'")->fetch()['count'];

$pageTitle = 'Ogłoszenia - Panel administracyjny';
$adminMessage = $_SESSION['admin_message'] ?? '';
$adminMessageType = $_SESSION['admin_message_type'] ?? 'success';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
$csrfToken = getAdminCsrfToken();

// Obsługa masowych akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $action = (string) $_POST['bulk_action'];
    $selectedIds = $_POST['selected_ids'] ?? [];
    $validBulkActions = ['activate', 'deactivate', 'feature', 'unfeature', 'approve', 'reject', 'delete'];
    
    if (is_array($selectedIds) && !empty($selectedIds) && in_array($action, $validBulkActions, true)) {
        foreach ($selectedIds as $id) {
            $id = (int)$id;
            
            switch ($action) {
                case 'activate':
                    $pdo->prepare("UPDATE ads SET is_active = 1 WHERE id = ?")->execute([$id]);
                    break;
                case 'deactivate':
                    $pdo->prepare("UPDATE ads SET is_active = 0 WHERE id = ?")->execute([$id]);
                    break;
                case 'feature':
                    $pdo->prepare("UPDATE ads SET is_featured = 1 WHERE id = ?")->execute([$id]);
                    break;
                case 'unfeature':
                    $pdo->prepare("UPDATE ads SET is_featured = 0 WHERE id = ?")->execute([$id]);
                    break;
                case 'approve':
                    $pdo->prepare("UPDATE ads SET submission_status = 'approved', is_active = 1 WHERE id = ?")->execute([$id]);
                    break;
                case 'reject':
                    $pdo->prepare("UPDATE ads SET submission_status = 'rejected', is_active = 0 WHERE id = ?")->execute([$id]);
                    break;
                case 'delete':
                    // Usuń również nieopublikowane zgłoszenie i jego zdjęcie główne.
                    $imageStmt = $pdo->prepare("SELECT image FROM ads WHERE id = ?");
                    $imageStmt->execute([$id]);
                    $ad = $imageStmt->fetch();
                    if ($ad && !empty($ad['image'])) {
                        deleteFile($ad['image']);
                    }
                    $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
                    break;
            }
        }
        
        // Odśwież stronę
        redirect(SITE_URL . '/admin/ads/index.php?' . http_build_query($_GET));
        exit();
    }
}
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

    <div class="admin-container">
        <!-- Admin Header -->
        <?php require_once dirname(__DIR__, 3) . '/admin/includes/admin-header.php'; ?>
        
        <!-- Ads Content -->
        <div class="admin-content">
            <!-- Page Header -->
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Ogłoszenia</h1>
                    <p>Zarządzaj ogłoszeniami na stronie</p>
                </div>
                <div class="admin-page-actions">
                    <a href="<?= SITE_URL ?>/admin/ads/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Dodaj ogłoszenie
                    </a>
                    <a href="<?= SITE_URL ?>/admin/ads/categories.php" class="btn btn-secondary">
                        <i class="fas fa-tags"></i> Kategorie
                    </a>
                </div>
            </div>
            
            <?php if ($adminMessage !== ''): ?>
                <div class="alert alert-<?= $adminMessageType === 'warning' ? 'warning' : 'success' ?>">
                    <i class="fas <?= $adminMessageType === 'warning' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    <span class="alert-message"><?= sanitize($adminMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom: 1.5rem;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Wszystkie</h3>
                        <p class="stat-number"><?= $totalAds ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Aktywne</h3>
                        <p class="stat-number"><?= $activeAds ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f5576c 0%, #e1465d 100%);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Nieaktywne</h3>
                        <p class="stat-number"><?= $inactiveAds ?></p>
                    </div>
                </div>
                <a href="<?= SITE_URL ?>/admin/ads/index.php?moderation=pending" class="stat-card stat-card-link">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Do moderacji</h3>
                        <p class="stat-number"><?= $pendingAds ?></p>
                        <p class="stat-subtitle">Zgłoszenia użytkowników</p>
                    </div>
                </a>
            </div>
            
            <!-- Filters -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3>Filtrowanie</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="page" value="ads">
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 200px;">
                            <select name="category" class="form-control select-control">
                                <option value="all" <?php echo ($categoryFilter === null || $categoryFilter === 'all') ? 'selected' : ''; ?>>Wszystkie kategorie</option>
                                <?php foreach ($adCategories as $category): ?>
                                    <option value="<?= $category['slug'] ?>" <?php echo $categoryFilter === $category['slug'] ? 'selected' : ''; ?>>
                                        <?= sanitize($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 150px;">
                            <select name="status" class="form-control select-control">
                                <option value="all" <?php echo ($statusFilter === null || $statusFilter === 'all') ? 'selected' : ''; ?>>Wszystkie widoczności</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Widoczne</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Ukryte</option>
                            </select>
                        </div>

                        <div class="form-group mb-0 flex-1" style="min-width: 190px;">
                            <select name="moderation" class="form-control select-control">
                                <option value="all" <?= $moderationFilter === 'all' ? 'selected' : '' ?>>Wszystkie etapy moderacji</option>
                                <option value="pending" <?= $moderationFilter === 'pending' ? 'selected' : '' ?>>Oczekujące na akceptację</option>
                                <option value="approved" <?= $moderationFilter === 'approved' ? 'selected' : '' ?>>Zaakceptowane</option>
                                <option value="rejected" <?= $moderationFilter === 'rejected' ? 'selected' : '' ?>>Odrzucone</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 200px;">
                            <input 
                                type="text" 
                                name="search" 
                                class="form-control" 
                                placeholder="Szukaj po tytule, opisie lub lokalizacji..."
                                value="<?= sanitize($searchQuery ?? '') ?>"
                            >
                        </div>
                        
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Szukaj
                            </button>
                        </div>
                        
                        <div class="form-group mb-0">
                            <a href="<?= SITE_URL ?>/admin/ads/index.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Wyczyść
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <?php if (!empty($ads)): ?>
                <form method="POST" action="" class="bulk-form mb-2">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <div class="bulk-toolbar">
                        <select name="bulk_action" class="form-control select-control" style="width: 220px;" aria-label="Wybierz akcję zbiorczą">
                            <option value="">Akcja zbiorcza...</option>
                            <option value="activate">Aktywuj wybrane</option>
                            <option value="deactivate">Deaktywuj wybrane</option>
                            <option value="feature">Oznacz jako wyróżnione</option>
                            <option value="unfeature">Odznacz wyróżnione</option>
                            <option value="approve">Zaakceptuj i opublikuj</option>
                            <option value="reject">Odrzuć zgłoszenia</option>
                            <option value="delete">Usuń wybrane</option>
                        </select>
                        <button type="submit" class="btn btn-warning" data-bulk-submit onclick="return confirm('Czy na pewno chcesz wykonać tę akcję na wybranych ogłoszeniach?')">
                            <i class="fas fa-check"></i> Wykonaj
                        </button>
                        <span class="selection-summary" data-selection-summary aria-live="polite">Nie wybrano pozycji</span>
                    </div>
                    
                    <!-- Ads Table -->
                    <div class="table-container mt-2">
                        <div class="table-header">
                            <h2>
                                Lista ogłoszeń 
                                <span style="color: #999; font-size: 0.9rem; font-weight: normal;">
                                    (<?= count($ads) ?>)
                                </span>
                            </h2>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th style="width: 60px;">Zdjęcie</th>
                                        <th>Tytuł</th>
                                        <th>Kategoria</th>
                                        <th>Lokalizacja</th>
                                        <th>Ocena</th>
                                        <th>Moderacja</th>
                                        <th>Widoczność</th>
                                        <th style="width: 150px;">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ads as $ad): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_ids[]" value="<?= $ad['id'] ?>">
                                            </td>
                                            <td>
                                                <img 
                                                    src="<?= getImageUrl($ad['image']) ?>" 
                                                    alt="<?= sanitize($ad['title']) ?>" 
                                                    class="table-image"
                                                >
                                            </td>
                                            <td class="table-title-cell">
                                                <a href="<?= SITE_URL ?>/admin/ads/edit.php?id=<?= $ad['id'] ?>" style="color: var(--admin-primary); text-decoration: none; font-weight: 500;">
                                                    <?= truncateText(sanitize($ad['title']), 50) ?>
                                                </a>
                                                <?php if ($ad['is_featured']): ?>
                                                    <span class="status-badge featured" style="margin-left: 0.5rem;">Wyróżnione</span>
                                                <?php endif; ?>
                                                <span class="table-meta-line">Dodano: <?= formatDate($ad['created_at'], 'd.m.Y H:i') ?></span>
                                            </td>
                                            <td><?= sanitize($ad['category_name']) ?></td>
                                            <td><?= sanitize($ad['location'] ?? '—') ?></td>
                                            <td>
                                                <span style="color: #ffd700; font-weight: 600;">
                                                    ⭐ <?= $ad['rating'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge moderation-<?= sanitize($ad['submission_status'] ?? 'approved') ?>">
                                                    <?= sanitize(getSubmissionStatusLabel($ad['submission_status'] ?? 'approved')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= $ad['is_active'] ? 'active' : 'inactive' ?>">
                                                    <?= $ad['is_active'] ? 'Widoczne' : 'Ukryte' ?>
                                                </span>
                                            </td>
                                            <td class="actions-col">
                                                <?php if (($ad['submission_status'] ?? 'approved') === 'approved' && $ad['is_active']): ?>
                                                    <a href="<?= SITE_URL ?>/ad.php?id=<?= (int) $ad['id'] ?>" class="action-btn view-btn" title="Podgląd publiczny" target="_blank" rel="noopener">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= SITE_URL ?>/admin/ads/edit.php?id=<?= $ad['id'] ?>" class="action-btn edit-btn" title="Edytuj">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="submit" form="ad-delete-<?= (int) $ad['id'] ?>" class="action-btn delete-btn" title="Usuń" onclick="return confirm('Czy na pewno chcesz usunąć to ogłoszenie?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php if (($ad['submission_status'] ?? 'approved') === 'pending'): ?>
                                                    <button type="submit" form="ad-toggle-approve-<?= (int) $ad['id'] ?>" class="action-btn approve-btn" title="Zaakceptuj i opublikuj">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" form="ad-toggle-reject-<?= (int) $ad['id'] ?>" class="action-btn reject-btn" title="Odrzuć zgłoszenie" onclick="return confirm('Odrzucić to zgłoszenie?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php elseif (($ad['submission_status'] ?? 'approved') === 'approved' && $ad['is_active']): ?>
                                                    <button type="submit" form="ad-toggle-deactivate-<?= (int) $ad['id'] ?>" class="action-btn" title="Ukryj ogłoszenie" style="background: rgba(245, 87, 108, 0.15); color: var(--admin-danger);">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </button>
                                                <?php elseif (($ad['submission_status'] ?? 'approved') === 'approved'): ?>
                                                    <button type="submit" form="ad-toggle-activate-<?= (int) $ad['id'] ?>" class="action-btn" title="Opublikuj ogłoszenie" style="background: rgba(67, 233, 123, 0.15); color: var(--admin-success);">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
                <?php foreach ($ads as $ad): ?>
                    <form id="ad-delete-<?= (int) $ad['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/ads/delete.php" hidden>
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                    </form>
                    <?php foreach (['activate', 'approve', 'reject', 'deactivate', 'feature', 'unfeature'] as $action): ?>
                        <form id="ad-toggle-<?= $action ?>-<?= (int) $ad['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/ads/toggle.php" hidden>
                            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                            <input type="hidden" name="action" value="<?= $action ?>">
                        </form>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-bullhorn" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <h3>Brak ogłoszeń</h3>
                        <p>Nie znaleziono żadnych ogłoszeń pasujących do kryteriów.</p>
                        <a href="<?= SITE_URL ?>/admin/ads/add.php" class="btn btn-primary mt-2">
                            <i class="fas fa-plus"></i> Dodaj pierwsze ogłoszenie
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Zaznacz/wyczyść wszystkie checkboxy
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }
        
        // Pokaż/ukryj submenu na mobile
        document.querySelectorAll('.has-submenu > .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    const submenu = this.nextElementSibling;
                    const arrow = this.querySelector('.submenu-arrow');
                    
                    if (submenu) {
                        e.preventDefault();
                        submenu.classList.toggle('active');
                        arrow.classList.toggle('active');
                    }
                }
            });
        });
    </script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
