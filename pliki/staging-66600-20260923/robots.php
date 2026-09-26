<?php
/**
 * Dynamiczny robots.txt.
 * URL do mapy witryny jest budowany z bieżącego żądania, dzięki czemu instalacja
 * działa poprawnie na dowolnej domenie. Stałe reguły Disallow pozostają
 * niezależne od hosta.
 */
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$sitemapUrl = SITE_URL . '/sitemap.xml';

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /database.sql\n";
echo "Disallow: /chatroom/includes/\n";
echo "Disallow: /chatroom/api/\n";
echo "Disallow: /chatroom/cron/\n";
echo "Disallow: /cron/\n";
echo "Disallow: /.private/\n";
echo "\n";
echo "Sitemap: " . $sitemapUrl . "\n";
