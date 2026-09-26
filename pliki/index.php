<?php
// Strona główna - 66600.PL
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/modules/ads/contract.php';
require_once __DIR__ . '/modules/chronicle/contract.php';

$requestedPath = trim((string) ($_GET['url'] ?? ''), '/');
if ($requestedPath !== '') {
    http_response_code(404);
    $pageTitle = '404 - Strona nie znaleziona';
    $pageDescription = 'Podany adres nie prowadzi do istniejącej strony serwisu 66600.PL.';
    $pageRobots = 'noindex, nofollow';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="page-header">
        <h1>404</h1>
        <p>Przepraszamy, podana strona nie została znaleziona.</p>
        <a href="<?= SITE_URL ?>/index.php" class="ad-external-btn" style="margin-top: 1rem;">← Wróć do strony głównej</a>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'Jesteś u siebie';
$bodyClass = 'home-page';

$adCategories = getAdSelectableCategories();
$homepageAds = ads_homepage_cards(6);
$placeholderAds = array_map(static function (array $category): array {
    return [
        'category_id' => (int) $category['id'],
        'category_name' => (string) $category['name'],
        'category_slug' => (string) $category['slug'],
    ];
}, $adCategories);
$featuredPosts = chronicle_homepage_cards(6);
$featuredChronicleHero = $featuredPosts[0] ?? null;
$featuredChronicleEntries = array_slice($featuredPosts, 1);
$activePoll = getPublicPoll();
$blogCategories = chronicle_navigation()['categories'];

header("Cache-Control: no-cache, must-revalidate");
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="mobile-sector-switcher" role="tablist" aria-label="Główne sektory strony">
        <button type="button" class="mobile-sector-switcher__tab active" role="tab" id="sector-tab-ads" aria-selected="true" aria-controls="sector-ads" data-home-sector="ads">Ogłoszenia</button>
        <button type="button" class="mobile-sector-switcher__tab" role="tab" id="sector-tab-blog" aria-selected="false" aria-controls="sector-blog" data-home-sector="blog" tabindex="-1">Kronika miasta</button>
    </div>
    <p class="mobile-sector-status" id="mobile-sector-status" aria-live="polite">Ogłoszenia</p>

    <div class="mobile-sector-viewport" data-home-sectors>
        <div class="mobile-sector-track">
            <section class="mobile-sector mobile-sector--ads" id="sector-ads" role="tabpanel" aria-labelledby="sector-tab-ads" data-home-sector-panel="ads" tabindex="-1" aria-hidden="false">
                <div class="section-title">
                    <h2>Popularne</h2>
                    <a href="category.php" class="see-all-link">Zobacz wszystkie</a>
                </div>
                <div class="ads-grid">
                    <?php if (!empty($homepageAds)): ?>
                        <?php foreach ($homepageAds as $ad): ?>
                            <?php
                            $adShareUrl = SITE_URL . '/ad.php?id=' . (int) $ad['id'];
                            $adShareText = createMetaDescription($ad['description']);
                            $adImage = !empty($ad['image']) ? getResponsiveUploadImage($ad['image'], [480, 768]) : null;
                            ?>
                            <article class="ad-card shareable-card" data-ad-id="<?= (int) $ad['id'] ?>">
                                <a href="<?= SITE_URL ?>/ad.php?id=<?= (int) $ad['id'] ?>" class="ad-card-link card-primary-link">
                                    <div class="ad-image-container">
                                        <?php if ($adImage !== null): ?>
                                            <img src="<?= sanitize($adImage['src']) ?>"<?= $adImage['srcset'] !== '' ? ' srcset="' . sanitize($adImage['srcset']) . '" sizes="(max-width: 700px) 100vw, 33vw"' : '' ?> alt="<?= sanitize($ad['title']) ?>" class="ad-image" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <div class="ad-image-placeholder"><span>Brak zdjęcia</span></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ad-content">
                                        <h3 class="ad-title"><?= sanitize($ad['title']) ?></h3>
                                        <div class="ad-meta"><span><strong>Kategoria:</strong> <?= sanitize($ad['category_name']) ?></span></div>
                                        <?php if (!empty($ad['description'])): ?><p class="ad-description-excerpt"><?= sanitize(truncateText(strip_tags($ad['description']), 80)) ?></p><?php endif; ?>
                                    </div>
                                </a>
                                <div class="card-share-action"><button type="button" class="card-share-button" data-share-url="<?= sanitize($adShareUrl) ?>" data-share-title="<?= sanitize($ad['title']) ?>" data-share-text="<?= sanitize($adShareText) ?>" aria-label="Udostępnij ogłoszenie: <?= sanitize($ad['title']) ?>"><?= getCategorySvg('share') ?><span>Udostępnij</span></button></div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($placeholderAds as $placeholder): ?>
                            <article class="ad-card" data-placeholder-ad data-category-id="<?= (int) $placeholder['category_id'] ?>">
                                <a href="<?= SITE_URL ?>/submit-ad.php?category_id=<?= (int) $placeholder['category_id'] ?>" class="ad-card-link card-primary-link" data-open-submission="ads" data-category-id="<?= (int) $placeholder['category_id'] ?>" aria-label="Dodaj nowe ogłoszenie w kategorii <?= sanitize($placeholder['category_name']) ?>"><div class="ad-image-container"><img src="<?= SITE_URL ?>/assets/uploads/miniaturka_dodaj_ogl.avif" alt="Dodaj nowe ogłoszenie" class="ad-image" loading="lazy" decoding="async"></div><div class="ad-content"><h3 class="ad-title">Dodaj nowe ogłoszenie</h3><div class="ad-meta"><span><strong>Kategoria:</strong> <?= sanitize($placeholder['category_name']) ?></span></div></div></a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="mobile-sector mobile-sector--blog chronicle-section" id="sector-blog" role="tabpanel" aria-labelledby="sector-tab-blog" data-home-sector-panel="blog" tabindex="-1" aria-hidden="true">
                <div class="chronicle-heading">
                    <p class="chronicle-date"><?= formatPolishChronicleDate('now') ?></p>
                    <a href="blog.php" class="see-all-link">Wszystkie wpisy</a>
                </div>
                <?php if ($activePoll !== null): ?>
                    <section class="poll-card" data-poll-card data-poll-id="<?= (int) $activePoll['id'] ?>" aria-labelledby="poll-question-<?= (int) $activePoll['id'] ?>">
                        <div class="poll-card-header"><span>Głos mieszkańców</span></div>
                        <h3 id="poll-question-<?= (int) $activePoll['id'] ?>"><?= sanitize($activePoll['question']) ?></h3>
                        <form class="poll-form" data-poll-form <?= $activePoll['has_voted'] ? 'hidden' : '' ?>>
                            <fieldset>
                                <legend class="sr-only">Wybierz odpowiedź</legend>
                                <?php foreach ($activePoll['options'] as $option): ?>
                                    <label class="poll-option" for="poll-option-<?= (int) $option['id'] ?>">
                                        <input id="poll-option-<?= (int) $option['id'] ?>" type="radio" name="poll_option" value="<?= (int) $option['id'] ?>">
                                        <span><?= sanitize($option['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <button class="poll-submit" type="submit">Zagłosuj</button>
                        </form>
                        <div class="poll-results" data-poll-results <?= $activePoll['has_voted'] ? '' : 'hidden' ?>>
                            <?php foreach ($activePoll['options'] as $option): ?>
                                <div class="poll-result<?= (int) $activePoll['selected_option_id'] === (int) $option['id'] ? ' is-selected' : '' ?>" data-poll-result data-option-id="<?= (int) $option['id'] ?>">
                                    <div class="poll-result-label"><span><?= sanitize($option['option_text']) ?></span><strong data-poll-percentage><?= (int) $option['percentage'] ?>%</strong></div>
                                    <div class="poll-result-track"><span data-poll-bar style="width: <?= (int) $option['percentage'] ?>%"></span></div>
                                </div>
                            <?php endforeach; ?>
                            <p class="poll-total" data-poll-total>Łącznie: <?= (int) $activePoll['total_votes'] ?> głosów</p>
                        </div>
                        <p class="poll-message" data-poll-message aria-live="polite"><?= $activePoll['has_voted'] ? 'Twój głos został zapisany.' : '' ?></p>
                        <?php if (!empty($activePoll['ends_at'])): ?>
                            <p class="poll-deadline">Ankieta trwa do <?= formatDate($activePoll['ends_at'], 'd.m.Y H:i') ?></p>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($featuredChronicleHero !== null): ?>
                    <?php
                        $heroShareUrl = getChroniclePostUrl($featuredChronicleHero['slug']);
                    $heroShareText = !empty($featuredChronicleHero['meta_description'])
                        ? $featuredChronicleHero['meta_description']
                        : createMetaDescription(!empty($featuredChronicleHero['excerpt']) ? $featuredChronicleHero['excerpt'] : $featuredChronicleHero['content']);
                    ?>
                    <article class="chronicle-hero">
                        <?php if (!empty($featuredChronicleHero['image'])): ?>
                            <?php $chronicleHeroImage = getResponsiveUploadImage($featuredChronicleHero['image'], [480, 768]); ?>
                            <a class="chronicle-hero__media" href="<?= getChroniclePostUrl($featuredChronicleHero['slug']) ?>">
                                <img src="<?= $chronicleHeroImage['src'] ?>"<?= $chronicleHeroImage['srcset'] !== '' ? ' srcset="' . sanitize($chronicleHeroImage['srcset']) . '" sizes="(max-width: 600px) calc(100vw - 2rem), 568px"' : '' ?> alt="<?= sanitize($featuredChronicleHero['title']) ?>" loading="lazy" decoding="async">
                            </a>
                        <?php endif; ?>
                        <div class="chronicle-hero__content">
                            <span class="chronicle-category">• <?= sanitize($featuredChronicleHero['category_name']) ?></span>
                            <h2><a href="<?= getChroniclePostUrl($featuredChronicleHero['slug']) ?>"><?= sanitize($featuredChronicleHero['title']) ?></a></h2>
                            <p><?= sanitize(!empty($featuredChronicleHero['excerpt']) ? $featuredChronicleHero['excerpt'] : truncateText(strip_tags($featuredChronicleHero['content']), 180)) ?> <a href="<?= getChroniclePostUrl($featuredChronicleHero['slug']) ?>" class="chronicle-cta chronicle-cta--inline" aria-label="Czytaj dalej: <?= sanitize($featuredChronicleHero['title']) ?>">Czytaj dalej</a></p>
                            <div class="chronicle-hero__actions chronicle-hero__actions--share">
                                <button type="button" class="card-share-button" data-share-url="<?= sanitize($heroShareUrl) ?>" data-share-title="<?= sanitize(!empty($featuredChronicleHero['meta_title']) ? $featuredChronicleHero['meta_title'] : $featuredChronicleHero['title']) ?>" data-share-text="<?= sanitize($heroShareText) ?>" aria-label="Udostępnij wpis: <?= sanitize($featuredChronicleHero['title']) ?>">
                                    <?= getCategorySvg('share') ?><span>Udostępnij</span>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endif; ?>

                <?php if (!empty($featuredChronicleEntries)): ?>
                    <div class="chronicle-timeline">
                        <?php foreach ($featuredChronicleEntries as $post): ?>
                            <?php
                            $postShareUrl = getChroniclePostUrl($post['slug']);
                            $postShareText = !empty($post['meta_description'])
                                ? $post['meta_description']
                                : createMetaDescription(!empty($post['excerpt']) ? $post['excerpt'] : $post['content']);
                            ?>
                            <article class="chronicle-entry">
                                <div class="chronicle-entry__layout<?= !empty($post['image']) ? ' has-media' : '' ?>">
                                    <?php if (!empty($post['image'])): ?>
                                        <?php $chronicleEntryImage = getResponsiveUploadImage($post['image'], [480]); ?>
                                        <a class="chronicle-entry__media" href="<?= getChroniclePostUrl($post['slug']) ?>" tabindex="-1" aria-hidden="true">
                                            <img src="<?= $chronicleEntryImage['src'] ?>"<?= $chronicleEntryImage['srcset'] !== '' ? ' srcset="' . sanitize($chronicleEntryImage['srcset']) . '" sizes="(max-width: 600px) 35vw, 200px"' : '' ?> alt="" loading="lazy" decoding="async">
                                        </a>
                                    <?php endif; ?>
                                    <div class="chronicle-entry__content">
                                        <div class="chronicle-entry__meta">
                                            <span class="chronicle-category">• <?= sanitize($post['category_name']) ?></span>
                                            <time datetime="<?= sanitize($post['created_at']) ?>"><?= formatDate($post['created_at']) ?></time>
                                        </div>
                                        <h3><a href="<?= getChroniclePostUrl($post['slug']) ?>"><?= sanitize($post['title']) ?></a></h3>
                                        <p><?= sanitize(!empty($post['excerpt']) ? $post['excerpt'] : truncateText(strip_tags($post['content']), 150)) ?></p>
                                    </div>
                                    <div class="chronicle-entry__metrics" aria-label="Aktywność wpisu">
                                        <span title="Pozytywne oceny"><?= getChronicleInteractionSvg('up') ?><strong><?= (int) ($post['positive_votes'] ?? 0) ?></strong></span>
                                        <span title="Komentarze"><?= getChronicleInteractionSvg('comment') ?><strong><?= (int) ($post['comments_count'] ?? 0) ?></strong></span>
                                    </div>
                                </div>
                                <div class="chronicle-entry__footer">
                                    <button type="button" class="card-share-button" data-share-url="<?= sanitize($postShareUrl) ?>" data-share-title="<?= sanitize(!empty($post['meta_title']) ? $post['meta_title'] : $post['title']) ?>" data-share-text="<?= sanitize($postShareText) ?>" aria-label="Udostępnij wpis: <?= sanitize($post['title']) ?>">
                                        <?= getCategorySvg('share') ?><span>Udostępnij</span>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($featuredChronicleHero === null): ?>
                    <div class="no-results">
                        <h3>Brak wpisów</h3>
                        <p>Wkrótce pojawią się tu najnowsze informacje z miasta.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
