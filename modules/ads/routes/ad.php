<?php
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

$id = $_GET['id'] ?? null;
if (empty($id)) {
    redirect(SITE_URL . '/index.php');
    exit;
}

$ad = getAdById($id);
if (!$ad) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = '404 - Ogłoszenie nie znalezione';
} else {
    $pageTitle = $ad['title'];
    $pageDescription = createMetaDescription($ad['description']);
    $canonicalUrl = SITE_URL . '/ad.php?id=' . (int) $ad['id'];
    $pageImage = getOpenGraphImageUrl($ad['image']) ?? (SITE_URL . '/assets/images/hero/ad.php.avif');
    $pageImageAlt = $ad['title'];
    $pageType = 'WebPage';
    $openGraphType = 'website';
    $pageSchema = ['@type' => 'WebPage', 'name' => $pageTitle, 'image' => $pageImage];
}

header('Cache-Control: no-cache, must-revalidate');
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

<?php if ($ad): ?>
    <nav class="breadcrumbs" aria-label="Okruszki">
        <a href="<?= SITE_URL ?>/index.php">Strona główna</a>
        <span aria-hidden="true">/</span>
        <a href="<?= SITE_URL ?>/category.php">Ogłoszenia</a>
        <span aria-hidden="true">/</span>
        <a href="<?= SITE_URL ?>/category.php?category=<?= rawurlencode((string) $ad['category_slug']) ?>"><?= sanitize($ad['category_name']) ?></a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= sanitize($ad['title']) ?></span>
    </nav>
<?php endif; ?>

<?php if (!$ad): ?>
    <div class="page-header">
        <h1>404</h1>
        <p>Przepraszamy, ogłoszenie nie zostało znalezione.</p>
        <a href="<?= SITE_URL ?>/index.php" class="ad-external-btn" style="margin-top: 1rem;">← Wróć do strony głównej</a>
    </div>
<?php elseif (($ad['detail_layout'] ?? 'legacy') === 'profile'): ?>
    <?php
    $gallery = array_slice(getAdGallery((int) $ad['id']), 0, 4);
    $tags = getAdProfileTags($ad);
    $contactUrl = getAdProfileContactUrl($ad);
    $mainImageUrl = !empty($ad['image']) ? SITE_URL . '/assets/uploads/' . rawurlencode($ad['image']) : '';
    $plainDescription = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($ad['description'] ?? ''))));
    $descriptionLength = function_exists('mb_strlen') ? mb_strlen($plainDescription) : strlen($plainDescription);
    $isLongDescription = $descriptionLength > 220;
    $isExternalContact = str_starts_with($contactUrl, 'http://') || str_starts_with($contactUrl, 'https://');
    $profileEmail = trim((string) ($ad['email'] ?? ''));
    $facebookUrl = !empty($ad['link']) && preg_match('~^https?://(?:www\.)?facebook\.com/~i', $ad['link']) ? (string) $ad['link'] : '';
    ?>
    <article class="ad-profile" data-ad-profile>
        <div class="ad-profile__media">
            <div class="ad-profile__hero">
                <?php if ($mainImageUrl !== ''): ?>
                    <img src="<?= sanitize($mainImageUrl) ?>" alt="<?= sanitize($ad['title']) ?>" data-ad-profile-main-image fetchpriority="high" decoding="async">
                <?php else: ?>
                    <div class="ad-profile__placeholder">Brak zdjęcia</div>
                <?php endif; ?>
            </div>
            <?php if (!empty($ad['is_city_pride'])): ?>
                <img class="ad-profile__city-pride" src="<?= SITE_URL ?>/assets/images/badge-duma_miasta.svg" alt="Duma Miasta">
            <?php endif; ?>
        </div>

        <div class="ad-profile__content">
            <header class="ad-profile__header">
                <h1><?= sanitize($ad['title']) ?></h1>
                <?php if (!empty($ad['profile_subtitle'])): ?>
                    <p class="ad-profile__subtitle"><?= sanitize($ad['profile_subtitle']) ?></p>
                <?php endif; ?>
                <?php if (!empty($ad['location']) || !empty($ad['address'])): ?>
                    <div class="ad-profile__location">
                        <?= getCategorySvg('location') ?>
                        <span>
                            <?php if (!empty($ad['location'])): ?><strong><?= sanitize($ad['location']) ?></strong><?php endif; ?>
                            <?php if (!empty($ad['location']) && !empty($ad['address'])): ?><br><?php endif; ?>
                            <?php if (!empty($ad['address'])): ?><?= sanitize($ad['address']) ?><?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ($tags !== []): ?>
                <ul class="ad-profile__tags" aria-label="Tagi ogłoszenia">
                    <?php foreach ($tags as $tag): ?>
                        <li><?= sanitize($tag) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <section class="ad-profile__description">
                <p class="<?= $isLongDescription ? 'is-collapsible' : '' ?>" data-ad-profile-description><?= nl2br(sanitize($ad['description'])) ?></p>
                <?php if ($isLongDescription): ?>
                    <button type="button" class="ad-profile__description-toggle" data-ad-profile-description-toggle aria-expanded="false">Szczegóły</button>
                <?php endif; ?>
            </section>

            <?php if ($gallery !== []): ?>
                <section class="ad-profile__gallery" aria-labelledby="ad-profile-gallery-title">
                    <h2 id="ad-profile-gallery-title">Galeria</h2>
                    <div class="ad-profile__thumbnails">
                        <?php foreach ($gallery as $index => $galleryImage): ?>
                            <?php $galleryUrl = SITE_URL . '/assets/uploads/' . rawurlencode($galleryImage['image']); ?>
                            <button type="button" class="ad-profile__thumbnail" data-ad-profile-thumbnail data-ad-profile-image="<?= sanitize($galleryUrl) ?>" data-ad-profile-alt="<?= sanitize($galleryImage['description'] ?: $ad['title']) ?>" aria-pressed="false" aria-label="Pokaż zdjęcie <?= (int) ($index + 1) ?> w obszarze głównym">
                                <img src="<?= sanitize($galleryUrl) ?>" alt="<?= sanitize($galleryImage['description'] ?: 'Miniatura galerii') ?>" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($profileEmail !== '' || $facebookUrl !== ''): ?>
                <section class="ad-profile__contact-options" aria-label="Dodatkowe dane kontaktowe">
                    <?php if ($profileEmail !== ''): ?>
                        <a class="ad-profile__contact-option" href="mailto:<?= sanitize($profileEmail) ?>" aria-label="Napisz e-mail na adres <?= sanitize($profileEmail) ?>">
                            <?= getCategorySvg('email') ?><span><?= sanitize($profileEmail) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($facebookUrl !== ''): ?>
                        <a class="ad-profile__contact-option ad-profile__contact-option--facebook" href="<?= sanitize($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Otwórz profil Sklepu Metalowego Rampa na Facebooku">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.64 22v-8.21h2.76l.41-3.2h-3.17V8.55c0-.93.26-1.56 1.59-1.56h1.7V4.13a22.82 22.82 0 0 0-2.48-.13c-2.45 0-4.13 1.49-4.13 4.23v2.36H7.55v3.2h2.77V22h3.32Z"/></svg><span>Facebook</span>
                        </a>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($contactUrl !== ''): ?>
                <a class="ad-profile__contact" href="<?= sanitize($contactUrl) ?>"<?= $isExternalContact ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>Skontaktuj się</a>
            <?php endif; ?>
        </div>
    </article>
    <script src="<?= SITE_URL ?>/assets/js/ad-profile.js?v=<?= ASSET_VERSION ?>"></script>
<?php else: ?>
    <div class="ad-detail-view">
        <div class="ad-detail-image">
            <?php if (!empty($ad['image'])): ?>
                <img src="<?= SITE_URL ?>/assets/uploads/<?= rawurlencode($ad['image']) ?>" alt="<?= sanitize($ad['title']) ?>" fetchpriority="high" decoding="async">
            <?php else: ?>
                <div class="ad-image-placeholder">Brak zdjęcia</div>
            <?php endif; ?>
        </div>

        <div class="ad-detail-content">
            <div class="ad-detail-header">
                <span class="ad-detail-category"><?= sanitize($ad['category_name']) ?></span>
                <h1 class="ad-detail-title"><?= sanitize($ad['title']) ?></h1>
            </div>

            <div class="ad-detail-description">
                <h3>Opis</h3>
                <p><?= nl2br(sanitize($ad['description'])) ?></p>
            </div>

            <?php
            $phoneLink = !empty($ad['phone']) ? preg_replace('/[^0-9+]/', '', $ad['phone']) : '';
            $hasContactDetails = $phoneLink !== '' || !empty($ad['email']) || !empty($ad['address']);
            ?>
            <?php if ($hasContactDetails): ?>
                <section class="ad-contact-details" aria-label="Dane kontaktowe ogłoszenia">
                    <?php if ($phoneLink !== ''): ?>
                        <a class="ad-contact-details__item" href="tel:<?= sanitize($phoneLink) ?>" aria-label="Zadzwoń pod numer <?= sanitize($ad['phone']) ?>">
                            <?= getCategorySvg('phone') ?><span><?= sanitize($ad['phone']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($ad['email'])): ?>
                        <a class="ad-contact-details__item" href="mailto:<?= sanitize($ad['email']) ?>" aria-label="Napisz e-mail na adres <?= sanitize($ad['email']) ?>">
                            <?= getCategorySvg('email') ?><span><?= sanitize($ad['email']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($ad['address'])): ?>
                        <div class="ad-contact-details__item ad-contact-details__address"><?= getCategorySvg('location') ?><span><?= sanitize($ad['address']) ?></span></div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="ad-detail-actions">
                <?php if (!empty($ad['link'])): ?>
                    <a href="<?= sanitize($ad['link']) ?>" target="_blank" rel="noopener noreferrer" class="ad-external-btn">Odwiedź stronę <?= getCategorySvg('external') ?></a>
                <?php endif; ?>
                <button type="button" class="content-share-button" data-share-url="<?= sanitize($canonicalUrl) ?>" data-share-title="<?= sanitize($pageTitle) ?>" data-share-text="<?= sanitize($pageDescription) ?>">
                    <?= getCategorySvg('share') ?><span>Udostępnij ogłoszenie</span>
                </button>
            </div>
            <div class="ad-detail-back"><a href="javascript:history.back()" class="back-link"><?= getCategorySvg('back') ?> Powrót</a></div>
        </div>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
