<?php
// Pojedynczy artykuł blogowy
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// Pobierz slug z URL
$slug = $_GET['slug'] ?? null;

if (empty($slug)) {
    redirect(SITE_URL . '/blog.php');
    exit();
}

// Pobierz artykuł po slug
$post = getBlogPostBySlug($slug);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    $pageTitle = '404 - Artykuł nie znaleziony';
} else {
    $pageTitle = !empty($post['meta_title']) ? $post['meta_title'] : $post['title'];
    $pageDescription = !empty($post['meta_description'])
        ? $post['meta_description']
        : createMetaDescription(!empty($post['excerpt']) ? $post['excerpt'] : $post['content']);
    $canonicalUrl = getChroniclePostUrl($post['slug']);
    $pageImage = getOpenGraphImageUrl($post['image']) ?? (SITE_URL . '/assets/images/hero/blog.avif');
    $pageImageAlt = $post['title'];
    $pageType = 'Article';
    $openGraphType = 'article';
    $pageSchema = [
        '@type' => 'Article',
        'headline' => $pageTitle,
        'image' => [$pageImage],
        'datePublished' => $post['created_at'],
        'dateModified' => $post['updated_at'] ?? $post['created_at']
    ];
    
    // Pobierz powiązane artykuły z tej samej kategorii
    $relatedPosts = getBlogPostsByCategory($post['category_slug'], 4);
    // Usuń obecny artykuł z powiązanych
    $relatedPosts = array_filter($relatedPosts, function($p) use ($post) {
        return $p['id'] != $post['id'];
    });
}

// Pobierz kategorie bloga
$blogCategories = getBlogCategories();

// Ustaw nagłówki HTTP
if ($post) {
    $interactionState = getChroniclePostInteractionState((int) $post['id']);
    $comments = getChronicleComments((int) $post['id']);
    $gallery = getBlogPostGallery((int) $post['id']);
}

$getImageDimensions = static function (?string $filename): array {
    $path = UPLOAD_PATH . basename((string) $filename);
    $details = $filename !== null && $filename !== '' ? @getimagesize($path) : false;
    return [
        (int) ($details[0] ?? 0),
        (int) ($details[1] ?? 0)
    ];
};

header('Cache-Control: no-cache, must-revalidate');
?>

<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

    <?php if (!$post): ?>
        <div class="page-header">
            <h1>404</h1>
            <p>Przepraszamy, artykuł nie został znaleziony.</p>
            <a href="<?= SITE_URL ?>/blog.php" class="ad-external-btn" style="margin-top: 1rem;">
                ← Wróć do bloga
            </a>
        </div>
    <?php else: ?>
        <nav class="breadcrumbs" aria-label="Okruszki">
            <a href="<?= SITE_URL ?>/index.php">Strona główna</a>
            <span aria-hidden="true">/</span>
            <a href="<?= SITE_URL ?>/blog.php">Kronika Miasta</a>
            <span aria-hidden="true">/</span>
            <a href="<?= SITE_URL ?>/blog.php?category=<?= rawurlencode((string) $post['category_slug']) ?>"><?= sanitize($post['category_name']) ?></a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= sanitize($post['title']) ?></span>
        </nav>
        <article class="single-post">
            <div class="ad-detail-view">
                <div class="ad-detail-image">
                    <?php if (!empty($post['image'])): ?>
                        <?php [$mainImageWidth, $mainImageHeight] = $getImageDimensions((string) $post['image']); ?>
                        <img src="<?= SITE_URL ?>/assets/uploads/<?= rawurlencode($post['image']) ?>" alt="<?= sanitize($post['title']) ?>"<?= $mainImageWidth > 0 && $mainImageHeight > 0 ? ' width="' . $mainImageWidth . '" height="' . $mainImageHeight . '"' : '' ?> fetchpriority="high" decoding="async">
                    <?php else: ?>
                        <div class="ad-image-placeholder">Brak zdjęcia</div>
                    <?php endif; ?>
                </div>

                <div class="ad-detail-content">
                    <div class="ad-detail-header">
                        <span class="ad-detail-category"><?= sanitize($post['category_name']) ?></span>
                        <?php if (($post['source_type'] ?? 'chronicle') === 'calendar'): ?>
                            <span class="ad-detail-category">Dodano z kalendarza</span>
                        <?php endif; ?>
                        <h1 class="ad-detail-title"><?= sanitize($post['title']) ?></h1>
                        <div class="ad-detail-location">
                            <span>📅 <?= formatDate($post['created_at'], 'd.m.Y') ?></span>
                        </div>
                        <?php if (!empty($post['author_signature'])): ?>
                            <p class="post-author-signature<?= (($post['author_source'] ?? 'community') === 'official') ? ' is-official' : '' ?>">
                                <?php if (($post['author_source'] ?? 'community') === 'official'): ?><span class="official-author-badge">Oficjalnie</span><?php endif; ?>
                                Podpis autora: <?= sanitize($post['author_signature']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="ad-detail-description">
                        <div class="post-body-content">
                            <?php if (preg_match('/<[a-z][^>]*>/i', (string) $post['content']) === 1): ?>
                                <?= $post['content'] ?>
                            <?php else: ?>
                                <?= nl2br(sanitize((string) $post['content']), false) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($gallery)): ?>
                        <section id="chronicle-gallery" class="ad-profile__gallery chronicle-gallery" aria-labelledby="chronicle-gallery-title">
                            <h2 id="chronicle-gallery-title">Galeria zdjęć</h2>
                            <div class="ad-profile__thumbnails">
                                <?php foreach ($gallery as $index => $galleryImage): ?>
                                    <?php $galleryUrl = SITE_URL . '/assets/uploads/' . rawurlencode($galleryImage['image']); ?>
                                    <?php [$galleryImageWidth, $galleryImageHeight] = $getImageDimensions((string) $galleryImage['image']); ?>
                                    <a class="ad-profile__thumbnail" style="display:block;text-decoration:none" href="<?= sanitize($galleryUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Otwórz zdjęcie <?= (int) ($index + 1) ?> w pełnym rozmiarze">
                                        <img src="<?= sanitize($galleryUrl) ?>" alt="<?= sanitize($galleryImage['description'] ?: 'Zdjęcie galerii wpisu') ?>"<?= $galleryImageWidth > 0 && $galleryImageHeight > 0 ? ' width="' . $galleryImageWidth . '" height="' . $galleryImageHeight . '"' : '' ?> loading="lazy" decoding="async">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <div class="content-share-row">
                        <button type="button" class="content-share-button"
                                data-share-url="<?= sanitize($canonicalUrl) ?>"
                                data-share-title="<?= sanitize($pageTitle) ?>"
                                data-share-text="<?= sanitize($pageDescription) ?>">
                            <?= getCategorySvg('share') ?>
                            <span>Udostępnij wpis</span>
                        </button>
                    </div>

                    <section class="chronicle-interactions" data-chronicle-interactions data-post-id="<?= (int) $post['id'] ?>" data-endpoint="<?= SITE_URL ?>/chronicle-interactions.php" aria-label="Oceny i komentarze wpisu">
                        <div class="chronicle-reactions" aria-label="Oceń ten wpis">
                            <button type="button" class="chronicle-reaction chronicle-reaction--up<?= (int) ($interactionState['viewer_vote'] ?? 0) === 1 ? ' is-selected' : '' ?>" data-chronicle-vote="1" aria-pressed="<?= (int) ($interactionState['viewer_vote'] ?? 0) === 1 ? 'true' : 'false' ?>" aria-label="Na plus <?= (int) $interactionState['positive_votes'] ?>">
                                <?= getChronicleInteractionSvg('up') ?><span>Na plus</span><strong data-positive-votes><?= (int) $interactionState['positive_votes'] ?></strong>
                            </button>
                            <button type="button" class="chronicle-reaction chronicle-reaction--down<?= (int) ($interactionState['viewer_vote'] ?? 0) === -1 ? ' is-selected' : '' ?>" data-chronicle-vote="-1" aria-pressed="<?= (int) ($interactionState['viewer_vote'] ?? 0) === -1 ? 'true' : 'false' ?>" aria-label="Na minus <?= (int) $interactionState['negative_votes'] ?>">
                                <?= getChronicleInteractionSvg('down') ?><span>Na minus</span><strong data-negative-votes><?= (int) $interactionState['negative_votes'] ?></strong>
                            </button>
                        </div>

                        <form class="chronicle-comment-form" data-chronicle-comment-form>
                            <div class="chronicle-comment-form__row">
                                <label class="sr-only" for="chronicle-comment-author-<?= (int) $post['id'] ?>">Podpis autora komentarza</label>
                                <input id="chronicle-comment-author-<?= (int) $post['id'] ?>" name="author_name" type="text" maxlength="80" autocomplete="nickname" placeholder="Twój podpis" required>
                            </div>
                            <div class="chronicle-comment-form__row">
                                <label class="sr-only" for="chronicle-comment-content-<?= (int) $post['id'] ?>">Treść komentarza</label>
                                <textarea id="chronicle-comment-content-<?= (int) $post['id'] ?>" name="content" rows="3" maxlength="1500" placeholder="Napisz komentarz" required></textarea>
                            </div>
                            <button type="submit" class="chronicle-comment-form__submit">Opublikuj</button>
                            <p class="chronicle-interactions__status" data-chronicle-status role="status" aria-live="polite"></p>
                        </form>

                        <div class="chronicle-comments" data-chronicle-comments role="region" aria-label="Komentarze">
                            <?php foreach ($comments as $comment): ?>
                                <article class="chronicle-comment" data-chronicle-comment-id="<?= (int) $comment['id'] ?>">
                                    <div class="chronicle-comment__meta"><strong><?= sanitize($comment['author_name']) ?></strong><time datetime="<?= sanitize($comment['created_at']) ?>"><?= sanitize($comment['created_label']) ?></time></div>
                                    <p class="chronicle-comment__content"><?= nl2br(sanitize($comment['content']), false) ?></p>
                                    <button type="button" class="chronicle-comment__like<?= !empty($comment['viewer_has_liked']) ? ' is-selected' : '' ?>" data-chronicle-comment-like aria-pressed="<?= !empty($comment['viewer_has_liked']) ? 'true' : 'false' ?>" aria-label="Polubienie <?= (int) $comment['likes_count'] ?>">
                                        <?= getChronicleInteractionSvg('like') ?><span>Polubienie</span><strong data-comment-likes><?= (int) $comment['likes_count'] ?></strong>
                                    </button>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="ad-detail-back">
                        <a href="<?= SITE_URL ?>/blog.php" class="back-link">
                            <?= getCategorySvg('back') ?> Wróć do bloga
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($relatedPosts)): ?>
                <section class="related-posts" style="margin-top: 3rem;">
                    <div class="section-title">
                        <h2>Powiązane artykuły</h2>
                    </div>
                    
                    <div class="blog-grid">
                        <?php foreach ($relatedPosts as $relatedPost): ?>
                            <article class="blog-card">
                                <?php if (!empty($relatedPost['image'])): ?>
                                    <?php [$relatedImageWidth, $relatedImageHeight] = $getImageDimensions((string) $relatedPost['image']); ?>
                                    <div class="blog-image-container">
                                        <img src="<?= SITE_URL ?>/assets/uploads/<?= rawurlencode($relatedPost['image']) ?>" alt="<?= sanitize($relatedPost['title']) ?>" class="blog-image"<?= $relatedImageWidth > 0 && $relatedImageHeight > 0 ? ' width="' . $relatedImageWidth . '" height="' . $relatedImageHeight . '"' : '' ?> loading="lazy" decoding="async">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="blog-content">
                                    <span class="blog-category"><?= sanitize($relatedPost['category_name']) ?></span>
                                    <h3 class="blog-title">
                                        <a href="<?= getChroniclePostUrl($relatedPost['slug']) ?>">
                                            <?= sanitize($relatedPost['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="blog-meta">
                                        <span>📅 <?= formatDate($relatedPost['created_at']) ?></span>
                                    </div>
                                    <?php
                                    $relatedShareUrl = getChroniclePostUrl($relatedPost['slug']);
                                    $relatedShareText = !empty($relatedPost['meta_description'])
                                        ? $relatedPost['meta_description']
                                        : createMetaDescription(!empty($relatedPost['excerpt']) ? $relatedPost['excerpt'] : $relatedPost['content']);
                                    ?>
                                    <div class="card-share-action">
                                        <button type="button" class="card-share-button"
                                                data-share-url="<?= sanitize($relatedShareUrl) ?>"
                                                data-share-title="<?= sanitize(!empty($relatedPost['meta_title']) ? $relatedPost['meta_title'] : $relatedPost['title']) ?>"
                                                data-share-text="<?= sanitize($relatedShareText) ?>"
                                                aria-label="Udostępnij wpis: <?= sanitize($relatedPost['title']) ?>">
                                            <?= getCategorySvg('share') ?>
                                            <span>Udostępnij</span>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>
    <?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
