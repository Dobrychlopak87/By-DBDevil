<?php
// Publiczny formularz zgłaszania ogłoszeń. Każde zgłoszenie wymaga akceptacji administratora.
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/auth/lib.php';

auth_ensure_schema();
ensureAdModerationSchema();
ensureAdContactSchema();
ensureAdProfileSchema();

$isFragment = isset($_GET['fragment']) && $_GET['fragment'] === '1';
$wantsJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

$pageTitle = 'Dodaj ogłoszenie';
$pageDescription = 'Prześlij ogłoszenie do moderacji w serwisie 66600.PL.';
$canonicalUrl = SITE_URL . '/submit-ad.php';
$pageRobots = 'noindex, nofollow';

$adCategories = getAdCategories();
$adCategoryTree = getAdCategoryTree();
$validCategoryIds = array_map('intval', array_column(getAdSelectableCategories(), 'id'));

if (empty($_SESSION['public_ad_csrf'])) {
    $_SESSION['public_ad_csrf'] = bin2hex(random_bytes(32));
}

$title = '';
$description = '';
$categoryId = (int) ($_GET['category_id'] ?? 0);
$categoryId = $categoryId > 0 && in_array($categoryId, $validCategoryIds, true) ? $categoryId : '';
$location = '';
$phone = '';
$email = '';
$address = '';
$link = '';
$profileSubtitle = '';
$profileTags = '';
$contactUrl = '';
$error = '';
$success = isset($_GET['submitted']) && $_GET['submitted'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $profileSubtitle = trim($_POST['profile_subtitle'] ?? '');
    $profileTags = trim($_POST['profile_tags'] ?? '');
    $contactUrl = trim($_POST['contact_url'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim($_POST['website'] ?? '');
    $galleryUploadCount = 0;
    if (isset($_FILES['gallery']['error']) && is_array($_FILES['gallery']['error'])) {
        foreach ($_FILES['gallery']['error'] as $uploadError) {
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                $galleryUploadCount++;
            }
        }
    }

    $titleLength = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
    $locationLength = function_exists('mb_strlen') ? mb_strlen($location) : strlen($location);
    $addressLength = function_exists('mb_strlen') ? mb_strlen($address) : strlen($address);

    if (!hash_equals($_SESSION['public_ad_csrf'], $csrfToken)) {
        $error = 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.';
    } elseif ($honeypot !== '') {
        $error = 'Nie udało się przyjąć zgłoszenia.';
    } elseif (!empty($_SESSION['public_ad_last_submission_at']) && (time() - (int) $_SESSION['public_ad_last_submission_at']) < 30) {
        $error = 'Odczekaj chwilę przed wysłaniem kolejnego ogłoszenia.';
    } elseif ($galleryUploadCount > 4) {
        $error = 'Możesz dodać maksymalnie 4 zdjęcia dodatkowe.';
    } elseif ($title === '' || $titleLength > 255) {
        $error = 'Podaj tytuł ogłoszenia (maksymalnie 255 znaków).';
    } elseif ($description === '') {
        $error = 'Dodaj treść ogłoszenia.';
    } elseif (!in_array($categoryId, $validCategoryIds, true)) {
        $error = 'Wybierz poprawną kategorię.';
    } elseif ($location !== '' && $locationLength > 255) {
        $error = 'Lokalizacja może mieć maksymalnie 255 znaków.';
    } elseif ($phone !== '' && !preg_match('/^[0-9+().\\s-]{6,40}$/', $phone)) {
        $error = 'Podaj poprawny numer telefonu.';
    } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Podaj poprawny adres e-mail.';
    } elseif ($address !== '' && $addressLength > 255) {
        $error = 'Adres może mieć maksymalnie 255 znaków.';
    } elseif ($link !== '' && filter_var($link, FILTER_VALIDATE_URL) === false) {
        $error = 'Podaj poprawny pełny adres linku, zaczynający się od http:// lub https://.';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Dodaj zdjęcie główne profilu.';
    } elseif ($contactUrl !== '' && normalizeAdContactUrl($contactUrl) === '') {
        $error = 'Podaj poprawny link kontaktowy rozpoczynający się od https://, http://, tel: lub mailto:.';
    } elseif (normalizeAdContactUrl($contactUrl) === '' && $phone === '' && $email === '') {
        $error = 'Podaj link kontaktowy, telefon lub e-mail.';
    } else {
        $uploadedFiles = [];
        $mainImage = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['image']);
            if ($uploadResult['success']) {
                $mainImage = $uploadResult['filename'];
                $uploadedFiles[] = $mainImage;
            } else {
                $error = $uploadResult['message'];
            }
        }

        if ($error === '') {
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO ads
                (owner_id, title, description, image, category_id, location, phone, email, address, rating, link, detail_layout, profile_subtitle, profile_tags, contact_url, is_city_pride, is_featured, is_active, submission_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'profile', ?, ?, ?, 0, 0, 0, 'pending')");
            $saved = $stmt->execute([
                auth_session_user_id() ?: null,
                $title,
                $description,
                $mainImage,
                $categoryId,
                $location !== '' ? $location : null,
                $phone !== '' ? $phone : null,
                $email !== '' ? $email : null,
                $address !== '' ? $address : null,
                4.5,
                $link !== '' ? $link : null,
                $profileSubtitle !== '' ? $profileSubtitle : null,
                json_encode(normalizeAdProfileTags($profileTags), JSON_UNESCAPED_UNICODE),
                normalizeAdContactUrl($contactUrl)
            ]);

            if ($saved) {
                $adId = (int) $pdo->lastInsertId();
                $galleryDescriptions = $_POST['gallery_descriptions'] ?? [];

                if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['tmp_name'])) {
                    foreach ($_FILES['gallery']['tmp_name'] as $index => $tmpName) {
                        if ($_FILES['gallery']['error'][$index] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        if ($_FILES['gallery']['error'][$index] !== UPLOAD_ERR_OK) {
                            continue;
                        }

                        $galleryFile = [
                            'name' => $_FILES['gallery']['name'][$index],
                            'type' => $_FILES['gallery']['type'][$index],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['gallery']['error'][$index],
                            'size' => $_FILES['gallery']['size'][$index]
                        ];
                        $galleryUpload = uploadFile($galleryFile);
                        if ($galleryUpload['success']) {
                            $uploadedFiles[] = $galleryUpload['filename'];
                            addAdGalleryImage($adId, $galleryUpload['filename'], trim($galleryDescriptions[$index] ?? ''));
                        }
                    }
                }

                $_SESSION['public_ad_last_submission_at'] = time();
                $_SESSION['public_ad_csrf'] = bin2hex(random_bytes(32));
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true, 'message' => 'Ogłoszenie przekazano do moderacji.'], JSON_UNESCAPED_UNICODE);
                    exit();
                }
                redirect(SITE_URL . '/submit-ad.php?submitted=1');
                exit();
            }

            foreach ($uploadedFiles as $filename) {
                deleteFile($filename);
            }
            $error = 'Nie udało się zapisać zgłoszenia. Spróbuj ponownie za chwilę.';
        }
    }
}

if ($wantsJson) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $error !== '' ? $error : 'Nie udało się przyjąć zgłoszenia.'], JSON_UNESCAPED_UNICODE);
    exit();
}
?>

<?php if (!$isFragment): require_once dirname(__DIR__, 3) . '/includes/header.php'; endif; ?>

<section class="public-submission-page">
    <div class="public-submission-card">
        <div class="public-submission-intro">
            <span class="submission-icon"><?= getCategorySvg('plus') ?></span>
            <div>
                <h1>Dodaj ogłoszenie</h1>
                <p>Wypełnij formularz, a Twoje ogłoszenie trafi do administratora do sprawdzenia. Zostanie opublikowane dopiero po akceptacji.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="public-form-alert success" role="status">
                <strong>Dziękujemy za zgłoszenie.</strong>
                <span>Ogłoszenie zostało przekazane do moderacji. Po akceptacji pojawi się w wybranej kategorii.</span>
            </div>
        <?php elseif ($error !== ''): ?>
            <div class="public-form-alert error" role="alert"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
                <form method="POST" action="<?= SITE_URL ?>/submit-ad.php" enctype="multipart/form-data" class="public-ad-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= sanitize($_SESSION['public_ad_csrf']) ?>">
                <div class="honeypot-field" aria-hidden="true">
                    <label for="website">Strona internetowa</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label for="title">Tytuł ogłoszenia <span aria-hidden="true">*</span></label><span id="public-title-counter" class="public-char-counter" aria-live="polite"></span></div>
                    <input type="text" id="title" name="title" value="<?= sanitize($title) ?>" maxlength="255" required data-public-counter="public-title-counter" placeholder="Np. Usługa lub oferta dla mieszkańców">
                                        <span class="public-field-hint">Podaj krótki, konkretny tytuł.</span>
                </div>
                <div class="public-form-group">
                    <label for="profile_subtitle">Podtytuł</label>
                    <input type="text" id="profile_subtitle" name="profile_subtitle" value="<?= sanitize($profileSubtitle) ?>" maxlength="255" placeholder="Np. Wszystko do domu, warsztatu i ogrodu">
                    <span class="public-field-hint">Opcjonalny, krótki podpis wyświetlany pod tytułem.</span>
                </div>
                <div class="public-form-group">
                    <label for="category_id">Kategoria <span aria-hidden="true">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">— Wybierz kategorię —</option>
                        <?php foreach ($adCategoryTree as $category): ?>
                            <?php if (!empty($category['children'])): ?>
                                <optgroup label="<?= sanitize($category['name']) ?>">
                                    <?php foreach ($category['children'] as $child): ?>
                                        <option value="<?= (int) $child['id'] ?>" <?= $categoryId === (int) $child['id'] ? 'selected' : '' ?>><?= sanitize($child['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php else: ?>
                                <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= sanitize($category['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label for="description">Treść ogłoszenia <span aria-hidden="true">*</span></label><span id="public-description-counter" class="public-char-counter" aria-live="polite"></span></div>
                    <textarea id="description" name="description" rows="7" required data-public-counter="public-description-counter" placeholder="Opisz ofertę, najważniejsze informacje oraz sposób kontaktu."><?= sanitize($description) ?></textarea>
                    <span class="public-field-hint">Treść zostanie sprawdzona przez administratora przed publikacją.</span>
                </div>

                <div class="public-form-group">
                    <label for="profile_tags">Tagi</label>
                    <input type="text" id="profile_tags" name="profile_tags" value="<?= sanitize($profileTags) ?>" maxlength="350" placeholder="Np. Ogrodnictwo, Narzędzia, Farby i kleje">
                    <span class="public-field-hint">Wpisz maksymalnie 8 tagów, rozdzielając je przecinkami.</span>
                </div>

                <div class="public-form-row">
                    <div class="public-form-group">
                        <label for="location">Miejscowość</label>
                        <input type="text" id="location" name="location" value="<?= sanitize($location) ?>" maxlength="255" placeholder="Np. Krosno Odrzańskie">
                    </div>
                    <div class="public-form-group">
                        <label for="address">Ulica i numer / wieś i numer</label>
                        <input type="text" id="address" name="address" value="<?= sanitize($address) ?>" maxlength="255" placeholder="Np. ul. Przykładowa 12 lub Osiecznica 25">
                    </div>
                </div>

                <div class="public-form-row">
                    <div class="public-form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" value="<?= sanitize($phone) ?>" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="Np. +48 600 000 000">
                    </div>
                    <div class="public-form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= sanitize($email) ?>" maxlength="255" autocomplete="email" placeholder="kontakt@przyklad.pl">
                    </div>
                </div>

                <div class="public-form-group">
                    <label for="link">Link (opcjonalnie)</label>
                    <input type="url" id="link" name="link" value="<?= sanitize($link) ?>" placeholder="https://...">
                </div>

                <div class="public-form-group">
                    <label for="contact_url">Link kontaktowy</label>
                    <input type="url" id="contact_url" name="contact_url" value="<?= sanitize($contactUrl) ?>" placeholder="https://..., tel:+48600000000 lub mailto:kontakt@przyklad.pl">
                    <span class="public-field-hint">Wymagany jest link kontaktowy, telefon albo e-mail.</span>
                </div>

                <div class="public-form-group">
                    <label for="image">Zdjęcie główne <span aria-hidden="true">*</span></label>
                    <input type="file" id="image" name="image" class="public-main-image-input" accept="image/avif,image/jpeg,image/png,image/gif,image/webp" data-public-preview="public-image-preview">
                    <button type="button" class="public-upload-zone" data-public-file-trigger="image" aria-describedby="public-image-hint">
                        <span class="public-upload-icon" aria-hidden="true">↑</span>
                        <span><strong>Wybierz zdjęcie</strong><small id="public-image-hint">JPG, PNG, GIF, WebP lub AVIF · maks. 5 MB · łącznie do 5 zdjęć</small></span>
                    </button>
                    <div id="public-image-preview" class="public-image-preview" aria-live="polite"></div>
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label>Zdjęcia dodatkowe</label><button type="button" class="public-add-gallery" id="add-gallery-image">Dodaj zdjęcie</button></div>
                    <span class="public-field-hint">Możesz dodać maksymalnie 4 zdjęcia dodatkowe wraz z krótkim opisem.</span>
                    <div id="public-gallery-container"></div>
                </div>

                <div class="public-form-notice">
                    <span aria-hidden="true">i</span>
                    <p>Wysłanie formularza nie oznacza automatycznej publikacji. Administrator może zaakceptować, odrzucić lub edytować zgłoszenie przed opublikowaniem.</p>
                </div>

                <div class="public-form-actions">
                    <button type="submit" class="public-submit-button">Dodaj ogłoszenie</button>
                    <a href="<?= SITE_URL ?>/" class="public-cancel-link">Wróć do strony głównej</a>
                </div>
            </form>
        <?php else: ?>
            <div class="public-form-actions success-actions">
                <a href="<?= SITE_URL ?>/submit-ad.php" class="public-submit-button secondary">Dodaj kolejne ogłoszenie</a>
                <a href="<?= SITE_URL ?>/" class="public-cancel-link">Wróć do strony głównej</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!$isFragment): require_once dirname(__DIR__, 3) . '/includes/footer.php'; endif; ?>
