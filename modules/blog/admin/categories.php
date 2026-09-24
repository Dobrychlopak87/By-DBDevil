<?php
// Panel administracyjny - Zarządzanie kategoriami bloga
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'Kategorie bloga - Panel administracyjny';
$csrfToken = getAdminCsrfToken();

// Pobierz kategorie bloga
global $pdo;
ensureCategoryIconSchema();
$blogCategories = $pdo->query("SELECT * FROM blog_categories ORDER BY display_order ASC")->fetchAll();
$iconOptions = getCategoryIconOptions();

// Obsługa dodawania nowej kategorii
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $icon = $_POST['icon'] ?? '';
    $icon = array_key_exists($icon, $iconOptions) ? $icon : 'all';
    $iconUpload = uploadCategoryIconSvg($_FILES['icon_file'] ?? []);
    if (!$iconUpload['empty']) {
        if (!$iconUpload['success']) {
            $_SESSION['admin_message'] = $iconUpload['message'];
            $_SESSION['admin_message_type'] = 'danger';
            redirect(SITE_URL . '/admin/blog/categories.php');
            exit();
        }
        $icon = $iconUpload['value'];
    }
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    if (!empty($name)) {
        // Generuj slug, jeśli nie podano
        if (empty($slug)) {
            $slug = generateSlug($name);
        }
        
        // Sprawdź, czy slug jest unikalny
        $stmt = $pdo->prepare("SELECT id FROM blog_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . uniqid();
        }
        
        // Dodaj kategorię
        $stmt = $pdo->prepare("INSERT INTO blog_categories (name, slug, icon, display_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $icon, $displayOrder]);
        
        // Odśwież listę kategorii
        redirect(SITE_URL . '/admin/blog/categories.php');
        exit();
    }
}

// Obsługa aktualizacji kategorii
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $icon = $_POST['icon'] ?? '';
    $icon = array_key_exists($icon, $iconOptions) ? $icon : 'all';
    $existingStmt = $pdo->prepare("SELECT icon FROM blog_categories WHERE id = ? LIMIT 1");
    $existingStmt->execute([$id]);
    $existingCategory = $existingStmt->fetch();
    $oldIcon = $existingCategory['icon'] ?? '';
    $iconUpload = uploadCategoryIconSvg($_FILES['icon_file'] ?? []);
    if (!$iconUpload['empty']) {
        if (!$iconUpload['success']) {
            $_SESSION['admin_message'] = $iconUpload['message'];
            $_SESSION['admin_message_type'] = 'danger';
            redirect(SITE_URL . '/admin/blog/categories.php');
            exit();
        }
        $icon = $iconUpload['value'];
    }
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id && !empty($name)) {
        // Sprawdź, czy slug jest unikalny (wykluczając obecną kategorię)
        if (!empty($slug)) {
            $stmt = $pdo->prepare("SELECT id FROM blog_categories WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug .= '-' . uniqid();
            }
        } else {
            $slug = generateSlug($name);
        }
        
        // Zaktualizuj kategorię
        $stmt = $pdo->prepare("UPDATE blog_categories SET name = ?, slug = ?, icon = ?, display_order = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $icon, $displayOrder, $isActive, $id]);
        if ($oldIcon !== $icon) {
            deleteCustomCategoryIcon($oldIcon);
        }
        
        // Odśwież listę kategorii
        redirect(SITE_URL . '/admin/blog/categories.php');
        exit();
    }
}

// Obsługa usunięcia kategorii
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        http_response_code(400);
        exit;
    }
    
    // Sprawdź, czy kategoria istnieje
    $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if ($category) {
        // Sprawdź, czy kategoria jest używana w artykułach
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $_SESSION['admin_message'] = 'Nie można usunąć kategorii, która jest używana w artykułach.';
            $_SESSION['admin_message_type'] = 'danger';
        } else {
            // Usuń kategorię oraz powiązaną własną ikonę SVG.
            deleteCustomCategoryIcon($category['icon'] ?? '');
            $stmt = $pdo->prepare("DELETE FROM blog_categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['admin_message'] = 'Kategoria została usunięta pomyślnie.';
            $_SESSION['admin_message_type'] = 'success';
        }
    }
    
    redirect(SITE_URL . '/admin/blog/categories.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'move') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $direction = (string) ($_POST['direction'] ?? '');
        if (!$id || !in_array($direction, ['up', 'down'], true)) {
            throw new InvalidArgumentException('Nieprawidłowa zmiana kolejności kategorii.');
        }

        $ordered = $pdo->query('SELECT id FROM blog_categories ORDER BY display_order ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
        $currentIndex = array_search((string) $id, array_map('strval', $ordered), true);
        $targetIndex = $currentIndex === false ? false : $currentIndex + ($direction === 'up' ? -1 : 1);
        if ($targetIndex === false || $targetIndex < 0 || $targetIndex >= count($ordered)) {
            throw new RuntimeException('Ta kategoria nie może zostać przesunięta dalej.');
        }

        [$ordered[$currentIndex], $ordered[$targetIndex]] = [$ordered[$targetIndex], $ordered[$currentIndex]];
        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE blog_categories SET display_order = ? WHERE id = ?');
        foreach ($ordered as $index => $categoryId) {
            $update->execute([$index, (int) $categoryId]);
        }
        $pdo->commit();
        $_SESSION['admin_message'] = 'Kolejność kategorii została zmieniona.';
        $_SESSION['admin_message_type'] = 'success';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['admin_message'] = $exception->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }

    redirect(SITE_URL . '/admin/blog/categories.php');
    exit();
}

// Obsługa zmiany kolejności (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $items = $_POST['items'] ?? [];
    
    foreach ($items as $index => $id) {
        $stmt = $pdo->prepare("UPDATE blog_categories SET display_order = ? WHERE id = ?");
        $stmt->execute([$index, $id]);
    }
    
    echo json_encode(['success' => true]);
    exit();
}
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

    <div class="admin-container">
        <!-- Admin Header -->
        <?php require_once dirname(__DIR__, 3) . '/admin/includes/admin-header.php'; ?>
        
        <!-- Categories Content -->
        <div class="admin-content">
            <!-- Page Header -->
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Kategorie bloga</h1>
                    <p>Zarządzaj kategoriami artykułów blogowych</p>
                </div>
                <div class="admin-page-actions">
                    <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Powrót do artykułów
                    </a>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="alert alert-<?= $_SESSION['admin_message_type'] ?? 'info' ?>">
                    <i class="fas fa-<?= $_SESSION['admin_message_type'] === 'success' ? 'check' : ($_SESSION['admin_message_type'] === 'danger' ? 'exclamation' : 'info') ?>-circle"></i>
                    <span class="alert-message"><?= sanitize($_SESSION['admin_message']) ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
                </div>
                <?php unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); ?>
            <?php endif; ?>
            
            <div class="dashboard-sections">
                <!-- Add Category Form -->
                <div class="dashboard-section" style="flex: 0 0 400px;">
                    <div class="section-header">
                        <h2>Dodaj nową kategorię</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                            <input type="hidden" name="add_category" value="1">
                            
                            <div class="form-group">
                                <label for="name">Nazwa *</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    class="form-control" 
                                    placeholder="np. Na bieżąco"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="slug">Slug (opcjonalny)</label>
                                <input 
                                    type="text" 
                                    id="slug" 
                                    name="slug" 
                                    class="form-control" 
                                    placeholder="np. na-biezaco (wygeneruje się automatycznie)"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="icon">Ikona kategorii</label>
                                <select id="icon" name="icon" class="form-control select-control">
                                    <?php foreach ($iconOptions as $iconKey => $iconLabel): ?>
                                        <option value="<?= sanitize($iconKey) ?>"><?= sanitize($iconLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group icon-upload-group">
                                <label for="icon-file">Lub dodaj własną ikonę SVG</label>
                                <input type="file" id="icon-file" name="icon_file" class="form-control icon-file-input" accept="image/svg+xml,.svg" data-svg-preview="new-icon-preview">
                                <span class="form-hint">Maks. 300 KB. Plik SVG zastępuje wybór z listy po zapisaniu.</span>
                                <div id="new-icon-preview" class="icon-upload-preview" aria-live="polite"></div>
                            </div>

                            <div class="form-group">
                                <label for="display_order">Kolejność wyświetlania</label>
                                <input 
                                    type="number" 
                                    id="display_order" 
                                    name="display_order" 
                                    class="form-control" 
                                    placeholder="0"
                                    value="0"
                                    min="0"
                                >
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Dodaj kategorię
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Categories List -->
                <div class="dashboard-section" style="flex: 1;">
                    <div class="section-header">
                        <h2>Lista kategorii</h2>
                    </div>
                    
                    <?php if (!empty($blogCategories)): ?>
                        <div class="table-container">
                            <div class="table-header">
                                <h2>
                                    Kategorie bloga 
                                    <span style="color: #999; font-size: 0.9rem; font-weight: normal;">
                                        (<?= count($blogCategories) ?>)
                                    </span>
                                </h2>
                            </div>
                            <div class="table-wrapper">
                                <table class="table" id="categories-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Nazwa</th>
                                            <th>Slug</th>
                                            <th>Ikona</th>
                                            <th>Kolejność</th>
                                            <th>Status</th>
                                            <th style="width: 120px;">Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($blogCategories as $category): ?>
                                            <tr data-id="<?= $category['id'] ?>">
                                                <td>
                                                    <i class="fas fa-grip-vertical" style="color: #999; cursor: move;"></i>
                                                </td>
                                                <td>
                                                    <strong><?= sanitize($category['name']) ?></strong>
                                                </td>
                                                <td>
                                                    <code style="font-size: 0.85rem;"><?= sanitize($category['slug']) ?></code>
                                                </td>
                                                <td>
                                                    <span class="category-icon-box" style="display: inline-flex; width: 34px; height: 34px;"><?= getCategoryIconMarkup($category) ?></span>
                                                </td>
                                                <td>
                                                    <span class="status-badge" style="background: #e9ecef; color: #666;">
                                                        <?= $category['display_order'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $category['is_active'] ? 'active' : 'inactive' ?>">
                                                        <?= $category['is_active'] ? 'Aktywna' : 'Nieaktywna' ?>
                                                    </span>
                                                </td>
                                                <td class="actions-col">
                                                    <button type="submit" form="blog-category-move-up-<?= (int) $category['id'] ?>" class="action-btn" title="Przesuń wyżej" aria-label="Przesuń kategorię <?= sanitize($category['name']) ?> wyżej">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                    <button type="submit" form="blog-category-move-down-<?= (int) $category['id'] ?>" class="action-btn" title="Przesuń niżej" aria-label="Przesuń kategorię <?= sanitize($category['name']) ?> niżej">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </button>
                                                    <button 
                                                        class="action-btn edit-btn" 
                                                        onclick="editCategory(<?= $category['id'] ?>, '<?= addslashes($category['name']) ?>', '<?= addslashes($category['slug']) ?>', '<?= addslashes($category['icon'] ?? '') ?>', <?= $category['display_order'] ?>, <?= $category['is_active'] ?>)"
                                                        title="Edytuj"
                                                    >
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="submit" form="blog-category-delete-<?= (int) $category['id'] ?>" class="action-btn delete-btn" title="Usuń" onclick="return confirm('Czy na pewno chcesz usunąć tę kategorię?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-body text-center py-4">
                            <i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                            <h3>Brak kategorii</h3>
                            <p>Nie dodano jeszcze żadnych kategorii.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php foreach ($blogCategories as $category): ?>
        <form id="blog-category-delete-<?= (int) $category['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/categories.php" hidden>
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
        </form>
        <form id="blog-category-move-up-<?= (int) $category['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/categories.php" hidden>
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <input type="hidden" name="action" value="move">
            <input type="hidden" name="direction" value="up">
            <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
        </form>
        <form id="blog-category-move-down-<?= (int) $category['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/categories.php" hidden>
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <input type="hidden" name="action" value="move">
            <input type="hidden" name="direction" value="down">
            <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
        </form>
    <?php endforeach; ?>
    
    <!-- Edit Category Modal -->
    <div class="modal-overlay" id="edit-category-modal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Edytuj kategorię</h2>
                <button class="modal-close" onclick="closeEditModal()">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="edit-category-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <input type="hidden" name="update_category" value="1">
                    <input type="hidden" name="id" id="edit-category-id">
                    
                    <div class="form-group">
                        <label for="edit-name">Nazwa *</label>
                        <input 
                            type="text" 
                            id="edit-name" 
                            name="name" 
                            class="form-control" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-slug">Slug</label>
                        <input 
                            type="text" 
                            id="edit-slug" 
                            name="slug" 
                            class="form-control"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-icon">Ikona kategorii</label>
                        <select id="edit-icon" name="icon" class="form-control select-control">
                            <?php foreach ($iconOptions as $iconKey => $iconLabel): ?>
                                <option value="<?= sanitize($iconKey) ?>"><?= sanitize($iconLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group icon-upload-group">
                        <label for="edit-icon-file">Zastąp własną ikoną SVG</label>
                        <input type="file" id="edit-icon-file" name="icon_file" class="form-control icon-file-input" accept="image/svg+xml,.svg" data-svg-preview="edit-icon-preview">
                        <span class="form-hint">Opcjonalnie; plik SVG zastąpi ikonę po zapisaniu.</span>
                        <div id="edit-icon-preview" class="icon-upload-preview" aria-live="polite"></div>
                    </div>

                    <div class="form-group">
                        <label for="edit-display-order">Kolejność wyświetlania</label>
                        <input 
                            type="number" 
                            id="edit-display-order" 
                            name="display_order" 
                            class="form-control" 
                            min="0"
                        >
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input 
                                type="checkbox" 
                                id="edit-is-active" 
                                name="is_active" 
                                value="1"
                            >
                            <label for="edit-is-active" class="form-check-label">Aktywna</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" form="edit-category-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Zapisz zmiany
                </button>
                <button class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Anuluj
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Edytuj kategorię
        function editCategory(id, name, slug, icon, displayOrder, isActive) {
            document.getElementById('edit-category-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-slug').value = slug;
            document.getElementById('edit-icon').value = icon;
            document.getElementById('edit-display-order').value = displayOrder;
            document.getElementById('edit-is-active').checked = isActive === 1;
            
            document.getElementById('edit-category-modal').classList.add('active');
        }
        
        // Zamknij modal
        function closeEditModal() {
            document.getElementById('edit-category-modal').classList.remove('active');
        }
        
        // Zamknij modal po kliknięciu poza nim
        document.getElementById('edit-category-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Sortowanie kategorii (Drag & Drop)
        let draggedItem = null;
        
        document.querySelectorAll('#categories-table tbody tr').forEach(row => {
            row.setAttribute('draggable', 'true');
            
            row.addEventListener('dragstart', function(e) {
                draggedItem = this;
                this.style.opacity = '0.5';
            });
            
            row.addEventListener('dragend', function() {
                this.style.opacity = '1';
            });
            
            row.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            row.addEventListener('drop', function(e) {
                e.preventDefault();
                if (draggedItem !== this) {
                    const table = document.getElementById('categories-table');
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    
                    const draggedIndex = rows.indexOf(draggedItem);
                    const targetIndex = rows.indexOf(this);
                    
                    if (draggedIndex < targetIndex) {
                        this.parentNode.insertBefore(draggedItem, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedItem, this);
                    }
                    
                    // Zapisz nową kolejność
                    saveCategoriesOrder();
                }
            });
        });
        
        // Zapisz kolejność kategorii
        function saveCategoriesOrder() {
            const rows = document.querySelectorAll('#categories-table tbody tr');
            const items = [];
            
            rows.forEach(row => {
                items.push(row.dataset.id);
            });
            
            // Wyślij przez AJAX
            fetch('categories.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'csrf_token=<?= rawurlencode($csrfToken) ?>&action=reorder&' + items.map((id, index) => `items[${index}]=${id}`).join('&')
            });
        }
    </script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
