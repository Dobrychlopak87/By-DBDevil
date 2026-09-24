<?php
// Strona kategorii ogłoszeń
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Pobierz kategorię z URL
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$normalizedCategorySlug = strtolower($categorySlug);
if ($categorySlug !== '' && $categorySlug !== $normalizedCategorySlug) {
    header('Location: ' . SITE_URL . '/category.php?category=' . rawurlencode($normalizedCategorySlug), true, 301);
    exit;
}

$categoryNotFound = false;
if ($categorySlug === '') {
    $pageTitle = 'Wszystkie ogłoszenia';
    $ads = getAllAds();
    $currentCategory = null;
} else {
    $currentCategory = getAdCategoryBySlug($normalizedCategorySlug);
    if ($currentCategory) {
        $pageTitle = $currentCategory['name'];
        $ads = getAdsByCategory($normalizedCategorySlug);
    } else {
        http_response_code(404);
        $pageTitle = '404 - Kategoria nie znaleziona';
        $pageDescription = 'Podana kategoria ogłoszeń nie istnieje w serwisie 66600.PL.';
        $pageRobots = 'noindex, nofollow';
        $ads = [];
        $currentCategory = null;
        $categoryNotFound = true;
    }
}

$canonicalUrl = SITE_URL . '/category.php';
if ($currentCategory !== null && !$categoryNotFound) {
    $canonicalUrl .= '?category=' . rawurlencode((string) $currentCategory['slug']);
}

// Pobierz wszystkie kategorie do menu
$adCategories = getAdCategories();
$placeholderCategories = [];
if (!$categoryNotFound) {
    if ($currentCategory !== null) {
        $selectableCategories = getAdSelectableCategories();
        $placeholderCategory = null;
        foreach ($selectableCategories as $selectableCategory) {
            if ((int) $selectableCategory['id'] === (int) $currentCategory['id'] || (int) ($selectableCategory['parent_id'] ?? 0) === (int) $currentCategory['id']) {
                $placeholderCategory = $selectableCategory;
                break;
            }
        }
        if ($placeholderCategory !== null) {
            $placeholderCategories[] = [
                'category_id' => (int) $placeholderCategory['id'],
                'category_name' => (string) $currentCategory['name'],
            ];
        }
    } else {
        foreach (getAdSelectableCategories() as $selectableCategory) {
            $placeholderCategories[] = [
                'category_id' => (int) $selectableCategory['id'],
                'category_name' => (string) $selectableCategory['name'],
            ];
        }
    }
}

// Ustaw nagłówki HTTP
header("Cache-Control: no-cache, must-revalidate");
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

    <!-- Nagłówek strony -->
    <div class="page-header">
        <?php if ($categoryNotFound): ?>
            <h1>404</h1>
            <p>Przepraszamy, podana kategoria ogłoszeń nie została znaleziona.</p>
            <a href="<?= SITE_URL ?>/category.php" class="ad-external-btn" style="margin-top: 1rem;">← Wróć do wszystkich ogłoszeń</a>
        <?php elseif ($currentCategory): ?>
            <h1><?= sanitize($currentCategory['name']) ?></h1>
            <p>Przeglądaj ogłoszenia w kategorii <strong><?= sanitize($currentCategory['name']) ?></strong></p>
        <?php else: ?>
            <h1>Wszystkie ogłoszenia</h1>
            <p>Przeglądaj wszystkie dostępne ogłoszenia</p>
        <?php endif; ?>
    </div>

    <!-- Lista ogłoszeń -->
    <?php if (!$categoryNotFound): ?>
    <section class="ads-section">
        <?php if (!empty($ads)): ?>
            <div class="ads-grid">
                <?php foreach ($ads as $ad): ?>
                    <?php
                    $adShareUrl = SITE_URL . '/ad.php?id=' . (int) $ad['id'];
                    $adShareText = createMetaDescription($ad['description']);
                    ?>
                    <article class="ad-card shareable-card" data-ad-id="<?= (int) $ad['id'] ?>">
                        <a href="ad.php?id=<?= (int) $ad['id'] ?>" class="ad-card-link card-primary-link">
                            <div class="ad-image-container">
                                <?php if (!empty($ad['image'])): ?>
                                    <img src="<?= getPublicUploadUrl($ad['image']) ?>" alt="<?= sanitize($ad['title']) ?>" class="ad-image" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="ad-image-placeholder"><span>Brak zdjęcia</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="ad-content">
                                <h3 class="ad-title"><?= sanitize($ad['title']) ?></h3>
                                <div class="ad-meta"><span><strong>Kategoria:</strong> <?= sanitize($ad['category_name']) ?></span></div>
                                <?php if (!empty($ad['description'])): ?>
                                    <p class="ad-description-excerpt" style="font-size: 0.8rem; color: var(--light-text); margin-top: 0.5rem;">
                                        <?= truncateText(strip_tags($ad['description']), 80) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-share-action">
                            <button type="button" class="card-share-button"
                                    data-share-url="<?= sanitize($adShareUrl) ?>"
                                    data-share-title="<?= sanitize($ad['title']) ?>"
                                    data-share-text="<?= sanitize($adShareText) ?>"
                                    aria-label="Udostępnij ogłoszenie: <?= sanitize($ad['title']) ?>">
                                <?= getCategorySvg('share') ?>
                                <span>Udostępnij</span>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ads-grid">
                <?php foreach ($placeholderCategories as $placeholder): ?>
                    <article class="ad-card" data-placeholder-ad data-category-id="<?= (int) $placeholder['category_id'] ?>">
                        <a href="<?= SITE_URL ?>/submit-ad.php?category_id=<?= (int) $placeholder['category_id'] ?>" class="ad-card-link card-primary-link" data-open-submission="ads" data-category-id="<?= (int) $placeholder['category_id'] ?>" aria-label="Dodaj nowe ogłoszenie w kategorii <?= sanitize($placeholder['category_name']) ?>">
                            <div class="ad-image-container">
                                <img src="<?= SITE_URL ?>/assets/uploads/miniaturka_dodaj_ogl.avif" alt="Dodaj nowe ogłoszenie" class="ad-image" loading="lazy" decoding="async">
                            </div>
                            <div class="ad-content">
                                <h3 class="ad-title">Dodaj nowe ogłoszenie</h3>
                                <div class="ad-meta"><span><strong>Kategoria:</strong> <?= sanitize($placeholder['category_name']) ?></span></div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
