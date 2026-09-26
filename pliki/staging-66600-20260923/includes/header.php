<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__) . '/modules/ads/contract.php';
require_once dirname(__DIR__) . '/modules/chronicle/contract.php';
require_once dirname(__DIR__) . '/auth/lib.php';

$siteSettings = getSiteSettings();
$adsNavigation = ads_navigation();
$adCategories = $adsNavigation['categories'];
$adCategoryTree = $adsNavigation['tree'];
$chronicleNavigation = chronicle_navigation();
$blogCategories = $chronicleNavigation['categories'];
$publicMenuItems = getPublicMenuItems();
$authHeaderLoggedIn = !empty($_SESSION['auth_user_id']) && !empty($_SESSION['auth_username']);
$authHeaderUsername = (string) ($_SESSION['auth_username'] ?? '');
$adminHeaderLoggedIn = function_exists('isAdminLoggedIn') && isAdminLoggedIn();
$adminHeaderUsername = (string) ($_SESSION['username'] ?? '');

$isHomePage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php';
$isAdminPage = strpos('/' . ltrim($_SERVER['REQUEST_URI'] ?? '', '/'), '/admin/') !== false;
$siteVisitCount = $isAdminPage ? null : recordAndGetSiteVisitCount();

$defaultPageTitle = 'Krosno Odrzańskie – lokalne ogłoszenia i informacje';
$pageTitle = $pageTitle ?? $defaultPageTitle;
$pageDescription = $pageDescription ?? $siteSettings['description'];
$canonicalUrl = $canonicalUrl ?? (SITE_URL . '/');
$pageImage = $pageImage ?? (SITE_URL . '/assets/images/herb-most.avif?v=' . ASSET_VERSION);
$pageImageAlt = $pageImageAlt ?? $pageTitle;
$pageImageType = $pageImageType ?? (str_ends_with(strtolower((string) parse_url($pageImage, PHP_URL_PATH)), '.jpg') || str_ends_with(strtolower((string) parse_url($pageImage, PHP_URL_PATH)), '.jpeg') ? 'image/jpeg' : null);
$pageType = $pageType ?? 'WebPage';
$openGraphType = $openGraphType ?? ($pageType === 'Article' ? 'article' : 'website');
$pageRobots = $pageRobots ?? ($isAdminPage ? 'noindex, nofollow' : 'index, follow, max-image-preview:large');
$pageTitle = trim(preg_replace('/\s+/u', ' ', strip_tags($pageTitle)));
$pageDescription = trim(preg_replace('/\s+/u', ' ', strip_tags($pageDescription)));

$localPlace = [
    '@type' => 'City',
    'name' => 'Krosno Odrzańskie',
    'address' => [
        '@type' => 'PostalAddress',
        'postalCode' => '66-600',
        'addressCountry' => 'PL'
    ]
];

$websiteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => SITE_URL . '/#website',
    'url' => SITE_URL . '/',
    'name' => '66600.PL',
    'description' => 'Lokalny serwis ogłoszeniowy i informacyjny dla Krosna Odrzańskiego.',
    'inLanguage' => 'pl-PL',
    'areaServed' => $localPlace
];

$defaultPageSchema = [
    '@context' => 'https://schema.org',
    '@type' => $pageType,
    '@id' => $canonicalUrl . '#webpage',
    'url' => $canonicalUrl,
    'name' => $pageTitle,
    'description' => $pageDescription,
    'inLanguage' => 'pl-PL',
    'isPartOf' => ['@id' => SITE_URL . '/#website'],
    'about' => $localPlace
];
$pageSchema = array_replace($defaultPageSchema, $pageSchema ?? []);
$jsonLdOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?= sanitize($pageDescription) ?>">
    <?php if (!$isAdminPage): ?>
        <meta name="robots" content="<?= sanitize($pageRobots) ?>">
        <link rel="canonical" href="<?= sanitize($canonicalUrl) ?>">
        <meta property="og:locale" content="pl_PL">
        <meta property="og:type" content="<?= sanitize($openGraphType) ?>">
        <meta property="og:site_name" content="66600.PL">
        <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
        <meta property="og:description" content="<?= sanitize($pageDescription) ?>">
        <meta property="og:url" content="<?= sanitize($canonicalUrl) ?>">
        <meta property="og:image" content="<?= sanitize($pageImage) ?>">
        <meta property="og:image:secure_url" content="<?= sanitize($pageImage) ?>">
        <?php if ($pageImageType !== null): ?><meta property="og:image:type" content="<?= sanitize($pageImageType) ?>"><?php endif; ?>
        <meta property="og:image:alt" content="<?= sanitize($pageImageAlt) ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?= sanitize($pageTitle) ?>">
        <meta name="twitter:description" content="<?= sanitize($pageDescription) ?>">
        <meta name="twitter:image" content="<?= sanitize($pageImage) ?>">
        <meta name="twitter:image:alt" content="<?= sanitize($pageImageAlt) ?>">
        <script type="application/ld+json"><?= json_encode($websiteSchema, $jsonLdOptions) ?></script>
        <script type="application/ld+json"><?= json_encode($pageSchema, $jsonLdOptions) ?></script>
    <?php endif; ?>

    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/images/icons/favicon.svg?v=<?= ASSET_VERSION ?>">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/icons/favicon-32x32.png?v=<?= ASSET_VERSION ?>" sizes="32x32">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/icons/favicon-16x16.png?v=<?= ASSET_VERSION ?>" sizes="16x16">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/icons/favicon-48x48.png?v=<?= ASSET_VERSION ?>" sizes="48x48">
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/images/icons/favicon.ico?v=<?= ASSET_VERSION ?>">
    <link rel="mask-icon" href="<?= SITE_URL ?>/assets/images/icons/safari-pinned-tab.svg?v=<?= ASSET_VERSION ?>" color="#1A232D">
    <meta name="msapplication-config" content="<?= SITE_URL ?>/browserconfig.xml?v=<?= ASSET_VERSION ?>">
    <meta name="theme-color" content="#1E2832">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="66600.PL">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/fonts.css?v=<?= ASSET_VERSION ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= ASSET_VERSION ?>">
    <?php if (!$isAdminPage): ?>
        <script src="<?= SITE_URL ?>/assets/js/weather.js?v=<?= ASSET_VERSION ?>" defer></script>
        <script src="<?= SITE_URL ?>/assets/js/pwa-install.js?v=<?= ASSET_VERSION ?>" defer></script>
    <?php endif; ?>
    <?php if ($isAdminPage): ?>
        <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css?v=<?= ASSET_VERSION ?>">
    <?php endif; ?>
    <link rel="manifest" href="<?= SITE_URL ?>/manifest.json?v=<?= ASSET_VERSION ?>">
    <link rel="apple-touch-icon" sizes="120x120" href="<?= SITE_URL ?>/assets/images/icons/apple-touch-icon-120x120.png?v=<?= ASSET_VERSION ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= SITE_URL ?>/assets/images/icons/apple-touch-icon-152x152.png?v=<?= ASSET_VERSION ?>">
    <link rel="apple-touch-icon" sizes="167x167" href="<?= SITE_URL ?>/assets/images/icons/apple-touch-icon-167x167.png?v=<?= ASSET_VERSION ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/assets/images/icons/apple-touch-icon-180x180.png?v=<?= ASSET_VERSION ?>">
    <title><?= sanitize($pageTitle) ?> | 66600.PL</title>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const swUrl = '<?= rtrim(SITE_URL, '/') ?>/sw.js';
                const swScope = '<?= rtrim(SITE_URL, '/') ?>/';
                navigator.serviceWorker.register(swUrl, { scope: swScope, updateViaCache: 'none' })
                    .then((registration) => {
                        const checkForUpdate = () => registration.update().catch(() => undefined);
                        checkForUpdate();
                        window.setInterval(checkForUpdate, 300000);
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            if (!newWorker) {
                                return;
                            }
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                }
                            });
                        });
                    })
                    .catch(() => undefined);
            });
        }
    </script>
</head>
<body class="page-is-loading<?= !empty($bodyClass) ? ' ' . sanitize($bodyClass) : '' ?>">
    <?php if (!$isAdminPage): ?>
        <a class="skip-link" href="#main-content">Przejdź do treści głównej</a>
        <aside class="pwa-install-prompt" id="pwa-install-prompt" hidden aria-label="Instalacja aplikacji 66600.PL">
            <img class="pwa-install-prompt__icon" src="<?= SITE_URL ?>/assets/images/icons/icon-72x72.png?v=<?= ASSET_VERSION ?>" alt="" width="54" height="54">
            <div class="pwa-install-prompt__content">
                <strong>Zainstaluj 66600.PL</strong>
                <span>Dodaj serwis do ekranu głównego, aby szybciej wracać do ogłoszeń i Kroniki.</span>
            </div>
            <button type="button" id="pwa-install-button">Zainstaluj</button>
            <button type="button" class="pwa-install-prompt__dismiss" id="pwa-install-dismiss" aria-label="Zamknij powiadomienie instalacji">×</button>
        </aside>
        <div class="page-skeleton" id="page-skeleton" role="status" aria-live="polite" aria-label="Ładowanie strony">
            <div class="page-skeleton__inner">
                <div class="page-skeleton__topbar">
                    <span class="skeleton-line skeleton-line--brand"></span>
                    <span class="skeleton-pill"></span>
                </div>
                <div class="skeleton-line skeleton-line--title"></div>
                <div class="skeleton-media skeleton-media--hero"></div>
                <div class="skeleton-cards" aria-hidden="true">
                    <div class="skeleton-card"><span class="skeleton-media"></span><span class="skeleton-line"></span><span class="skeleton-line skeleton-line--short"></span></div>
                    <div class="skeleton-card"><span class="skeleton-media"></span><span class="skeleton-line"></span><span class="skeleton-line skeleton-line--short"></span></div>
                    <div class="skeleton-card"><span class="skeleton-media"></span><span class="skeleton-line"></span><span class="skeleton-line skeleton-line--short"></span></div>
                </div>
                <p class="page-skeleton__label">Ładowanie strony…</p>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!$isAdminPage): ?>
    <div id="hamburger-menu" class="hamburger-menu" aria-hidden="true" hidden>
        <div class="hamburger-menu-header">
            <p class="hamburger-menu-title">Menu</p>
            <button type="button" class="hamburger-menu-close" id="close-hamburger" aria-label="Zamknij menu">×</button>
        </div>
        <nav class="hamburger-menu-nav" aria-label="Główne menu serwisu">
            <ul class="public-menu-list">
                <li class="public-menu-item"><a href="<?= SITE_URL ?>/calendar.php"<?= currentPageAttribute(SITE_URL . '/calendar.php') ?>>Kalendarz</a></li>
                <?php foreach ($publicMenuItems as $menuItem): ?>
                    <?php if ($menuItem['menu_key'] === 'ads'): ?>
                        <li class="public-menu-item public-menu-item--group">
                            <button type="button" class="public-menu-trigger" aria-expanded="false" aria-controls="menu-ads-categories">
                                <span><?= sanitize($menuItem['label']) ?></span><span class="public-menu-chevron" aria-hidden="true">›</span>
                            </button>
                            <div class="public-menu-submenu" id="menu-ads-categories" hidden>
                                <a href="<?= SITE_URL ?>/category.php"<?= currentPageAttribute(SITE_URL . '/category.php') ?>>Wszystkie ogłoszenia</a>
                                <?php foreach ($adCategoryTree as $category): ?>
                                    <?php if (!empty($category['children'])): ?>
                                        <div class="public-menu-category-group">
                                            <div class="public-menu-category-row">
                                                <a href="<?= SITE_URL ?>/category.php?category=<?= rawurlencode($category['slug']) ?>"><?= sanitize($category['name']) ?></a>
                                                <button type="button" class="public-menu-nested-trigger" aria-expanded="false" aria-controls="menu-ad-children-<?= (int) $category['id'] ?>" aria-label="Rozwiń podkategorie: <?= sanitize($category['name']) ?>">›</button>
                                            </div>
                                            <div class="public-menu-submenu public-menu-submenu--nested" id="menu-ad-children-<?= (int) $category['id'] ?>" hidden>
                                                <?php foreach ($category['children'] as $child): ?>
                                                    <a href="<?= SITE_URL ?>/category.php?category=<?= rawurlencode($child['slug']) ?>"><?= sanitize($child['name']) ?></a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?= SITE_URL ?>/category.php?category=<?= rawurlencode($category['slug']) ?>"><?= sanitize($category['name']) ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php elseif ($menuItem['menu_key'] === 'blog'): ?>
                        <li class="public-menu-item public-menu-item--group">
                            <button type="button" class="public-menu-trigger" aria-expanded="false" aria-controls="menu-blog-categories">
                                <span><?= sanitize($menuItem['label']) ?></span><span class="public-menu-chevron" aria-hidden="true">›</span>
                            </button>
                            <div class="public-menu-submenu" id="menu-blog-categories" hidden>
                                <a href="<?= SITE_URL ?>/blog.php"<?= currentPageAttribute(SITE_URL . '/blog.php') ?>>Wszystkie wpisy</a>
                                <?php foreach ($blogCategories as $category): ?>
                                    <a href="<?= SITE_URL ?>/blog.php?category=<?= rawurlencode($category['slug']) ?>"><?= sanitize($category['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php elseif ($menuItem['menu_key'] === 'chatroom'): ?>
                        <li class="public-menu-item"><button type="button" class="public-menu-action" data-open-chatroom><?= sanitize($menuItem['label']) ?></button></li>
                    <?php elseif ($menuItem['menu_key'] === 'pulse'): ?>
                        <li class="public-menu-item"><a href="<?= SITE_URL ?>/pulse.php"<?= currentPageAttribute(SITE_URL . '/pulse.php') ?>><?= sanitize($menuItem['label']) ?></a></li>
                    <?php elseif ($menuItem['menu_key'] === 'about'): ?>
                        <li class="public-menu-item public-menu-item--about"><a href="<?= SITE_URL ?>/about.php"<?= currentPageAttribute(SITE_URL . '/about.php') ?>><?= sanitize($menuItem['label']) ?></a></li>
                    <?php elseif ($menuItem['menu_key'] === 'cooperation'): ?>
                        <li class="public-menu-item"><button type="button" class="public-menu-action" data-open-contact data-contact-title="Współpraca z 66600.pl" data-contact-description="Napisz, jeśli chcesz współtworzyć lokalny serwis." data-contact-subject="Współpraca z 66600.pl"><?= sanitize($menuItem['label']) ?></button></li>
                    <?php elseif ($menuItem['menu_key'] === 'report'): ?>
                        <li class="public-menu-item"><button type="button" class="public-menu-action" data-open-contact data-contact-title="Zgłoś nadużycie" data-contact-description="Opisz problem. Każde zgłoszenie jest sprawdzane przez administrację." data-contact-subject="Zgłoszenie nadużycia"><?= sanitize($menuItem['label']) ?></button></li>
                    <?php else: ?>
                        <li class="public-menu-item"><span class="public-menu-static"><?= sanitize($menuItem['label']) ?></span></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li class="public-menu-item public-menu-item--auth">
                    <?php if ($adminHeaderLoggedIn): ?>
                        <a href="<?= SITE_URL ?>/admin/dashboard.php">Panel administracyjny <small>(<?= sanitize($adminHeaderUsername) ?>)</small></a>
                        <a href="<?= SITE_URL ?>/admin/logout.php">Wyloguj</a>
                    <?php elseif ($authHeaderLoggedIn): ?>
                        <a href="<?= SITE_URL ?>/auth/my.php">Moje zgłoszenia <small>(<?= sanitize($authHeaderUsername) ?>)</small></a>
                        <form method="post" action="<?= SITE_URL ?>/auth/logout.php">
                            <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
                            <button type="submit" class="public-menu-action">Wyloguj</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/auth/login.php">Zaloguj się</a>
                        <a href="<?= SITE_URL ?>/auth/register.php">Załóż konto</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
        <?php if (!$isAdminPage): ?>
            <section class="hamburger-weather" id="hamburger-weather" hidden aria-label="Aktualna pogoda w Krośnie Odrzańskim">
                <strong id="menu-weather-city"></strong>
                <span id="menu-weather-condition"></span>
                <span id="menu-weather-temperature"></span>
                <span id="menu-weather-wind"></span>
            </section>
            <div class="hamburger-visit-counter" aria-label="Licznik odwiedzin 66600.pl">
                <strong><?= number_format((int) $siteVisitCount, 0, ',', ' ') ?></strong>
                <span>Licznik odwiedzin 66600.pl</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$isAdminPage): ?>
        <section class="chatroom-overlay" id="chatroom-overlay" role="dialog" aria-modal="true" aria-labelledby="chatroom-overlay-title" hidden>
            <div class="chatroom-overlay__dialog">
                <div class="chatroom-overlay__header">
                    <h2 id="chatroom-overlay-title">Chatroom</h2>
                    <button type="button" class="chatroom-overlay__close" data-close-chatroom aria-label="Zamknij chatroom">×</button>
                </div>
                <iframe class="chatroom-overlay__frame" id="chatroom-frame" data-src="<?= SITE_URL ?>/chatroom/" title="Chatroom 66600.pl" loading="lazy"></iframe>
            </div>
        </section>
    <?php endif; ?>

    <header class="header">
        <div class="header-container">
            <div class="logo"><a href="<?= SITE_URL !== '' ? SITE_URL : '/' ?>"><span class="logo__text">66600.PL</span></a></div>
            <?php if (!$isAdminPage): ?>
                <div class="header-tools">
                    <button class="header-chat-button" id="header-chat-button" type="button" data-open-chatroom aria-label="Otwórz Chatroom">
                        <svg viewBox="0 0 28 24" aria-hidden="true" focusable="false"><rect x="2.5" y="3.5" width="23" height="17" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m3.8 5.2 10.2 7.4 10.2-7.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>
                        <span class="header-chat-button__badge" id="header-chat-unread" hidden aria-live="polite"></span>
                    </button>
                    <span class="header-weather" id="header-weather" hidden aria-label="Aktualna pogoda w Krośnie Odrzańskim"><span id="header-weather-summary"></span></span>
                </div>
            <?php endif; ?>
            <button class="hamburger-btn" id="hamburger-btn" type="button" aria-label="Otwórz menu" aria-controls="hamburger-menu" aria-expanded="false">
                <div class="hamburger-lines"><span></span><span></span><span></span></div>
            </button>
        </div>
    </header>
    <?php endif; ?>

    <?php
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    $activeAdCategorySlug = $currentPage === 'category.php' ? (string) ($_GET['category'] ?? '') : '';
    ?>
    <?php if (!$isAdminPage && $isHomePage): ?>
        <section class="hero-section">
            <div class="hero-container">
                <div class="hero-title-row">
                    <h1 class="hero-title"><?= sanitize($pageTitle) ?></h1>
                    <p class="home-current-date chronicle-date"><?= formatPolishChronicleDate('now') ?></p>
                </div>
                <div class="hero-graphic">
                    <iframe
                        class="hero-graphic__animation"
                        data-hero-animation-src="<?= SITE_URL ?>/assets/hero/krosno-intro.html?v=<?= ASSET_VERSION ?>"
                        title="Animacja przedstawiająca 66600.PL i Krosno Odrzańskie"
                        sandbox="allow-scripts allow-top-navigation-by-user-activation"
                        loading="eager"
                        referrerpolicy="no-referrer"></iframe>
                    <noscript>
                        <style>#page-skeleton { display: none !important; } .hero-graphic { background: #08131d url('<?= SITE_URL ?>/assets/hero/krosno-intro-poster.svg?v=<?= ASSET_VERSION ?>') center / cover no-repeat; }</style>
                        <img class="hero-graphic__fallback" src="<?= SITE_URL ?>/assets/hero/krosno-intro-poster.svg?v=<?= ASSET_VERSION ?>" alt="66600.PL — Jesteś u siebie">
                    </noscript>
                </div>
            </div>
        </section>
        <script>
            (() => {
                const animation = document.querySelector('.hero-graphic__animation[data-hero-animation-src]');
                if (!animation) return;

                const darkPreference = window.matchMedia('(prefers-color-scheme: dark)');
                const revealAnimation = () => window.requestAnimationFrame(() => animation.classList.add('is-ready'));
                animation.addEventListener('load', revealAnimation);

                const applyAnimationTheme = () => {
                    const theme = document.body.classList.contains('dark-mode') || darkPreference.matches ? 'dark' : 'light';
                    const sector = document.body.dataset.homeMode === 'blog' ? 'blog' : 'ads';
                    const source = new URL(animation.dataset.heroAnimationSrc, window.location.origin);
                    source.searchParams.set('theme', theme);
                    source.searchParams.set('sector', sector);
                    source.searchParams.set('motion', 'full');
                    if (animation.src !== source.href) {
                        animation.classList.remove('is-ready');
                        animation.src = source.href;
                    }
                };

                window.addEventListener('message', (event) => {
                    if (event.source !== animation.contentWindow || !event.data || event.data.type !== '66600-hero-action') return;
                    const action = event.data.action;
                    if (action === 'ads' || action === 'blog') {
                        document.querySelector(`[data-home-sector="${action}"]`)?.click();
                        return;
                    }
                    if (action === 'pulse') {
                        window.location.assign('<?= SITE_URL ?>/pulse.php');
                        return;
                    }
                    if (action === 'chat') {
                        document.querySelector('#header-chat-button, [data-open-chatroom]')?.click();
                        return;
                    }
                    if (action === 'poll') {
                        document.querySelector('[data-home-sector="blog"]')?.click();
                        window.setTimeout(() => document.getElementById('active-poll')?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 80);
                    }
                });

                applyAnimationTheme();
                new MutationObserver(applyAnimationTheme).observe(document.body, { attributes: true, attributeFilter: ['class', 'data-home-mode'] });
                darkPreference.addEventListener('change', applyAnimationTheme);
                document.addEventListener('home-sector-change', applyAnimationTheme);
            })();
        </script>
    <?php endif; ?>

    <?php if (!$isAdminPage && $isHomePage): ?>
        <nav class="categories-nav home-categories-nav" data-home-category-nav aria-label="Kategorie bieżącego sektora">
            <button type="button" class="home-categories-toggle" id="home-categories-toggle" aria-expanded="false" aria-controls="home-categories-drawer">
                <span data-home-categories-toggle-label>Rozwiń, aby zobaczyć kategorie</span>
                <span class="home-categories-toggle__icon" aria-hidden="true">›</span>
            </button>
            <div class="home-categories-drawer" id="home-categories-drawer" hidden>
                <div class="categories-container" data-home-category-panel="ads">
                    <?php foreach ($adCategoryTree as $category): ?>
                        <a href="<?= SITE_URL ?>/category.php?category=<?= rawurlencode($category['slug']) ?>" class="category-item">
                            <div class="category-icon-box"><?= getCategoryIconMarkup($category) ?></div>
                            <div class="category-name"><span><?= sanitize($category['name']) ?></span></div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="categories-container" data-home-category-panel="blog" hidden>
                    <a href="<?= SITE_URL ?>/blog.php" class="category-item active">
                        <div class="category-icon-box"><?= getCategorySvg('all') ?></div>
                        <div class="category-name"><span>Wszystkie</span></div>
                    </a>
                    <?php foreach ($blogCategories as $category): ?>
                        <a href="<?= SITE_URL ?>/blog.php?category=<?= rawurlencode($category['slug']) ?>" class="category-item">
                            <div class="category-icon-box"><?= getCategoryIconMarkup($category) ?></div>
                            <div class="category-name"><span><?= sanitize($category['name']) ?></span></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>
    <?php elseif (!$isAdminPage && $currentPage === 'category.php'): ?>
        <nav class="categories-nav">
            <div class="categories-container">
                <?php foreach ($adCategoryTree as $category): ?>
                    <a href="<?= SITE_URL ?>/category.php?category=<?= rawurlencode($category['slug']) ?>" class="category-item<?= $activeAdCategorySlug === $category['slug'] ? ' active' : '' ?>">
                        <div class="category-icon-box"><?= getCategoryIconMarkup($category) ?></div>
                        <div class="category-name"><span><?= sanitize($category['name']) ?></span></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>

    <main class="main-content" id="main-content" tabindex="-1">
