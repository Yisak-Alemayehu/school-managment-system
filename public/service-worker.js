// Urjiberi School ERP — Service Worker
const CACHE_VERSION = 'urjiberi-v1.0.0';
const APP_SHELL_CACHE = CACHE_VERSION + '-shell';
const DATA_CACHE = CACHE_VERSION + '-data';

// App shell files to cache
const APP_SHELL_FILES = [
    '/',
    '/offline.html',
    '/assets/css/app.css',
    '/assets/js/app.js',
    '/assets/icons/icon.php?s=192',
    '/assets/icons/icon.php?s=512',
    '/manifest.webmanifest'
];

// Install: cache app shell
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(APP_SHELL_CACHE)
            .then(function(cache) {
                console.log('[SW] Caching app shell');
                return cache.addAll(APP_SHELL_FILES);
            })
            .then(function() {
                return self.skipWaiting();
            })
    );
});

// Activate: clean old caches
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys()
            .then(function(cacheNames) {
                return Promise.all(
                    cacheNames
                        .filter(function(name) {
                            return name.startsWith('urjiberi-') && 
                                   name !== APP_SHELL_CACHE && 
                                   name !== DATA_CACHE;
                        })
                        .map(function(name) {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(function() {
                return self.clients.claim();
            })
    );
});

// Fetch: network-first for navigations, cache-first for assets
self.addEventListener('fetch', function(event) {
    var request = event.request;

    // Skip non-GET
    if (request.method !== 'GET') return;

    // Skip API/webhook requests
    if (request.url.includes('/payments/webhook')) return;

    // Navigation requests: network-first
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(function(response) {
                    // Cache the page
                    var clone = response.clone();
                    caches.open(DATA_CACHE).then(function(cache) {
                        cache.put(request, clone);
                    });
                    return response;
                })
                .catch(function() {
                    return caches.match(request)
                        .then(function(cached) {
                            return cached || caches.match('/offline.html');
                        });
                })
        );
        return;
    }

    // Static assets: cache-first
    if (request.url.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?)$/)) {
        event.respondWith(
            caches.match(request)
                .then(function(cached) {
                    if (cached) return cached;

                    return fetch(request).then(function(response) {
                        var clone = response.clone();
                        caches.open(APP_SHELL_CACHE).then(function(cache) {
                            cache.put(request, clone);
                        });
                        return response;
                    });
                })
        );
        return;
    }

    // Other requests: network-first with cache fallback
    event.respondWith(
        fetch(request)
            .then(function(response) {
                return response;
            })
            .catch(function() {
                return caches.match(request);
            })
    );
});
