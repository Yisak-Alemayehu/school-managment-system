/**
 * Portal Service Worker — network-first with offline fallback.
 * Scope: /portal/
 */

const CACHE = 'portal-v1';

// Pages/assets to pre-cache on install
const PRECACHE = [
    '/portal/login',
    '/img/Logo.png',
];

// ── Install ───────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll(PRECACHE))
    );
    self.skipWaiting();
});

// ── Activate: delete old caches ───────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// ── Fetch: network-first, fall back to cache ──────────────────────────────────
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Only handle same-origin portal requests (and static assets)
    const isPortal  = url.pathname.startsWith('/portal');
    const isAsset   = url.pathname.startsWith('/img') || url.pathname.startsWith('/public/assets');
    if (!isPortal && !isAsset) return;

    // Skip non-GET or requests with credentials payloads (form POSTs)
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Cache successful responses
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() =>
                // Try cache, then fall back to login page
                caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    if (isPortal) return caches.match('/portal/login');
                    return new Response('Offline', { status: 503 });
                })
            )
    );
});
