<?php
declare(strict_types=1);

/**
 * Public integration contract for the Chronicle module.
 * Existing repositories remain behind this adapter during the first migration.
 */
require_once dirname(__DIR__, 2) . '/includes/functions.php';

function chronicle_navigation(): array
{
    return [
        'categories' => getBlogCategories(),
    ];
}

function chronicle_homepage_cards(int $limit = 6): array
{
    return getLatestBlogPosts($limit);
}

function chronicle_sitemap_urls(string $siteUrl): array
{
    $urls = [$siteUrl . '/blog.php'];
    foreach (getBlogCategories() as $category) {
        $urls[] = $siteUrl . '/blog.php?category=' . rawurlencode((string) $category['slug']);
    }
    foreach (getAllBlogPosts() as $post) {
        $urls[] = getChroniclePostUrl((string) $post['slug']);
    }
    return array_values(array_unique($urls));
}
