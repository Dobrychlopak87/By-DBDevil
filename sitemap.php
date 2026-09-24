<?php
// Dynamiczna mapa witryny dla publicznych stron 66600.PL.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/modules/ads/contract.php';
require_once __DIR__ . '/modules/chronicle/contract.php';

header('Content-Type: application/xml; charset=UTF-8');

function sitemapEscape($value) {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$urls = [
    SITE_URL . '/',
    SITE_URL . '/privacy-policy.php'
];
$urls = array_merge($urls, ads_sitemap_urls(SITE_URL), chronicle_sitemap_urls(SITE_URL));

$urls = array_values(array_unique($urls));

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
foreach ($urls as $url) {
    echo '  <url><loc>' . sitemapEscape($url) . '</loc></url>' . PHP_EOL;
}
echo '</urlset>' . PHP_EOL;
