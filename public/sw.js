// naToke service worker — network-first for pages, cache-first for hashed assets.
const CACHE = 'natoke-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Immutable, hashed assets → cache-first.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith((async () => {
            const cached = await caches.match(req);
            if (cached) return cached;
            const res = await fetch(req);
            const cache = await caches.open(CACHE);
            cache.put(req, res.clone());
            return res;
        })());
        return;
    }

    // Everything else (pages/API) → network-first, cache as offline fallback.
    event.respondWith((async () => {
        try {
            return await fetch(req);
        } catch (err) {
            const cached = await caches.match(req);
            if (cached) return cached;
            throw err;
        }
    })());
});
