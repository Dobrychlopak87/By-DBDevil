<?php
// Strona bloga - artykuły z kategorii menu hamburger
$bodyClass = 'chronicle-page';
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Pobierz kategorię z URL
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$categoryNotFound = false;
if ($categorySlug === '') {
    $pageTitle = 'Kronika miasta';
    $posts = getAllBlogPosts();
    $currentCategory = null;
} else {
    $currentCategory = getBlogCategoryBySlug($categorySlug);
    if ($currentCategory) {
        $pageTitle = $currentCategory['name'];
        $posts = getBlogPostsByCategory($categorySlug);
    } else {
        http_response_code(404);
        $pageTitle = '404 - Kategoria nie znaleziona';
        $pageDescription = 'Podana kategoria Kroniki Miasta nie istnieje w serwisie 66600.PL.';
        $pageRobots = 'noindex, nofollow';
        $posts = [];
        $currentCategory = null;
        $categoryNotFound = true;
    }
}

$canonicalUrl = SITE_URL . '/blog.php';
if ($currentCategory !== null && !$categoryNotFound) {
    $canonicalUrl .= '?category=' . rawurlencode((string) $currentCategory['slug']);
}

$chronicleHero = $posts[0] ?? null;
$chronicleEntries = array_slice($posts, 1);

// Pobierz wszystkie kategorie bloga
$blogCategories = getBlogCategories();

// Ustaw nagłówki HTTP
header("Cache-Control: no-cache, must-revalidate");
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

    <div class="chronicle-page-header">
        <p class="chronicle-date"><?= formatPolishChronicleDate('now') ?></p>
        <?php if ($categoryNotFound): ?>
            <h1>404</h1>
            <p>Przepraszamy, podana kategoria Kroniki Miasta nie została znaleziona.</p>
        <?php elseif ($currentCategory): ?>
            <h1><?= sanitize($currentCategory['name']) ?></h1>
            <p>Wybrane wpisy z Kroniki Miasta</p>
        <?php else: ?>
            <h1>Kronika miasta</h1>
            <p>Najnowsze sprawy, wydarzenia i historie Krosna Odrzańskiego.</p>
        <?php endif; ?>
    </div>

    <section class="community-post-entry" aria-label="Wpisy mieszkańców">
        <div>
            <strong>Twórz wspólną kronikę miasta</strong>
            <p>Prześlij swój artykuł do moderacji w jednej z kategorii bloga.</p>
        </div>
        <a href="<?= SITE_URL ?>/submit-post.php" data-open-submission="chronicle" class="community-post-entry__button">Dodaj wpis mieszkańca</a>
    </section>

    <nav class="sub-categories-nav" aria-label="Kategorie Kroniki">
        <div class="categories-container">
            <a href="<?= SITE_URL ?>/blog.php" class="category-item <?php if (!$currentCategory && !$categoryNotFound): ?>active<?php endif; ?>">
                <div class="category-icon-box">
                    <?= getCategorySvg('all') ?>
                </div>
                <div class="category-name"><span>Wszystkie</span></div>
            </a>

            <?php foreach ($blogCategories as $category): ?>
                <a href="<?= SITE_URL ?>/blog.php?category=<?= $category['slug'] ?>" class="category-item <?php if ($currentCategory && $currentCategory['id'] == $category['id']): ?>active<?php endif; ?>">
                    <div class="category-icon-box">
                        <?= getCategoryIconMarkup($category) ?>
                    </div>
                    <div class="category-name"><span><?= sanitize($category['name']) ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <section class="chronicle-feed">
        <?php if ($categoryNotFound): ?>
            <div class="no-results">
                <h2>404</h2>
                <p>Przepraszamy, podana kategoria Kroniki Miasta nie została znaleziona.</p>
                <a href="<?= SITE_URL ?>/blog.php" class="chronicle-cta">Wróć do wszystkich artykułów</a>
            </div>
        <?php elseif ($chronicleHero !== null): ?>
            <?php
            $heroShareUrl = getChroniclePostUrl($chronicleHero['slug']);
            $heroShareText = !empty($chronicleHero['meta_description'])
                ? $chronicleHero['meta_description']
                : createMetaDescription(!empty($chronicleHero['excerpt']) ? $chronicleHero['excerpt'] : $chronicleHero['content']);
            ?>
            <article class="chronicle-hero chronicle-hero--page">
                <?php if (!empty($chronicleHero['image'])): ?>
                    <a class="chronicle-hero__media" href="<?= getChroniclePostUrl($chronicleHero['slug']) ?>">
                        <img src="<?= getPublicUploadUrl($chronicleHero['image']) ?>" alt="<?= sanitize($chronicleHero['title']) ?>" loading="eager" decoding="async">
                    </a>
                <?php endif; ?>
                <div class="chronicle-hero__content">
                    <span class="chronicle-category">• <?= sanitize($chronicleHero['category_name']) ?></span>
                    <h2><a href="<?= getChroniclePostUrl($chronicleHero['slug']) ?>"><?= sanitize($chronicleHero['title']) ?></a></h2>
                    <p><?= sanitize(!empty($chronicleHero['excerpt']) ? $chronicleHero['excerpt'] : truncateText(strip_tags($chronicleHero['content']), 220)) ?> <a href="<?= getChroniclePostUrl($chronicleHero['slug']) ?>" class="chronicle-cta chronicle-cta--inline" aria-label="Czytaj dalej: <?= sanitize($chronicleHero['title']) ?>">Czytaj dalej</a></p>
                    <div class="chronicle-hero__actions chronicle-hero__actions--share">
                        <button type="button" class="card-share-button" data-share-url="<?= sanitize($heroShareUrl) ?>" data-share-title="<?= sanitize(!empty($chronicleHero['meta_title']) ? $chronicleHero['meta_title'] : $chronicleHero['title']) ?>" data-share-text="<?= sanitize($heroShareText) ?>" aria-label="Udostępnij wpis: <?= sanitize($chronicleHero['title']) ?>">
                            <?= getCategorySvg('share') ?><span>Udostępnij</span>
                        </button>
                    </div>
                </div>
            </article>
        <?php endif; ?>

        <?php if (!empty($chronicleEntries)): ?>
            <div class="chronicle-timeline">
                <?php foreach ($chronicleEntries as $post): ?>
                    <?php
                    $postShareUrl = getChroniclePostUrl($post['slug']);
                    $postShareText = !empty($post['meta_description'])
                        ? $post['meta_description']
                        : createMetaDescription(!empty($post['excerpt']) ? $post['excerpt'] : $post['content']);
                    ?>
                    <article class="chronicle-entry">
                        <div class="chronicle-entry__layout<?= !empty($post['image']) ? ' has-media' : '' ?>">
                            <?php if (!empty($post['image'])): ?>
                                <a class="chronicle-entry__media" href="<?= getChroniclePostUrl($post['slug']) ?>" tabindex="-1" aria-hidden="true">
                                    <img src="<?= getPublicUploadUrl($post['image']) ?>" alt="" loading="lazy" decoding="async">
                                </a>
                            <?php endif; ?>
                            <div class="chronicle-entry__content">
                                <div class="chronicle-entry__meta">
                                    <span class="chronicle-category">• <?= sanitize($post['category_name']) ?></span>
                                    <time datetime="<?= sanitize($post['created_at']) ?>"><?= formatDate($post['created_at']) ?></time>
                                </div>
                                <h3><a href="<?= getChroniclePostUrl($post['slug']) ?>"><?= sanitize($post['title']) ?></a></h3>
                                <p><?= sanitize(!empty($post['excerpt']) ? $post['excerpt'] : truncateText(strip_tags($post['content']), 160)) ?></p>
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
        <?php elseif ($chronicleHero === null): ?>
            <div class="no-results">
                <h3>Brak artykułów w tej kategorii</h3>
                <p>Przepraszamy, nie znaleziono żadnych artykułów.</p>
                <a href="<?= SITE_URL ?>/blog.php" class="chronicle-cta">Wróć do wszystkich artykułów</a>
            </div>
        <?php endif; ?>
    </section>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
