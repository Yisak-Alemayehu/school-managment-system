/// <reference lib="webworker" />
declare const self: ServiceWorkerGlobalScope

const CACHE_NAME = 'pwa-school-v1'
const OFFLINE_URL = '/offline.html'

// Static assets to cache on install
const PRECACHE_URLS = [
  '/app/',
  OFFLINE_URL,
]

self.addEventListener('install', (event: ExtendableEvent) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS))
  )
  self.skipWaiting()
})

self.addEventListener('activate', (event: ExtendableEvent) => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  )
  self.clients.claim()
})

self.addEventListener('fetch', (event: FetchEvent) => {
  const { request } = event
  const url = new URL(request.url)

  // Only handle same-origin requests
  if (url.origin !== self.location.origin) return

  // API calls: network-first with offline graceful failure
  if (url.pathname.startsWith('/pwa-api/')) {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(JSON.stringify({ error: 'You are offline. Please check your connection.' }), {
          status: 503,
          headers: { 'Content-Type': 'application/json' },
        })
      )
    )
    return
  }

  // App shell: serve from cache, fallback to network
  if (url.pathname.startsWith('/app')) {
    event.respondWith(
      caches.match('/app/').then(cached => cached ?? fetch(request))
    )
    return
  }

  // Static assets: cache-first
  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) return cached
      return fetch(request).then(response => {
        if (!response || response.status !== 200 || response.type !== 'basic') return response
        const toCache = response.clone()
        caches.open(CACHE_NAME).then(cache => cache.put(request, toCache))
        return response
      }).catch(() => caches.match(OFFLINE_URL) as Promise<Response>)
    })
  )
})
