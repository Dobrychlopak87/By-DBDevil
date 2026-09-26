<?php
declare(strict_types=1);

/**
 * Public integration contract for the Ads module.
 * The first migration keeps the existing repositories as a compatibility layer;
 * callers depend on this contract rather than on Ads table functions.
 */
require_once dirname(__DIR__, 2) . '/includes/functions.php';

function ads_navigation(): array
{
    return [
        'categories' => getAdCategories(),
        'tree' => getAdCategoryTree(),
    ];
}

function ads_homepage_cards(int $limit = 6): array
{
    return getHomepageAds($limit);
}

function ads_sitemap_urls(string $siteUrl): array
{
    $urls = [$siteUrl . '/category.php'];
    foreach (getAdCategories() as $category) {
        $urls[] = $siteUrl . '/category.php?category=' . rawurlencode((string) $category['slug']);
    }
    foreach (getAllAds() as $ad) {
        $urls[] = $siteUrl . '/ad.php?id=' . (int) $ad['id'];
    }
    return array_values(array_unique($urls));
}
