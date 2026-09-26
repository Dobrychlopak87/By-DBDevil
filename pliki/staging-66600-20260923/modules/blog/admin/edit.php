<?php
// Panel administracyjny - Edytuj artykuł blogowy
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$csrfToken = getAdminCsrfToken();

// Pobierz ID artykułu
$id = $_GET['id'] ?? null;

if (!$id) {
    redirect(SITE_URL . '/admin/blog/index.php');
    exit();
}

// Pobierz artykuł, także gdy oczekuje na moderację.
$post = getBlogPostById($id, true);

if (!$post) {
    redirect(SITE_URL . '/admin/blog/index.php');
    exit();
}

// Obsługa usuwania zdjęcia z galerii
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_gallery_image') {
    try {
        verifyAdminCsrfToken($_POST['csrf_token'] ?? null);
    } catch (RuntimeException $exception) {
        http_response_code(403);
        exit;
    }

    $imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
    if ($imageId === false || $imageId === null || $imageId < 1) {
        http_response_code(400);
        exit;
    }

    deleteBlogPostGalleryImage($imageId);
    redirect(SITE_URL . '/admin/blog/edit.php?id=' . (int) $id . '&success=image_deleted');
    exit;
}

$pageTitle = 'Edytuj artykuł - ' . $post['title'] . ' - Panel administracyjny';

// Pobierz kategorie bloga
$blogCategories = getBlogCategories();

// Pobierz galerię
$gallery = getBlogPostGallery($id);

// Inicjalizacja zmiennych
$title = $post['title'];
$content = $post['content'];
$excerpt = $post['excerpt'];
$categoryId = $post['category_id'];
$image = $post['image'];
$isFeatured = $post['is_featured'];
$isActive = $post['is_active'];
$metaTitle = $post['meta_title'];
$metaDescription = $post['meta_description'];
$originalAuthorSignature = (string) ($post['author_signature'] ?? '');
$isCommunityAuthored = (($post['author_source'] ?? 'community') !== 'official');
$authorSignature = $originalAuthorSignature !== '' ? $originalAuthorSignature : ($isCommunityAuthored ? '' : 'Administracja');
$authorSource = $isCommunityAuthored ? 'community' : 'official';
$submissionStatus = $post['submission_status'] ?? 'approved';

$error = '';
$success = '';

if (isset($_GET['success']) && $_GET['success'] == 'image_deleted') {
    $success = 'Zdjęcie z galerii zostało usunięte.';
}

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
    $authorSignature = trim((string) ($_POST['author_signature'] ?? 'Administracja'));
    
    // Walidacja
    if (empty($title)) {
        $error = 'Tytuł artykułu jest wymagany.';
    } elseif (empty($categoryId)) {
        $error = 'Kategoria jest wymagana.';
    } elseif (!($isCommunityAuthored && $authorSignature === $originalAuthorSignature)
        && !in_array(normalizeOfficialLabel($authorSignature), ['66600.pl', 'administracja', 'administrator'], true)) {
        $error = 'Wybierz prawidłowy podpis oficjalny.';
    } else {
        // Zachowaj autorstwo mieszkańca, gdy podpis nie został zmieniony na oficjalny.
        $authorSource = ($isCommunityAuthored && $authorSignature === $originalAuthorSignature) ? 'community' : 'official';
        // Obsługa przesyłania głównego zdjęcia
        $newImage = $image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if (!empty($image)) {
                deleteFile($image);
            }
            $uploadResult = uploadFile($_FILES['image']);
            if ($uploadResult['success']) {
                $newImage = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
            if (!empty($image)) {
                deleteFile($image);
            }
            $newImage = '';
        }
        
        if (empty($error)) {
            // Zaktualizuj artykuł
            $result = updateBlogPost($id, [
                'title' => $title,
                'slug' => $post['slug'],
                'content' => $content,
                'excerpt' => $excerpt,
                'image' => $newImage,
                'category_id' => $categoryId,
                'is_featured' => $isFeatured,
                'is_active' => $isActive,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'author_signature' => $authorSignature,
                'author_source' => $authorSource
            ]);
            
            if ($result) {
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
                                addBlogPostGalleryImage($id, $uploadResult['filename'], $galleryDescriptions[$key] ?? '');
                            }
                        }
                    }
                }
                
                // Aktualizacja istniejących opisów
                if (isset($_POST['existing_gallery_descriptions'])) {
                    global $pdo;
                    foreach ($_POST['existing_gallery_descriptions'] as $imgId => $desc) {
                        $stmt = $pdo->prepare("UPDATE blog_post_gallery SET description = ? WHERE id = ? AND post_id = ?");
                        $stmt->execute([$desc, $imgId, $id]);
                    }
                }

                $success = 'Artykuł został zaktualizowany pomyślnie!';
                $post = getBlogPostById($id, true);
                $originalAuthorSignature = (string) ($post['author_signature'] ?? '');
                $isCommunityAuthored = (($post['author_source'] ?? 'community') !== 'official');
                $submissionStatus = $post['submission_status'] ?? 'approved';
                $image = $post['image'];
                $gallery = getBlogPostGallery($id);
            } else {
                $error = 'Wystąpił błąd podczas aktualizowania artykułu.';
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
                <h1>Edytuj artykuł blogowy</h1>
                <p>Modyfikuj dane artykułu: <strong><?= sanitize($post['title']) ?></strong></p>
            </div>
            <div class="admin-page-actions">
                <?php if ($submissionStatus === 'pending'): ?>
                    <form method="post" action="<?= SITE_URL ?>/admin/blog/toggle.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Zaakceptuj i opublikuj</button>
                    </form>
                    <form method="post" action="<?= SITE_URL ?>/admin/blog/toggle.php" class="inline-form" onsubmit="return confirm('Odrzucić to zgłoszenie?')">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Odrzuć</button>
                    </form>
                <?php elseif ($submissionStatus === 'approved' && $isActive): ?>
                    <a href="<?= SITE_URL ?>/post.php?slug=<?= rawurlencode($post['slug']) ?>" class="btn btn-secondary" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt"></i> Podgląd publiczny
                    </a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Lista artykułów
                </a>
            </div>
        </div>
        
        <div class="moderation-summary moderation-<?= sanitize($submissionStatus) ?>">
            <i class="fas <?= $submissionStatus === 'pending' ? 'fa-hourglass-half' : ($submissionStatus === 'rejected' ? 'fa-times-circle' : 'fa-check-circle') ?>"></i>
            <div><strong><?= sanitize(getSubmissionStatusLabel($submissionStatus)) ?></strong><span><?= $submissionStatus === 'pending' ? 'Po zapisaniu zmian zaakceptuj lub odrzuć zgłoszenie.' : ($submissionStatus === 'rejected' ? 'Artykuł nie jest widoczny publicznie.' : 'Status moderacji został zatwierdzony.') ?></span></div>
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
                        <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($title) ?>" maxlength="255" data-counter="title-counter" required>
                        <span class="form-hint">Tytuł jest widoczny w serwisie oraz na liście artykułów.</span>
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
                        <textarea id="excerpt" name="excerpt" class="form-control textarea-control" rows="3" maxlength="500" data-counter="excerpt-counter"><?= sanitize($excerpt) ?></textarea>
                        <span class="form-hint">Zwięzły wstęp pomaga czytelnikom zrozumieć temat przed otwarciem artykułu.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="author_signature">Podpis oficjalny</label>
                        <select id="author_signature" name="author_signature" class="form-control select-control">
                            <?php if ($isCommunityAuthored && !isReservedOfficialLabel($originalAuthorSignature)): ?>
                                <option value="<?= sanitize($originalAuthorSignature) ?>" <?= $authorSignature === $originalAuthorSignature ? 'selected' : '' ?>><?= $originalAuthorSignature !== '' ? 'Zachowaj: ' . sanitize($originalAuthorSignature) . ' (mieszkaniec)' : 'Brak podpisu — zachowaj jako wpis mieszkańca' ?></option>
                            <?php endif; ?>
                            <?php foreach (['66600.pl', 'Administracja', 'Administrator'] as $officialLabel): ?>
                                <option value="<?= sanitize($officialLabel) ?>" <?= normalizeOfficialLabel($authorSignature) === normalizeOfficialLabel($officialLabel) ? 'selected' : '' ?>><?= sanitize($officialLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isCommunityAuthored): ?>
                            <span class="form-hint">Wybierz „Zachowaj…”, aby nie nadpisać podpisu mieszkańca. Podpisy oficjalne są dostępne wyłącznie w panelu administratora.</span>
                        <?php else: ?>
                            <span class="form-hint">Podpis jest dostępny wyłącznie w panelu administratora.</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="content">Treść artykułu *</label>
                        <textarea id="content" name="content" class="form-control textarea-control tinymce-editor" required><?= sanitize($content) ?></textarea>
                        <span class="form-hint">Nowe linie i akapity są zachowywane na stronie. Możesz też wkleić gotowy kod HTML.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Zdjęcie główne</label>
                        <div class="file-control" role="button" aria-label="Zmień zdjęcie główne">
                            <span class="file-control-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </span>
                            <span class="file-control-label">Kliknij lub upuść nowe zdjęcie, aby je podmienić</span>
                            <span class="file-control-hint">Zdjęcie zostanie podmienione po zapisaniu · JPG, PNG, GIF, WebP lub AVIF · maks. 5 MB</span>
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                        <div id="image-preview" class="file-preview" aria-live="polite">
                            <?php if (!empty($image)): ?>
                                <div class="file-preview-item">
                                    <img src="<?= getImageUrl($image) ?>" alt="Aktualne zdjęcie główne">
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($image)): ?>
                            <div class="form-check mt-2">
                                <input type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label for="remove_image" class="form-check-label">Usuń główne zdjęcie</label>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <h3>Galeria zdjęć</h3>
                    <div id="gallery-container">
                        <?php foreach ($gallery as $img): ?>
                            <div class="gallery-item-edit mb-3 p-3 border rounded">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <div class="gallery-file-thumb gallery-file-thumb-filled">
                                            <img src="<?= getImageUrl($img['image']) ?>" alt="Zdjęcie galerii">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" name="existing_gallery_descriptions[<?= $img['id'] ?>]" class="form-control" placeholder="Krótki opis zdjęcia" value="<?= sanitize($img['description']) ?>">
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <button type="submit" form="blog-gallery-delete-<?= (int) $img['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Usunąć to zdjęcie?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4">
                        <h4>Dodaj nowe zdjęcia do galerii</h4>
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
                    </div>

                    <details class="admin-advanced-panel">
                        <summary>Ustawienia SEO (opcjonalne)</summary>
                        <div class="advanced-panel-content">
                            <p class="form-hint" style="margin-top: 0;">Pola SEO są opcjonalne. Gdy pozostaną puste, serwis wykorzysta treść artykułu.</p>
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

                    <div class="form-section-heading"><h4>Publikacja</h4><p>Zarządzaj widocznością i wyróżnieniem wpisu.</p></div>
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
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Zapisz zmiany</button>
                    <a href="<?= SITE_URL ?>/admin/blog/index.php" class="btn btn-secondary">Anuluj</a>
                    <span class="form-save-note"><i class="fas fa-keyboard"></i> Skrót zapisu: Ctrl / Cmd + S</span>
                </div>
            </div>
        </form>
        <?php foreach ($gallery as $img): ?>
            <form id="blog-gallery-delete-<?= (int) $img['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/edit.php?id=<?= (int) $id ?>" hidden>
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_gallery_image">
                <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
            </form>
        <?php endforeach; ?>
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
