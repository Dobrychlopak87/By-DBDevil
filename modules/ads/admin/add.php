<?php
// Panel administracyjny - Dodaj ogłoszenie
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'Dodaj ogłoszenie - Panel administracyjny';
$csrfToken = getAdminCsrfToken();

// Pobierz kategorie ogłoszeń
$adCategories = getAdCategories();
$adCategoryTree = getAdCategoryTree();
$validCategoryIds = array_map('intval', array_column(getAdSelectableCategories(), 'id'));

// Inicjalizacja zmiennych
$title = '';
$description = '';
$categoryId = '';
$location = '';
$phone = '';
$email = '';
$address = '';
$rating = 4.5;
$link = '';
$profileSubtitle = '';
$profileTags = '';
$contactUrl = '';
$isCityPride = 0;
$isFeatured = 0;
$isActive = 1;

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
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Zdjęcie główne profilu jest wymagane.';
    } elseif ($galleryUploadCount > 4) {
        $error = 'Możesz dodać maksymalnie 4 zdjęcia dodatkowe.';
    } elseif ($contactUrl !== '' && normalizeAdContactUrl($contactUrl) === '') {
        $error = 'Podaj poprawny link kontaktowy.';
    } elseif (normalizeAdContactUrl($contactUrl) === '' && $phone === '' && $email === '') {
        $error = 'Podaj link kontaktowy, telefon lub e-mail.';
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
            // Dodaj ogłoszenie
            global $pdo;
            ensureAdContactSchema();
            ensureAdProfileSchema();
            $stmt = $pdo->prepare("INSERT INTO ads 
                    (title, description, image, category_id, location, phone, email, address, rating, link, detail_layout, profile_subtitle, profile_tags, contact_url, is_city_pride, is_featured, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'profile', ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $title,
                $description,
                $image,
                $categoryId,
                $location !== '' ? $location : null,
                $phone !== '' ? $phone : null,
                $email !== '' ? $email : null,
                $address !== '' ? $address : null,
                $rating,
                $link,
                $profileSubtitle !== '' ? $profileSubtitle : null,
                json_encode(normalizeAdProfileTags($profileTags), JSON_UNESCAPED_UNICODE),
                normalizeAdContactUrl($contactUrl),
                $isCityPride,
                $isFeatured,
                $isActive
            ]);
            
            if ($result) {
                $adId = $pdo->lastInsertId();
                
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
                                addAdGalleryImage($adId, $uploadResult['filename'], $galleryDescriptions[$key] ?? '');
                            }
                        }
                    }
                }
                
                $success = 'Ogłoszenie zostało dodane pomyślnie!';
                // Wyczyść formularz
                $title = ''; $description = ''; $categoryId = ''; $location = ''; $phone = ''; $email = ''; $address = ''; $rating = 4.5; $link = ''; $profileSubtitle = ''; $profileTags = ''; $contactUrl = ''; $isCityPride = 0; $isFeatured = 0; $isActive = 1;
            } else {
                $error = 'Wystąpił błąd podczas dodawania ogłoszenia.';
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
                <h1>Dodaj ogłoszenie</h1>
                <p>Utwórz nowe ogłoszenie na stronie</p>
            </div>
            <div class="admin-page-actions">
                <a href="<?= SITE_URL ?>/admin/ads/index.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Lista ogłoszeń
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
                    <h3>Dane ogłoszenia</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="form-label-row"><label for="title">Tytuł *</label><span id="title-counter" class="char-counter" aria-live="polite"></span></div>
                        <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($title) ?>" placeholder="Np. Usługa lub oferta dla mieszkańców" maxlength="255" data-counter="title-counter" required>
                                                <span class="form-hint">Krótki, rzeczowy tytuł ułatwia odnalezienie ogłoszenia na liście.</span>
                    </div>
                    <div class="form-group">
                        <label for="profile_subtitle">Podtytuł</label>
                        <input type="text" id="profile_subtitle" name="profile_subtitle" class="form-control" value="<?= sanitize($profileSubtitle) ?>" maxlength="255" placeholder="Np. Wszystko do domu, warsztatu i ogrodu">
                        <span class="form-hint">Opcjonalny, krótki podpis wyświetlany pod tytułem ogłoszenia.</span>
                    </div>
                    <div class="form-group">
                        <div class="form-label-row"><label for="description">Opis</label><span id="description-counter" class="char-counter" aria-live="polite"></span></div>
                        <textarea id="description" name="description" class="form-control textarea-control" data-counter="description-counter" placeholder="Opisz ofertę, jej najważniejsze korzyści oraz sposób kontaktu."><?= sanitize($description) ?></textarea>
                        <span class="form-hint">Dodaj najważniejsze informacje, które pomogą odbiorcy podjąć decyzję.</span>
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
                        <label for="profile_tags">Tagi</label>
                        <input type="text" id="profile_tags" name="profile_tags" class="form-control" value="<?= sanitize($profileTags) ?>" maxlength="350" placeholder="Np. Ogrodnictwo, Narzędzia, Farby i kleje">
                        <span class="form-hint">Maksymalnie 8 tagów rozdzielonych przecinkami.</span>
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
                        <span class="form-hint">Wymagany jest link kontaktowy, telefon albo e-mail.</span>
                    </div>

                    <div class="form-group">
                        <label for="image">Główne zdjęcie *</label>
                        <div class="file-control" role="button" aria-label="Wybierz główne zdjęcie">
                            <span class="file-control-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </span>
                            <span class="file-control-label">Kliknij lub upuść zdjęcie tutaj</span>
                            <span class="file-control-hint">JPG, PNG, GIF, WebP lub AVIF · maks. 5 MB · łącznie do 5 zdjęć</span>
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                        <div id="image-preview" class="file-preview" aria-live="polite"></div>
                    </div>

                    <hr>
                    <h3>Galeria zdjęć</h3>
                    <p class="form-hint">Możesz dodać maksymalnie 4 zdjęcia dodatkowe.</p>
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
                    <button type="button" class="btn btn-info btn-sm" onclick="addNewImageRow()" id="add-gallery-row">
                        <i class="fas fa-plus"></i> Dodaj kolejne pole
                    </button>

                    <div class="form-section-heading"><h4>Publikacja</h4><p>Wyróżnij ofertę lub zachowaj ją jako nieaktywną do czasu publikacji.</p></div>
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
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Zapisz ogłoszenie</button>
                    <a href="<?= SITE_URL ?>/admin/ads/index.php" class="btn btn-secondary">Anuluj</a>
                    <span class="form-save-note"><i class="fas fa-keyboard"></i> Skrót zapisu: Ctrl / Cmd + S</span>
                </div>
            </div>
        </form>
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
