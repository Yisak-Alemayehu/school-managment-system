/**
 * Portal Service Worker — network-first with offline fallback.
 * Scope: /portal/
 */

const CACHE = 'portal-v3';

// Static assets to pre-cache on install
const PRECACHE = [
    '/portal/login',
    '/portal/offline',
    '/public/assets/css/app.css',
    '/public/assets/js/app.js',
    '/img/Logo.png',
    '/portal-manifest.webmanifest',
];

// ── Install ───────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE)
            .then(cache => cache.addAll(PRECACHE.map(url => new Request(url, { cache: 'reload' }))))
            .catch(() => caches.open(CACHE).then(cache => cache.add('/portal/login')))
    );
    self.skipWaiting();
});

// ── Activate: delete stale caches ────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// ── Fetch ─────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) return;

    // Only handle /portal/* and shared static assets
    const isPortal = url.pathname.startsWith('/portal');
    const isAsset  = url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?|ttf)$/);
    if (!isPortal && !isAsset) return;

    // Skip non-GET and form POSTs
    if (event.request.method !== 'GET') return;

    if (isAsset) {
        // Assets: cache-first
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                }).catch(() => new Response('', { status: 503 }));
            })
        );
        return;
    }

    // Portal pages: network-first, fall back to cache then offline page
    event.respondWith(
        fetch(event.request)
            .then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() =>
                caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    return caches.match('/portal/offline');
                })
            )
    );
});
