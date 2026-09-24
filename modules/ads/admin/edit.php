<?php
// Panel administracyjny - Edytuj ogłoszenie
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$csrfToken = getAdminCsrfToken();

// Pobierz ID ogłoszenia
$id = $_GET['id'] ?? null;

if (!$id) {
    redirect(SITE_URL . '/admin/ads/index.php');
    exit();
}

// Pobierz również ogłoszenia oczekujące na moderację lub odrzucone.
ensureAdModerationSchema();
ensureAdProfileSchema();
$ad = getAdById($id, true);

if (!$ad) {
    redirect(SITE_URL . '/admin/ads/index.php');
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

    deleteAdGalleryImage($imageId);
    redirect(SITE_URL . '/admin/ads/edit.php?id=' . (int) $id . '&success=image_deleted');
    exit;
}

$pageTitle = 'Edytuj ogłoszenie - ' . $ad['title'] . ' - Panel administracyjny';

// Pobierz kategorie ogłoszeń
$adCategories = getAdCategories();
$adCategoryTree = getAdCategoryTree();
$validCategoryIds = array_map('intval', array_column(getAdSelectableCategories(), 'id'));

// Pobierz galerię
$gallery = getAdGallery($id);

// Inicjalizacja zmiennych
$title = $ad['title'];
$description = $ad['description'];
$categoryId = $ad['category_id'];
$location = $ad['location'];
$phone = $ad['phone'] ?? '';
$email = $ad['email'] ?? '';
$address = $ad['address'] ?? '';
$rating = $ad['rating'];
$link = $ad['link'];
$profileSubtitle = $ad['profile_subtitle'] ?? '';
$profileTags = implode(', ', getAdProfileTags($ad));
$contactUrl = $ad['contact_url'] ?? '';
$isCityPride = (int) ($ad['is_city_pride'] ?? 0);
$detailLayout = $ad['detail_layout'] ?? 'legacy';
$isFeatured = $ad['is_featured'];
$isActive = $ad['is_active'];
$submissionStatus = $ad['submission_status'] ?? 'approved';
$image = $ad['image'];

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
    $description = $_POST['description'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $rating = $_POST['rating'] ?? 4.5;
    $link = $_POST['link'] ?? '';
    $profileSubtitle = trim($_POST['profile_subtitle'] ?? '');
    $profileTags = trim($_POST['profile_tags'] ?? '');
    $contactUrl = trim($_POST['contact_url'] ?? '');
    $isCityPride = isset($_POST['is_city_pride']) ? 1 : 0;
    $galleryUploadCount = isset($_FILES['gallery']['error']) && is_array($_FILES['gallery']['error']) ? count(array_filter($_FILES['gallery']['error'], static fn($error) => $error !== UPLOAD_ERR_NO_FILE)) : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Walidacja
    if (empty($title)) {
        $error = 'Tytuł ogłoszenia jest wymagany.';
    } elseif (empty($categoryId) || !in_array((int) $categoryId, $validCategoryIds, true)) {
        $error = 'Wybierz poprawną kategorię.';
    } elseif ($phone !== '' && !preg_match('/^[0-9+().\\s-]{6,40}$/', $phone)) {
        $error = 'Podaj poprawny numer telefonu.';
    } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Podaj poprawny adres e-mail.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($address) : strlen($address)) > 255) {
        $error = 'Adres może mieć maksymalnie 255 znaków.';
    } elseif ($detailLayout === 'profile' && $galleryUploadCount + getAdImageCount($id) > 5) {
        $error = 'Łącznie można zachować maksymalnie 5 zdjęć.';
    } elseif ($detailLayout === 'profile' && $contactUrl !== '' && normalizeAdContactUrl($contactUrl) === '') {
        $error = 'Podaj poprawny link kontaktowy.';
    } elseif ($detailLayout === 'profile' && normalizeAdContactUrl($contactUrl) === '' && $phone === '' && $email === '') {
        $error = 'Podaj link kontaktowy, telefon lub e-mail.';
    } else {
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
            // Zaktualizuj ogłoszenie
            $result = updateAd($id, [
                'title' => $title,
                'description' => $description,
                'image' => $newImage,
                'category_id' => $categoryId,
                'location' => $location,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'rating' => $rating,
                'link' => $link,
                'detail_layout' => $detailLayout,
                'profile_subtitle' => $profileSubtitle,
                'profile_tags' => $profileTags,
                'contact_url' => $contactUrl,
                'is_city_pride' => $isCityPride,
                'is_featured' => $isFeatured,
                'is_active' => $isActive
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
                                addAdGalleryImage($id, $uploadResult['filename'], $galleryDescriptions[$key] ?? '');
                            }
                        }
                    }
                }
                
                // Aktualizacja istniejących opisów w galerii
                if (isset($_POST['existing_gallery_descriptions'])) {
                    global $pdo;
                    foreach ($_POST['existing_gallery_descriptions'] as $imgId => $desc) {
                        $stmt = $pdo->prepare("UPDATE ad_gallery SET description = ? WHERE id = ? AND ad_id = ?");
                        $stmt->execute([$desc, $imgId, $id]);
                    }
                }

                $success = 'Ogłoszenie zostało zaktualizowane pomyślnie!';
                $ad = getAdById($id, true);
                $image = $ad['image'];
                $submissionStatus = $ad['submission_status'] ?? 'approved';
                $gallery = getAdGallery($id);
            } else {
                $error = 'Wystąpił błąd podczas aktualizowania ogłoszenia.';
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
                <h1>Edytuj ogłoszenie</h1>
                <p>Modyfikuj dane ogłoszenia: <strong><?= sanitize($ad['title']) ?></strong></p>
            </div>
            <div class="admin-page-actions">
                <?php if ($submissionStatus === 'pending'): ?>
                    <form method="post" action="<?= SITE_URL ?>/admin/ads/toggle.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Zaakceptuj i opublikuj</button>
                    </form>
                    <form method="post" action="<?= SITE_URL ?>/admin/ads/toggle.php" class="inline-form" onsubmit="return confirm('Odrzucić to zgłoszenie?')">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Odrzuć</button>
                    </form>
                <?php elseif ($submissionStatus === 'approved' && $isActive): ?>
                    <a href="<?= SITE_URL ?>/ad.php?id=<?= (int) $ad['id'] ?>" class="btn btn-secondary" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt"></i> Podgląd publiczny
                    </a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/admin/ads/index.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Lista ogłoszeń
                </a>
            </div>
        </div>
        
        <div class="moderation-summary moderation-<?= sanitize($submissionStatus) ?>">
            <i class="fas <?= $submissionStatus === 'pending' ? 'fa-hourglass-half' : ($submissionStatus === 'rejected' ? 'fa-times-circle' : 'fa-check-circle') ?>"></i>
            <div><strong><?= sanitize(getSubmissionStatusLabel($submissionStatus)) ?></strong><span><?= $submissionStatus === 'pending' ? 'Po zapisaniu zmian zaakceptuj lub odrzuć zgłoszenie.' : ($submissionStatus === 'rejected' ? 'Ogłoszenie nie jest widoczne publicznie.' : 'Status moderacji został zatwierdzony.') ?></span></div>
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
                    <h3>Dane ogłoszenia</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="form-label-row"><label for="title">Tytuł *</label><span id="title-counter" class="char-counter" aria-live="polite"></span></div>
                        <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($title) ?>" maxlength="255" data-counter="title-counter" required>
                                                <span class="form-hint">Tytuł jest widoczny w serwisie oraz na liście ogłoszeń.</span>
                    </div>
                    <div class="form-group">
                        <label for="profile_subtitle">Podtytuł</label>
                        <input type="text" id="profile_subtitle" name="profile_subtitle" class="form-control" value="<?= sanitize($profileSubtitle) ?>" maxlength="255" placeholder="Np. Wszystko do domu, warsztatu i ogrodu">
                        <span class="form-hint">Opcjonalny, krótki podpis wyświetlany pod tytułem ogłoszenia.</span>
                    </div>
                    <div class="form-group">
                        <div class="form-label-row"><label for="description">Opis</label><span id="description-counter" class="char-counter" aria-live="polite"></span></div>
                        <textarea id="description" name="description" class="form-control textarea-control" data-counter="description-counter"><?= sanitize($description) ?></textarea>
                        <span class="form-hint">Uzupełnij najważniejsze informacje, które zobaczy odbiorca ogłoszenia.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Kategoria *</label>
                        <select id="category_id" name="category_id" class="form-control select-control" required>
                            <option value="">— Wybierz kategorię —</option>
                            <?php foreach ($adCategoryTree as $category): ?>
                                <?php if (!empty($category['children'])): ?>
                                    <optgroup label="<?= sanitize($category['name']) ?>">
                                        <?php foreach ($category['children'] as $child): ?>
                                            <option value="<?= (int) $child['id'] ?>" <?= $categoryId == $child['id'] ? 'selected' : '' ?>><?= sanitize($child['name']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php else: ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>><?= sanitize($category['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_tags">Tagi profilu</label>
                        <input type="text" id="profile_tags" name="profile_tags" class="form-control" value="<?= sanitize($profileTags) ?>" maxlength="350" placeholder="Np. Ogrodnictwo, Narzędzia, Farby i kleje">
                    </div>

                    <div class="form-group">
                        <label for="location">Lokalizacja</label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= sanitize($location) ?>" placeholder="Np. Krosno Odrzańskie, ul. Przykładowa 1">
                        <span class="form-hint">Podaj miasto, a opcjonalnie także ulicę lub obszar obsługi.</span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= sanitize($phone) ?>" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="Np. +48 600 000 000">
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($email) ?>" maxlength="255" autocomplete="email" placeholder="kontakt@przyklad.pl">
                    </div>

                    <div class="form-group">
                        <label for="address">Ulica i numer / wieś i numer</label>
                        <input type="text" id="address" name="address" class="form-control" value="<?= sanitize($address) ?>" maxlength="255" placeholder="Np. ul. Przykładowa 12 lub Osiecznica 25">
                    </div>

                    <div class="form-group">
                        <label for="rating">Ocena (⭐)</label>
                        <select id="rating" name="rating" class="form-control select-control">
                            <?php for($i=1; $i<=5; $i+=0.5): ?>
                                <option value="<?= $i ?>" <?= $rating == $i ? 'selected' : '' ?>>⭐ <?= number_format($i, 1) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="link">Link (opcjonalny)</label>
                        <input type="url" id="link" name="link" class="form-control" value="<?= sanitize($link) ?>" placeholder="https://...">
                        <span class="form-hint">Wklej pełny adres strony, formularza kontaktowego lub rezerwacji.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_url">Link kontaktowy</label>
                        <input type="url" id="contact_url" name="contact_url" class="form-control" value="<?= sanitize($contactUrl) ?>" placeholder="https://..., tel:+48600000000 lub mailto:kontakt@przyklad.pl">
                    </div>

                    <div class="form-group">
                        <label for="image">Główne zdjęcie</label>
                        <div class="file-control" role="button" aria-label="Zmień główne zdjęcie">
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
                                    <img src="<?= getImageUrl($image) ?>" alt="Aktualne główne zdjęcie">
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
                                        <button type="submit" form="ad-gallery-delete-<?= (int) $img['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Usunąć to zdjęcie?')">
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

                    <div class="form-section-heading"><h4>Publikacja</h4><p>Zarządzaj widocznością oraz wyróżnieniem ogłoszenia.</p></div>
                    <div class="form-group">
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="checkbox" id="is_city_pride" name="is_city_pride" value="1" <?= $isCityPride ? 'checked' : '' ?>>
                                <label for="is_city_pride" class="form-check-label">Duma Miasta</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?>>
                                <label for="is_featured" class="form-check-label">Wyróżnione</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                <label for="is_active" class="form-check-label">Aktywne</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer admin-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Zapisz zmiany</button>
                    <a href="<?= SITE_URL ?>/admin/ads/index.php" class="btn btn-secondary">Anuluj</a>
                    <span class="form-save-note"><i class="fas fa-keyboard"></i> Skrót zapisu: Ctrl / Cmd + S</span>
                </div>
            </div>
        </form>
        <?php foreach ($gallery as $img): ?>
            <form id="ad-gallery-delete-<?= (int) $img['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/ads/edit.php?id=<?= (int) $id ?>" hidden>
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
    if (container.children.length >= 4) {
        return;
    }
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
