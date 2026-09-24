<?php
// Panel administracyjny - Dodaj artykuł blogowy
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

ensureBlogModerationSchema();

$pageTitle = 'Dodaj artykuł - Panel administracyjny';
$csrfToken = getAdminCsrfToken();

// Pobierz kategorie bloga
$blogCategories = getBlogCategories();

// Inicjalizacja zmiennych
$title = '';
$content = '';
$excerpt = '';
$categoryId = '';
$isFeatured = 0;
$isActive = 1;
$metaTitle = '';
$metaDescription = '';
$authorSignature = 'Administracja';

$error = '';
$success = '';

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $metaTitle = $_POST['meta_title'] ?? '';
    $metaDescription = $_POST['meta_description'] ?? '';
    $authorSignature = trim($_POST['author_signature'] ?? 'Administracja');
    $galleryUploadCount = 0;
    if (isset($_FILES['gallery']['error']) && is_array($_FILES['gallery']['error'])) {
        foreach ($_FILES['gallery']['error'] as $uploadError) {
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                $galleryUploadCount++;
            }
        }
    }

    // Walidacja
    if (empty($title)) {
        $error = 'Tytuł artykułu jest wymagany.';
    } elseif (empty($categoryId)) {
        $error = 'Kategoria jest wymagana.';
    } elseif ($galleryUploadCount > 6) {
        $error = 'Możesz dodać maksymalnie 6 zdjęć do galerii.';
    } elseif (!in_array(normalizeOfficialLabel($authorSignature), ['66600.pl', 'administracja', 'administrator'], true)) {
        $error = 'Wybierz prawidłowy podpis oficjalny.';
    } else {
        // Obsługa przesyłania głównego zdjęcia
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['image']);
            if ($uploadResult['success']) {
                $image = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (empty($error)) {
            // Dodaj artykuł
            global $pdo;
            $slug = generateUniqueBlogSlug($title);
            $stmt = $pdo->prepare("INSERT INTO blog_posts 
                    (title, content, excerpt, image, category_id, slug, author_signature, author_source, is_featured, is_active, meta_title, meta_description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'official', ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $title,
                $content,
                $excerpt,
                $image,
                $categoryId,
                $slug,
                $authorSignature,
                $isFeatured,
                $isActive,
                $metaTitle,
                $metaDescription
            ]);
            
            if ($result) {
                $postId = $pdo->lastInsertId();
                
                // Obsługa galerii
                if (isset($_FILES['gallery'])) {
                    $galleryDescriptions = $_POST['gallery_descriptions'] ?? [];
                    foreach ($_FILES['gallery']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['gallery']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['gallery']['name'][$key],
                                'type' => $_FILES['gallery']['type'][$key],
                                'tmp_name' => $_FILES['gallery']['tmp_name'][$key],
                                'error' => $_FILES['gallery']['error'][$key],
                                'size' => $_FILES['gallery']['size'][$key]
                            ];
                            $uploadResult = uploadFile($file);
                            if ($uploadResult['success']) {
                                addBlogPostGalleryImage($postId, $uploadResult['filename'], $galleryDescriptions[$key] ?? '');
                            }
                        }
                    }
                }
                
                $success = 'Artykuł został dodany pomyślnie!';
                // Wyczyść formularz
                $title = ''; $content = ''; $excerpt = ''; $categoryId = ''; $isFeatured = 0; $isActive = 1; $metaTitle = ''; $metaDescription = '';
            } else {
                $error = 'Wystąpił błąd podczas dodawania artykułu.';
            }
        }
    }
}
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

<div class="admin-container">
    <?php require_once dirname(__DIR__, 3) . '/admin/includes/admin-header.php'; ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <div class="admin-page-title">
                <h1>Dodaj artykuł blogowy</h1>
                <p>Utwórz nowy artykuł na blogu</p>
            </div>
            <div class="admin-page-actions">
                <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Lista artykułów
                </a>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span class="alert-message"><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span class="alert-message"><?= sanitize($success) ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="admin-editor-form">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <div class="card">
                <div class="card-header">
                    <h3>Dane artykułu</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="form-label-row"><label for="title">Tytuł *</label><span id="title-counter" class="char-counter" aria-live="polite"></span></div>
                        <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($title) ?>" placeholder="Np. Nowa inicjatywa dla mieszkańców" maxlength="255" data-counter="title-counter" required>
                        <span class="form-hint">Konkretny tytuł ułatwia późniejsze odnalezienie artykułu na liście.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Kategoria *</label>
                        <select id="category_id" name="category_id" class="form-control select-control" required>
                            <option value="">— Wybierz kategorię —</option>
                            <?php foreach ($blogCategories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-label-row"><label for="excerpt">Wstęp (krótki opis)</label><span id="excerpt-counter" class="char-counter" aria-live="polite"></span></div>
                        <textarea id="excerpt" name="excerpt" class="form-control textarea-control" rows="3" placeholder="Najważniejsza informacja w 1–3 zdaniach" maxlength="500" data-counter="excerpt-counter"><?= sanitize($excerpt) ?></textarea>
                        <span class="form-hint">Wstęp może być używany jako zwięzły opis artykułu na liście wpisów.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="author_signature">Podpis oficjalny</label>
                        <select id="author_signature" name="author_signature" class="form-control select-control">
                            <?php foreach (['66600.pl', 'Administracja', 'Administrator'] as $officialLabel): ?>
                                <option value="<?= sanitize($officialLabel) ?>" <?= normalizeOfficialLabel($authorSignature) === normalizeOfficialLabel($officialLabel) ? 'selected' : '' ?>><?= sanitize($officialLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Ten podpis jest dostępny wyłącznie w panelu administratora.</span>
                    </div>

                    <div class="form-group">
                        <label for="content">Treść artykułu *</label>
                        <textarea id="content" name="content" class="form-control textarea-control tinymce-editor" placeholder="Wpisz lub wklej treść artykułu…"><?= sanitize($content) ?></textarea>
                        <span class="form-hint">Nowe linie i akapity są zachowywane na stronie. Możesz też wkleić gotowy kod HTML.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Zdjęcie główne (opcjonalne)</label>
                        <div class="file-control" role="button" aria-label="Wybierz zdjęcie główne">
                            <span class="file-control-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </span>
                            <span class="file-control-label">Kliknij lub upuść zdjęcie tutaj</span>
                            <span class="file-control-hint">JPG, PNG, GIF, WebP lub AVIF · maks. 5 MB</span>
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                        <div id="image-preview" class="file-preview" aria-live="polite"></div>
                    </div>

                    <hr>
                    <h3>Galeria zdjęć</h3>
                    <div id="new-images-container">
                        <div class="new-image-row mb-3 p-3 border rounded bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <div class="gallery-file-thumb"></div>
                                </div>
                                <div class="col-md-4">
                                    <input type="file" name="gallery[]" class="form-control gallery-file-input" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="gallery_descriptions[]" class="form-control" placeholder="Krótki opis zdjęcia">
                                </div>
                                <div class="col-md-1 text-right">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.new-image-row').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-info btn-sm" onclick="addNewImageRow()">
                        <i class="fas fa-plus"></i> Dodaj kolejne pole
                    </button>

                    <details class="admin-advanced-panel">
                        <summary>Ustawienia SEO (opcjonalne)</summary>
                        <div class="advanced-panel-content">
                            <p class="form-hint" style="margin-top: 0;">Pozostaw puste pola, aby serwis utworzył opis na podstawie treści artykułu.</p>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-label-row"><label for="meta_title">Tytuł SEO</label><span id="meta-title-counter" class="char-counter" aria-live="polite"></span></div>
                                    <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?= sanitize($metaTitle) ?>" maxlength="255" data-counter="meta-title-counter">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-label-row"><label for="meta_description">Opis SEO</label><span id="meta-description-counter" class="char-counter" aria-live="polite"></span></div>
                                    <input type="text" id="meta_description" name="meta_description" class="form-control" value="<?= sanitize($metaDescription) ?>" maxlength="500" data-counter="meta-description-counter">
                                </div>
                            </div>
                        </div>
                    </details>

                    <div class="form-section-heading"><h4>Publikacja</h4><p>Wyróżnij wpis lub zachowaj go jako nieaktywny do czasu publikacji.</p></div>
                    <div class="form-group">
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?>>
                                <label for="is_featured" class="form-check-label">Wyróżniony</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                <label for="is_active" class="form-check-label">Aktywny</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer admin-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Zapisz artykuł</button>
                    <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary">Anuluj</a>
                    <span class="form-save-note"><i class="fas fa-keyboard"></i> Skrót zapisu: Ctrl / Cmd + S</span>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function addNewImageRow() {
    const container = document.getElementById('new-images-container');
    const row = document.createElement('div');
    row.className = 'new-image-row mb-3 p-3 border rounded bg-light';
    row.innerHTML = `
        <div class="row align-items-center">
            <div class="col-md-1">
                <div class="gallery-file-thumb"></div>
            </div>
            <div class="col-md-4">
                <input type="file" name="gallery[]" class="form-control gallery-file-input" accept="image/*">
            </div>
            <div class="col-md-6">
                <input type="text" name="gallery_descriptions[]" class="form-control" placeholder="Krótki opis zdjęcia">
            </div>
            <div class="col-md-1 text-right">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.new-image-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
}
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
