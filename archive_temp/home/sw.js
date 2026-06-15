const CACHE_NAME = 'dbdevstudio-cache-v1';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      // Basic static skeleton caching
      return cache.addAll(['/']);
    })
  );
});

self.addEventListener('fetch', (event) => {
  // Stale-while-revalidate for network resilience
  event.respondWith(
    caches.match(event.request).then((response) => {
      const fetchPromise = fetch(event.request).then((networkResponse) => {
        // Skip caching non-GET or cross-origin completely
        if (event.request.method === 'GET' && networkResponse.ok && networkResponse.type === 'basic') {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseToCache));
        }
        return networkResponse;
      }).catch(() => {
        return response; // Return cache offline
      });
      return response || fetchPromise;
    })
  );
});
