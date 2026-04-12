var CACHE_NAME = 'pwa-school-v1';
var OFFLINE_URL = '/offline.html';

self.addEventListener('install', function (event) {
  // Pre-cache the offline fallback only; don't cache the app shell here
  // (the shell is served fresh on navigation to avoid stale HTML)
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return fetch(OFFLINE_URL).then(function (res) {
        return cache.put(OFFLINE_URL, res);
      }).catch(function () { /* offline page not available—skip */ });
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return k !== CACHE_NAME; })
            .map(function (k) { return caches.delete(k); })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function (event) {
  var request = event.request;
  var url = new URL(request.url);

  // Only intercept same-origin requests
  if (url.origin !== self.location.origin) return;

  // API calls: network-first; return JSON error when offline
  if (url.pathname.startsWith('/pwa-api/')) {
    event.respondWith(
      fetch(request).catch(function () {
        return new Response(
          JSON.stringify({ error: 'You are offline. Please check your connection.' }),
          { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // App navigation: always go to network so PHP serves fresh index.html;
  // fall back to offline page if network is down
  if (request.mode === 'navigate' && url.pathname.startsWith('/app')) {
    event.respondWith(
      fetch(request).catch(function () {
        return caches.match(OFFLINE_URL).then(function (r) {
          return r || new Response('You are offline.', { status: 503 });
        });
      })
    );
    return;
  }

  // Versioned static assets (/app/assets/*): cache-first (they have hashed names)
  if (url.pathname.startsWith('/app/assets/')) {
    event.respondWith(
      caches.match(request).then(function (cached) {
        if (cached) return cached;
        return fetch(request).then(function (response) {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
          var toCache = response.clone();
          caches.open(CACHE_NAME).then(function (cache) {
            cache.put(request, toCache);
          });
          return response;
        });
      })
    );
    return;
  }
});
