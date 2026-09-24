<?php
// Panel administracyjny - Lista artykułów blogowych
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'Artykuły blogowe - Panel administracyjny';
$csrfToken = getAdminCsrfToken();
ensureBlogModerationSchema();

// Pobierz wszystkie artykuły blogowe
global $pdo;

// Filtrowanie
$categoryFilter = $_GET['category'] ?? null;
$statusFilter = $_GET['status'] ?? null;
$moderationFilter = $_GET['moderation'] ?? null;
$searchQuery = $_GET['search'] ?? null;

// Buduj zapytanie
$sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug 
        FROM blog_posts bp 
        JOIN blog_categories bc ON bp.category_id = bc.id 
        WHERE 1=1";

$params = [];

if ($categoryFilter && $categoryFilter !== 'all') {
    $sql .= " AND bc.slug = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter && $statusFilter !== 'all') {
    $statusValue = $statusFilter === 'active' ? 1 : 0;
    $sql .= " AND bp.is_active = ?";
    $params[] = $statusValue;
}

if (in_array($moderationFilter, ['pending', 'approved', 'rejected'], true)) {
    $sql .= " AND bp.submission_status = ?";
    $params[] = $moderationFilter;
}

if ($searchQuery) {
    $sql .= " AND (bp.title LIKE ? OR bp.content LIKE ? OR bp.excerpt LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$sql .= " ORDER BY bp.is_featured DESC, bp.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Pobierz kategorie bloga
$blogCategories = getBlogCategories();

// Pobierz statystyki
$totalPosts = $pdo->query("SELECT COUNT(*) as count FROM blog_posts")->fetch()['count'];
$activePosts = $pdo->query("SELECT COUNT(*) as count FROM blog_posts WHERE is_active = 1")->fetch()['count'];
$inactivePosts = $pdo->query("SELECT COUNT(*) as count FROM blog_posts WHERE is_active = 0")->fetch()['count'];
$pendingPosts = $pdo->query("SELECT COUNT(*) as count FROM blog_posts WHERE submission_status = 'pending'")->fetch()['count'];

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
    $validBulkActions = ['activate', 'approve', 'reject', 'deactivate', 'feature', 'unfeature', 'delete'];
    
    if (is_array($selectedIds) && !empty($selectedIds) && in_array($action, $validBulkActions, true)) {
        foreach ($selectedIds as $id) {
            $id = (int)$id;
            
            switch ($action) {
                case 'activate':
                    $pdo->prepare("UPDATE blog_posts SET is_active = 1 WHERE id = ?")->execute([$id]);
                    break;
                case 'approve':
                    $pdo->prepare("UPDATE blog_posts SET submission_status = 'approved', is_active = 1 WHERE id = ?")->execute([$id]);
                    break;
                case 'reject':
                    $pdo->prepare("UPDATE blog_posts SET submission_status = 'rejected', is_active = 0 WHERE id = ?")->execute([$id]);
                    break;
                case 'deactivate':
                    $pdo->prepare("UPDATE blog_posts SET is_active = 0 WHERE id = ?")->execute([$id]);
                    break;
                case 'feature':
                    $pdo->prepare("UPDATE blog_posts SET is_featured = 1 WHERE id = ?")->execute([$id]);
                    break;
                case 'unfeature':
                    $pdo->prepare("UPDATE blog_posts SET is_featured = 0 WHERE id = ?")->execute([$id]);
                    break;
                case 'delete':
                    // Usuń artykuł i jego zdjęcie
                    $post = getBlogPostById($id, true);
                    if ($post && !empty($post['image'])) {
                        deleteFile($post['image']);
                    }
                    $pdo->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$id]);
                    break;
            }
        }
        
        // Odśwież stronę
        redirect(SITE_URL . '/admin/blog/index.php?' . http_build_query($_GET));
        exit();
    }
}
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

    <div class="admin-container">
        <!-- Admin Header -->
        <?php require_once dirname(__DIR__, 3) . '/admin/includes/admin-header.php'; ?>
        
        <!-- Blog Posts Content -->
        <div class="admin-content">
            <!-- Page Header -->
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Artykuły blogowe</h1>
                    <p>Zarządzaj artykułami na stronie</p>
                </div>
                <div class="admin-page-actions">
                    <a href="<?= SITE_URL ?>/admin/blog/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Dodaj artykuł
                    </a>
                    <a href="<?= SITE_URL ?>/admin/blog/categories.php" class="btn btn-secondary">
                        <i class="fas fa-folder-open"></i> Kategorie
                    </a>
                    <a href="<?= SITE_URL ?>/admin/blog/comments.php" class="btn btn-secondary">
                        <i class="fas fa-comments"></i> Komentarze
                    </a>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom: 1.5rem;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Wszystkie</h3>
                        <p class="stat-number"><?= $totalPosts ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Aktywne</h3>
                        <p class="stat-number"><?= $activePosts ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f5576c 0%, #e1465d 100%);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Nieaktywne</h3>
                        <p class="stat-number"><?= $inactivePosts ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3>Filtrowanie</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="page" value="blog">
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 200px;">
                            <select name="category" class="form-control select-control">
                                <option value="all" <?php echo ($categoryFilter === null || $categoryFilter === 'all') ? 'selected' : ''; ?>>Wszystkie kategorie</option>
                                <?php foreach ($blogCategories as $category): ?>
                                    <option value="<?= $category['slug'] ?>" <?php echo $categoryFilter === $category['slug'] ? 'selected' : ''; ?>>
                                        <?= sanitize($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 150px;">
                            <select name="status" class="form-control select-control">
                                <option value="all" <?php echo ($statusFilter === null || $statusFilter === 'all') ? 'selected' : ''; ?>>Wszystkie statusy</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktywne</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Nieaktywne</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 170px;">
                            <select name="moderation" class="form-control select-control">
                                <option value="all" <?= !in_array($moderationFilter, ['pending', 'approved', 'rejected'], true) ? 'selected' : '' ?>>Wszystkie zgłoszenia</option>
                                <option value="pending" <?= $moderationFilter === 'pending' ? 'selected' : '' ?>>Oczekujące</option>
                                <option value="approved" <?= $moderationFilter === 'approved' ? 'selected' : '' ?>>Zaakceptowane</option>
                                <option value="rejected" <?= $moderationFilter === 'rejected' ? 'selected' : '' ?>>Odrzucone</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-0 flex-1" style="min-width: 200px;">
                            <input 
                                type="text" 
                                name="search" 
                                class="form-control" 
                                placeholder="Szukaj po tytule lub treści..."
                                value="<?= sanitize($searchQuery ?? '') ?>"
                            >
                        </div>
                        
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Szukaj
                            </button>
                        </div>
                        
                        <div class="form-group mb-0">
                            <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Wyczyść
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <?php if (!empty($posts)): ?>
                <form method="POST" action="" class="bulk-form">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <div class="bulk-toolbar">
                        <select name="bulk_action" class="form-control select-control" style="width: 220px;" aria-label="Wybierz akcję zbiorczą">
                            <option value="">Akcja zbiorcza...</option>
                            <option value="approve">Zaakceptuj i opublikuj wybrane</option>
                            <option value="reject">Odrzuć wybrane</option>
                            <option value="activate">Aktywuj wybrane</option>
                            <option value="deactivate">Deaktywuj wybrane</option>
                            <option value="feature">Oznacz jako wyróżnione</option>
                            <option value="unfeature">Odznacz wyróżnione</option>
                            <option value="delete">Usuń wybrane</option>
                        </select>
                        <button type="submit" class="btn btn-warning" data-bulk-submit onclick="return confirm('Czy na pewno chcesz wykonać tę akcję na wybranych artykułach?')">
                            <i class="fas fa-check"></i> Wykonaj
                        </button>
                        <span class="selection-summary" data-selection-summary aria-live="polite">Nie wybrano pozycji</span>
                    </div>
                    
                    <!-- Blog Posts Table -->
                    <div class="table-container mt-2">
                        <div class="table-header">
                            <h2>
                                Lista artykułów 
                                <span style="color: #999; font-size: 0.9rem; font-weight: normal;">
                                    (<?= count($posts) ?>)
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
                                        <th>Data utworzenia</th>
                                        <th>Status</th>
                                        <th style="width: 120px;">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_ids[]" value="<?= $post['id'] ?>">
                                            </td>
                                            <td>
                                                <img 
                                                    src="<?= getImageUrl($post['image']) ?>" 
                                                    alt="<?= sanitize($post['title']) ?>" 
                                                    class="table-image"
                                                >
                                            </td>
                                            <td class="table-title-cell">
                                                <a href="<?= SITE_URL ?>/admin/blog/edit.php?id=<?= $post['id'] ?>" style="color: var(--admin-primary); text-decoration: none; font-weight: 500;">
                                                    <?= truncateText(sanitize($post['title']), 50) ?>
                                                </a>
                                                <?php if ($post['is_featured']): ?>
                                                    <span class="status-badge featured" style="margin-left: 0.5rem;">Wyróżniony</span>
                                                <?php endif; ?>
                                                <span class="table-meta-line">Slug: <?= sanitize($post['slug']) ?></span>
                                                <?php if (!empty($post['author_signature'])): ?>
                                                    <span class="table-meta-line">Podpis: <?= sanitize($post['author_signature']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= sanitize($post['category_name']) ?></td>
                                            <td><?= formatDate($post['created_at'], 'd.m.Y H:i') ?></td>
                                            <td>
                                                <span class="status-badge moderation-<?= sanitize($post['submission_status'] ?? 'approved') ?>">
                                                    <?= sanitize(getSubmissionStatusLabel($post['submission_status'] ?? 'approved')) ?>
                                                </span>
                                                <span class="status-badge <?= $post['is_active'] ? 'active' : 'inactive' ?>" style="margin-left: .3rem;">
                                                    <?= $post['is_active'] ? 'Widoczny' : 'Ukryty' ?>
                                                </span>
                                            </td>
                                            <td class="actions-col">
                                                <?php if (($post['submission_status'] ?? 'approved') === 'pending'): ?>
                                                    <button type="submit" form="blog-toggle-approve-<?= (int) $post['id'] ?>" class="action-btn approve-btn" title="Zaakceptuj i opublikuj">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" form="blog-toggle-reject-<?= (int) $post['id'] ?>" class="action-btn reject-btn" title="Odrzuć zgłoszenie" onclick="return confirm('Odrzucić to zgłoszenie?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?= SITE_URL ?>/admin/blog/edit.php?id=<?= $post['id'] ?>" class="action-btn edit-btn" title="Edytuj">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (($post['submission_status'] ?? 'approved') === 'approved' && $post['is_active']): ?>
                                                    <a href="<?= SITE_URL ?>/post.php?slug=<?= $post['slug'] ?>" class="action-btn view-btn" title="Zobacz" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button type="submit" form="blog-delete-<?= (int) $post['id'] ?>" class="action-btn delete-btn" title="Usuń" onclick="return confirm('Czy na pewno chcesz usunąć ten artykuł?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php if ($post['is_active']): ?>
                                                    <button type="submit" form="blog-toggle-deactivate-<?= (int) $post['id'] ?>" class="action-btn" title="Deaktywuj" style="background: rgba(245, 87, 108, 0.15); color: var(--admin-danger);">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" form="blog-toggle-activate-<?= (int) $post['id'] ?>" class="action-btn" title="Aktywuj" style="background: rgba(67, 233, 123, 0.15); color: var(--admin-success);">
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
                <?php foreach ($posts as $post): ?>
                    <form id="blog-delete-<?= (int) $post['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/delete.php" hidden>
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                    </form>
                    <?php foreach (['activate', 'approve', 'reject', 'deactivate', 'feature', 'unfeature'] as $action): ?>
                        <form id="blog-toggle-<?= $action ?>-<?= (int) $post['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/toggle.php" hidden>
                            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <input type="hidden" name="action" value="<?= $action ?>">
                        </form>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-newspaper" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <h3>Brak artykułów</h3>
                        <p>Nie znaleziono żadnych artykułów pasujących do kryteriów.</p>
                        <a href="<?= SITE_URL ?>/admin/blog/add.php" class="btn btn-primary mt-2">
                            <i class="fas fa-plus"></i> Dodaj pierwszy artykuł
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
    </script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
