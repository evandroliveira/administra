const CACHE_NAME = 'administrar-static-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll([
            '/favicon.svg',
            '/manifest.webmanifest',
        ])),
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => Promise.all(
            cacheNames
                .filter((cacheName) => cacheName !== CACHE_NAME)
                .map((cacheName) => caches.delete(cacheName)),
        )),
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || new URL(event.request.url).origin !== self.location.origin) {
        return;
    }

    const destination = event.request.destination;

    if (!['script', 'style', 'image', 'font'].includes(destination)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cachedResponse) => cachedResponse || fetch(event.request).then((response) => {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
            return response;
        })),
    );
});