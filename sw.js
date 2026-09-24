// Service Worker dla PWA serwisu 66600.PL.
// Ścieżki są względne wobec lokalizacji tego pliku, dzięki czemu serwis
// działa w katalogu głównym domeny oraz w podkatalogu bez zmian w kodzie.
// Wersja cache jest powiązana z ASSET_VERSION z includes/config.php.
const ASSET_VERSION = '20260923-stage7';
const CACHE_NAME = 'app-cache-' + ASSET_VERSION;
const BASE_URL = new URL('./', self.location).href;

const resolve = (path) => new URL(path, BASE_URL).href;

const ASSETS_TO_CACHE = [
    'assets/css/fonts.css?v=20260923-stage7',
    'assets/css/style.css?v=20260923-stage7',
    'assets/hero/krosno-intro.html?v=20260923-stage7',
    'assets/hero/krosno-intro-poster.svg?v=20260923-stage7',
    'assets/fonts/inter/inter-latin-ext-400-normal.woff2',
    'assets/fonts/inter/inter-latin-ext-400-normal.woff',
    'assets/fonts/inter/inter-latin-400-normal.woff2',
    'assets/fonts/inter/inter-latin-400-normal.woff',
    'assets/fonts/inter/inter-latin-ext-500-normal.woff2',
    'assets/fonts/inter/inter-latin-ext-500-normal.woff',
    'assets/fonts/inter/inter-latin-500-normal.woff2',
    'assets/fonts/inter/inter-latin-500-normal.woff',
    'assets/fonts/inter/inter-latin-ext-600-normal.woff2',
    'assets/fonts/inter/inter-latin-ext-600-normal.woff',
    'assets/fonts/inter/inter-latin-600-normal.woff2',
    'assets/fonts/inter/inter-latin-600-normal.woff',
    'assets/fonts/inter/inter-latin-ext-700-normal.woff2',
    'assets/fonts/inter/inter-latin-ext-700-normal.woff',
    'assets/fonts/inter/inter-latin-700-normal.woff2',
    'assets/fonts/inter/inter-latin-700-normal.woff',
    'assets/fonts/inter/inter-latin-ext-800-normal.woff2',
    'assets/fonts/inter/inter-latin-ext-800-normal.woff',
    'assets/fonts/inter/inter-latin-800-normal.woff2',
    'assets/fonts/inter/inter-latin-800-normal.woff',
    'assets/fonts/orbitron/orbitron-latin-900-normal.woff2',
    'assets/fonts/orbitron/orbitron-latin-900-normal.woff',
    'assets/js/main.js?v=20260923-stage7',
    'assets/js/share.js?v=20260923-stage7',
    'assets/js/poll.js?v=20260923-stage7',
    'assets/js/chronicle-interactions.js?v=20260923-stage7',
    'assets/js/submit-ad.js?v=20260923-stage7',
    'assets/js/contact.js?v=20260923-stage7',
    'assets/js/pulse.js?v=20260923-stage7',
    'assets/pulse/pulse-ticker.html?v=20260923-stage7',
    'assets/js/weather.js?v=20260923-stage7',
    'assets/js/pwa-install.js?v=20260923-stage7',
    'assets/images/herb-most.avif?v=20260923-stage7',
    'assets/images/icons/favicon.svg?v=20260923-stage7',
    'assets/images/icons/favicon.ico?v=20260923-stage7',
    'assets/images/icons/favicon-16x16.png?v=20260923-stage7',
    'assets/images/icons/favicon-32x32.png?v=20260923-stage7',
    'assets/images/icons/favicon-48x48.png?v=20260923-stage7',
    'assets/images/icons/icon.svg?v=20260923-stage7',
    'assets/images/icons/icon-72x72.png?v=20260923-stage7',
    'assets/images/icons/icon-96x96.png?v=20260923-stage7',
    'assets/images/icons/icon-128x128.png?v=20260923-stage7',
    'assets/images/icons/icon-144x144.png?v=20260923-stage7',
    'assets/images/icons/icon-152x152.png?v=20260923-stage7',
    'assets/images/icons/icon-192x192.png?v=20260923-stage7',
    'assets/images/icons/icon-384x384.png?v=20260923-stage7',
    'assets/images/icons/icon-512x512.png?v=20260923-stage7',
    'assets/images/icons/icon-maskable-192x192.png?v=20260923-stage7',
    'assets/images/icons/icon-maskable-512x512.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-57x57.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-60x60.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-72x72.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-76x76.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-120x120.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-152x152.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-167x167.png?v=20260923-stage7',
    'assets/images/icons/apple-touch-icon-180x180.png?v=20260923-stage7',
    'assets/images/icons/icon-monochrome.svg?v=20260923-stage7',
    'assets/images/icons/safari-pinned-tab.svg?v=20260923-stage7',
    'assets/images/icons/mstile-150x150.png?v=20260923-stage7',
    'manifest.json?v=20260923-stage7',
    'browserconfig.xml?v=20260923-stage7'
];

// Obszary prywatne, administracyjne i dynamiczne — nigdy nie są
// przechwytywane ani zapisywane do cache.
const NEVER_CACHE_PREFIXES = ['admin/', 'auth/', 'chatroom/', 'session/', '.private/', 'installer/', 'importer/', 'includes/', 'app/', 'config/', 'database/'];
const NEVER_CACHE_FILES = [
    'pulse-api.php', 'pulse-submit.php', 'calendar-api.php', 'poll-vote.php',
    'submit-ad.php', 'submit-post.php', 'contact.php', 'chronicle-interactions.php',
    'weather.php', 'sitemap.php', 'robots.php', 'login.php', 'logout.php'
];

const basePathname = () => new URL(BASE_URL).pathname;

const relativePath = (requestUrl) => {
    if (requestUrl.origin !== self.location.origin) {
        return null;
    }
    const base = basePathname();
    if (!requestUrl.pathname.startsWith(base)) {
        return null;
    }
    return requestUrl.pathname.slice(base.length);
};

// Instalacja - cache'owanie zasobów statycznych
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => Promise.all(
                ASSETS_TO_CACHE.map((assetPath) => fetch(resolve(assetPath), { cache: 'no-store' })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`Nie udało się pobrać zasobu: ${assetPath}`);
                        }
                        return cache.put(resolve(assetPath), response);
                    }))
            ))
            .then(() => self.skipWaiting())
    );
});

// Aktywacja - usuwanie starych cache'y (wersjonowanie przez ASSET_VERSION)
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                    return undefined;
                })
            ).then(() => self.clients.claim());
        })
    );
});

// Fetch - najpierw aktualna wersja z sieci, cache tylko jako tryb offline.
// POST nie jest nigdy przechwytywany ani cache'owany.
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);
    const relPath = relativePath(requestUrl);
    if (relPath === null) {
        return;
    }

    const isDynamicOrPrivate = NEVER_CACHE_PREFIXES.some((prefix) => relPath.startsWith(prefix))
        || NEVER_CACHE_FILES.includes(relPath);
    if (isDynamicOrPrivate) {
        const offlineFallback = relPath.startsWith('chatroom/api/')
            ? new Response('', { status: 503, statusText: 'Chat unavailable' })
            : relPath === 'weather.php'
                ? new Response('', { status: 503, statusText: 'Weather unavailable' })
                : new Response('', { status: 504, statusText: 'Offline' });
        event.respondWith(
            fetch(event.request, { cache: 'no-store' })
                .catch(() => offlineFallback)
        );
        return;
    }

    const isStaticAsset = relPath.startsWith('assets/')
        || relPath === 'manifest.json'
        || relPath === 'browserconfig.xml';

    if (isStaticAsset) {
        event.respondWith((async () => {
            try {
                const response = await fetch(event.request, { cache: 'no-store' });
                if (response.ok) {
                    event.waitUntil(
                        caches.open(CACHE_NAME)
                            .then((cache) => cache.put(event.request, response.clone()))
                    );
                }
                return response;
            } catch {
                const cachedResponse = await caches.match(event.request);
                return cachedResponse ?? new Response('', { status: 504, statusText: 'Offline' });
            }
        })());
        return;
    }

    // Dokumenty HTML i pozostałe żądania GET: zawsze sieć, bez zapisu do cache.
    event.respondWith(
        fetch(event.request, { cache: 'no-store' })
            .catch(() => new Response('', { status: 504, statusText: 'Offline' }))
    );
});

// Powiadomienie o nowej wersji
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Obsługa push notifications (na przyszłość)
self.addEventListener('push', (event) => {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: resolve('assets/images/icons/icon-192x192.png'),
        badge: resolve('assets/images/icons/icon-72x72.png'),
        data: {
            url: data.url || BASE_URL
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Kliknięcie powiadomienia
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.notification.data.url) {
        clients.openWindow(event.notification.data.url);
    }
});
