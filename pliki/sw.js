// Service Worker dla PWA serwisu 66600.PL.
// UWAGA dotycząca instalacji w podkatalogu (np. public_html/home/):
// ścieżki w ASSETS_TO_CACHE zaczynają się od "/". Dla instalacji w podkatalogu
// trzeba je uzupełnić o ten prefiks (np. "/home/assets/...") albo przejść na
// ścieżki względne. Rejestracja SW w nagłówku PHP (includes/header.php) używa
// też "/sw.js" — dla podkatalogu zmień ją na "sw.js" ze scope "./".
const CACHE_NAME = 'app-cache-v2';
const ASSETS_TO_CACHE = [
    '/assets/css/fonts.css?v=20260904-chat-send-pwa1',
    '/assets/css/style.css?v=20260904-chat-send-pwa1',
    '/assets/hero/krosno-intro.html?v=20260904-chat-send-pwa1',
    '/assets/hero/krosno-intro-poster.svg?v=20260904-chat-send-pwa1',
    '/assets/fonts/inter/inter-latin-ext-400-normal.woff2',
    '/assets/fonts/inter/inter-latin-ext-400-normal.woff',
    '/assets/fonts/inter/inter-latin-400-normal.woff2',
    '/assets/fonts/inter/inter-latin-400-normal.woff',
    '/assets/fonts/inter/inter-latin-ext-500-normal.woff2',
    '/assets/fonts/inter/inter-latin-ext-500-normal.woff',
    '/assets/fonts/inter/inter-latin-500-normal.woff2',
    '/assets/fonts/inter/inter-latin-500-normal.woff',
    '/assets/fonts/inter/inter-latin-ext-600-normal.woff2',
    '/assets/fonts/inter/inter-latin-ext-600-normal.woff',
    '/assets/fonts/inter/inter-latin-600-normal.woff2',
    '/assets/fonts/inter/inter-latin-600-normal.woff',
    '/assets/fonts/inter/inter-latin-ext-700-normal.woff2',
    '/assets/fonts/inter/inter-latin-ext-700-normal.woff',
    '/assets/fonts/inter/inter-latin-700-normal.woff2',
    '/assets/fonts/inter/inter-latin-700-normal.woff',
    '/assets/fonts/inter/inter-latin-ext-800-normal.woff2',
    '/assets/fonts/inter/inter-latin-ext-800-normal.woff',
    '/assets/fonts/inter/inter-latin-800-normal.woff2',
    '/assets/fonts/inter/inter-latin-800-normal.woff',
    '/assets/fonts/orbitron/orbitron-latin-900-normal.woff2',
    '/assets/fonts/orbitron/orbitron-latin-900-normal.woff',
    '/assets/js/main.js?v=20260904-chat-send-pwa1',
    '/assets/js/share.js?v=20260904-chat-send-pwa1',
    '/assets/js/poll.js?v=20260904-chat-send-pwa1',
    '/assets/js/chronicle-interactions.js?v=20260924-gallery-lightbox1',
    '/assets/js/submit-ad.js?v=20260904-chat-send-pwa1',
    '/assets/js/contact.js?v=20260904-chat-send-pwa1',
    '/assets/js/pulse.js?v=20260904-chat-send-pwa1',
    '/assets/pulse/pulse-ticker.html?v=20260904-chat-send-pwa1',
    '/assets/js/weather.js?v=20260904-chat-send-pwa1',
    '/assets/js/pwa-install.js?v=20260904-chat-send-pwa1',
    '/assets/images/herb-most.avif?v=20260904-chat-send-pwa1',
    '/assets/images/icons/favicon.svg?v=20260910-pwa3',
    '/assets/images/icons/favicon.ico?v=20260910-pwa3',
    '/assets/images/icons/favicon-16x16.png?v=20260910-pwa3',
    '/assets/images/icons/favicon-32x32.png?v=20260910-pwa3',
    '/assets/images/icons/favicon-48x48.png?v=20260910-pwa3',
    '/assets/images/icons/icon.svg?v=20260910-pwa3',
    '/assets/images/icons/icon-72x72.png?v=20260910-pwa3',
    '/assets/images/icons/icon-96x96.png?v=20260910-pwa3',
    '/assets/images/icons/icon-128x128.png?v=20260910-pwa3',
    '/assets/images/icons/icon-144x144.png?v=20260910-pwa3',
    '/assets/images/icons/icon-152x152.png?v=20260910-pwa3',
    '/assets/images/icons/icon-192x192.png?v=20260910-pwa3',
    '/assets/images/icons/icon-384x384.png?v=20260910-pwa3',
    '/assets/images/icons/icon-512x512.png?v=20260910-pwa3',
    '/assets/images/icons/icon-maskable-192x192.png?v=20260910-pwa3',
    '/assets/images/icons/icon-maskable-512x512.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-57x57.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-60x60.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-72x72.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-76x76.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-120x120.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-152x152.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-167x167.png?v=20260910-pwa3',
    '/assets/images/icons/apple-touch-icon-180x180.png?v=20260910-pwa3',
    '/assets/images/icons/icon-monochrome.svg?v=20260910-pwa3',
    '/assets/images/icons/safari-pinned-tab.svg?v=20260910-pwa3',
    '/assets/images/icons/mstile-150x150.png?v=20260910-pwa3',
    '/manifest.json?v=20260904-chat-send-pwa1',
    '/browserconfig.xml?v=20260904-chat-send-pwa1'
];

// Instalacja - cache'owanie zasobów
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => Promise.all(
                ASSETS_TO_CACHE.map((assetUrl) => fetch(assetUrl, { cache: 'no-store' })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`Nie udało się pobrać zasobu: ${assetUrl}`);
                        }
                        return cache.put(assetUrl, response);
                    }))
            ))
            .then(() => self.skipWaiting())
    );
});

// Aktywacja - usuwanie starych cache'y
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

// Fetch - najpierw aktualna wersja z sieci, cache tylko jako tryb offline
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);
    // Gra w pokera działa w czasie rzeczywistym — zawsze bezpośrednio z sieci, bez pamięci podręcznej.
    if (requestUrl.origin === self.location.origin && (requestUrl.pathname === '/poker' || requestUrl.pathname.startsWith('/poker/'))) {
        return;
    }
    if (requestUrl.origin === self.location.origin && (requestUrl.pathname === '/auth/login.php' || requestUrl.pathname === '/auth/register.php' || requestUrl.pathname === '/auth/my.php')) {
        event.respondWith(fetch(event.request, { cache: 'no-store' }));
        return;
    }
    if (requestUrl.origin === self.location.origin && requestUrl.pathname === '/weather.php') {
        event.respondWith(
            fetch(event.request, { cache: 'no-store' })
                .catch(() => new Response('', { status: 503, statusText: 'Weather unavailable' }))
        );
        return;
    }

    if (requestUrl.origin === self.location.origin && requestUrl.pathname.startsWith('/chatroom/api/')) {
        event.respondWith(
            fetch(event.request, { cache: 'no-store' })
                .catch(() => new Response('', { status: 503, statusText: 'Chat unavailable' }))
        );
        return;
    }

    event.respondWith((async () => {
        try {
            const response = await fetch(event.request, { cache: 'no-store' });
            if (response.ok && requestUrl.origin === self.location.origin) {
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
        icon: '/assets/images/icons/icon-192x192.png',
        badge: '/assets/images/icons/icon-72x72.png',
        data: {
            url: data.url || '/'
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
