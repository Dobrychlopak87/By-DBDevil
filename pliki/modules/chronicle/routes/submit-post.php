<?php
// Publiczny formularz zgłaszania wpisów mieszkańców do moderacji.
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/auth/lib.php';

auth_ensure_schema();
ensureBlogModerationSchema();

$isFragment = isset($_GET['fragment']) && $_GET['fragment'] === '1';
$wantsJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

$pageTitle = 'Dodaj wpis mieszkańca';
$pageDescription = 'Prześlij artykuł do wspólnej kroniki miasta. Każdy wpis jest sprawdzany przez administratora przed publikacją.';
$canonicalUrl = SITE_URL . '/submit-post.php';
$pageRobots = 'noindex, nofollow';

$blogCategories = getCommunityBlogCategories();
$validCategoryIds = array_map('intval', array_column($blogCategories, 'id'));

if (empty($_SESSION['public_post_csrf'])) {
    $_SESSION['public_post_csrf'] = bin2hex(random_bytes(32));
}

$title = '';
$content = '';
$authorSignature = '';
$categoryId = '';
$error = '';
$success = isset($_GET['submitted']) && $_GET['submitted'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $authorSignature = trim($_POST['author_signature'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
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
    $contentLength = function_exists('mb_strlen') ? mb_strlen($content) : strlen($content);
    $signatureLength = function_exists('mb_strlen') ? mb_strlen($authorSignature) : strlen($authorSignature);

    if (!hash_equals($_SESSION['public_post_csrf'], $csrfToken)) {
        $error = 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.';
    } elseif ($honeypot !== '') {
        $error = 'Nie udało się przyjąć zgłoszenia.';
    } elseif (!empty($_SESSION['public_post_last_submission_at']) && (time() - (int) $_SESSION['public_post_last_submission_at']) < 30) {
        $error = 'Odczekaj chwilę przed wysłaniem kolejnego wpisu.';
    } elseif ($galleryUploadCount > 6) {
        $error = 'Możesz dodać maksymalnie 6 dodatkowych zdjęć.';
    } elseif ($title === '' || $titleLength > 255) {
        $error = 'Podaj tytuł wpisu (maksymalnie 255 znaków).';
    } elseif ($content === '' || $contentLength > 10000) {
        $error = 'Treść wpisu jest wymagana i może mieć maksymalnie 10 000 znaków.';
    } elseif (!in_array($categoryId, $validCategoryIds, true)) {
        $error = 'Wybierz jedną z dostępnych kategorii kroniki miasta.';
    } elseif ($signatureLength > 120) {
        $error = 'Podpis autora może mieć maksymalnie 120 znaków.';
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
            $postId = createCommunityBlogSubmission([
                'owner_id' => auth_session_user_id() ?: null,
                'title' => $title,
                'content' => $content,
                'image' => $mainImage,
                'category_id' => $categoryId,
                'author_signature' => $authorSignature !== '' ? $authorSignature : null
            ]);

            if ($postId > 0) {
                $galleryDescriptions = $_POST['gallery_descriptions'] ?? [];
                if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['tmp_name'])) {
                    foreach ($_FILES['gallery']['tmp_name'] as $index => $tmpName) {
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
                            addBlogPostGalleryImage($postId, $galleryUpload['filename'], trim($galleryDescriptions[$index] ?? ''));
                        }
                    }
                }

                $_SESSION['public_post_last_submission_at'] = time();
                $_SESSION['public_post_csrf'] = bin2hex(random_bytes(32));
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true, 'message' => 'Wpis przekazano do moderacji.'], JSON_UNESCAPED_UNICODE);
                    exit();
                }
                redirect(SITE_URL . '/submit-post.php?submitted=1');
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
                <h1>Dodaj wpis mieszkańca</h1>
                <p>Współtwórz kronikę Krosna Odrzańskiego i okolic. Wpis zostanie opublikowany w blogu dopiero po akceptacji administratora.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="public-form-alert success" role="status">
                <strong>Dziękujemy za Twój wpis.</strong>
                <span>Zgłoszenie trafiło do moderacji. Po akceptacji pojawi się w wybranej kategorii bloga.</span>
            </div>
        <?php elseif ($error !== ''): ?>
            <div class="public-form-alert error" role="alert"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" action="<?= SITE_URL ?>/submit-post.php" enctype="multipart/form-data" class="public-ad-form" data-gallery-multi-select="true" novalidate>
                <input type="hidden" name="csrf_token" value="<?= sanitize($_SESSION['public_post_csrf']) ?>">
                <div class="honeypot-field" aria-hidden="true">
                    <label for="website">Strona internetowa</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label for="title">Tytuł wpisu <span aria-hidden="true">*</span></label><span id="public-title-counter" class="public-char-counter" aria-live="polite"></span></div>
                    <input type="text" id="title" name="title" value="<?= sanitize($title) ?>" maxlength="255" required data-public-counter="public-title-counter" placeholder="Np. Wspomnienie, informacja lub inicjatywa mieszkańców">
                </div>

                <div class="public-form-group">
                    <label for="category_id">Kategoria kroniki miasta <span aria-hidden="true">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">— Wybierz kategorię —</option>
                        <?php foreach ($blogCategories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= sanitize($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label for="content">Treść wpisu <span aria-hidden="true">*</span></label><span id="public-content-counter" class="public-char-counter" aria-live="polite"></span></div>
                    <textarea id="content" name="content" rows="10" maxlength="10000" required data-public-counter="public-content-counter" placeholder="Opowiedz o wydarzeniu, miejscu, inicjatywie lub historii miasta."><?= sanitize($content) ?></textarea>
                    <span class="public-field-hint">Treść może mieć maksymalnie 10 000 znaków. Administrator sprawdzi wpis przed publikacją.</span>
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label for="author_signature">Podpis autora</label><span id="public-signature-counter" class="public-char-counter" aria-live="polite"></span></div>
                    <input type="text" id="author_signature" name="author_signature" value="<?= sanitize($authorSignature) ?>" maxlength="120" data-public-counter="public-signature-counter" placeholder="Np. Anna z Krosna Odrzańskiego">
                    <span class="public-field-hint">Opcjonalny podpis wyświetlany przy opublikowanym wpisie, maksymalnie 120 znaków.</span>
                </div>

                <div class="public-form-group">
                    <label for="image">Zdjęcie główne</label>
                    <input type="file" id="image" name="image" class="public-main-image-input" accept="image/avif,image/jpeg,image/png,image/gif,image/webp" data-public-preview="public-image-preview">
                    <button type="button" class="public-upload-zone" data-public-file-trigger="image" aria-describedby="public-image-hint">
                        <span class="public-upload-icon" aria-hidden="true">↑</span>
                        <span><strong>Wybierz zdjęcie</strong><small id="public-image-hint">JPG, PNG, GIF, WebP lub AVIF · maks. 5 MB</small></span>
                    </button>
                    <div id="public-image-preview" class="public-image-preview" aria-live="polite"></div>
                </div>

                <div class="public-form-group">
                    <div class="public-label-row"><label>Zdjęcia dodatkowe</label><button type="button" class="public-add-gallery" id="add-gallery-image">Dodaj zdjęcie</button></div>
                    <span class="public-field-hint">Możesz dodać maksymalnie 6 dodatkowych zdjęć wraz z krótkim opisem.</span>
                    <div id="public-gallery-container"></div>
                </div>

                <div class="public-form-notice">
                    <span aria-hidden="true">i</span>
                    <p>Wysłanie formularza nie oznacza automatycznej publikacji. Administrator może zaakceptować, odrzucić lub edytować zgłoszenie przed opublikowaniem.</p>
                </div>

                <div class="public-form-actions">
                    <button type="submit" class="public-submit-button">Prześlij wpis do moderacji</button>
                    <a href="<?= SITE_URL ?>/blog.php" class="public-cancel-link">Wróć do bloga</a>
                </div>
            </form>
        <?php else: ?>
            <div class="public-form-actions success-actions">
                <a href="<?= SITE_URL ?>/submit-post.php" class="public-submit-button secondary">Dodaj kolejny wpis</a>
                <a href="<?= SITE_URL ?>/blog.php" class="public-cancel-link">Przejdź do bloga</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!$isFragment): require_once dirname(__DIR__, 3) . '/includes/footer.php'; endif; ?>
