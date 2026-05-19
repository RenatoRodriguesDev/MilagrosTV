const CACHE = 'milagrostv-v1';

const SHELL = [
    '/',
    '/manifest.json',
    '/icon.svg',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
    'https://cdn.tailwindcss.com',
];

// Install: cache app shell
self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(SHELL)).then(() => self.skipWaiting())
    );
});

// Activate: remove old caches
self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// Fetch: network first for videos and API, cache first for shell
self.addEventListener('fetch', e => {
    const url = new URL(e.request.url);

    // Never cache video streams, admin, or API calls
    if (url.pathname.startsWith('/video/') ||
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/progress') ||
        url.pathname.startsWith('/watched') ||
        e.request.method !== 'GET') {
        return;
    }

    // Cache-first for static assets (fonts, CDN)
    if (url.hostname !== self.location.hostname) {
        e.respondWith(
            caches.match(e.request).then(cached =>
                cached || fetch(e.request).then(res => {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(e.request, clone));
                    return res;
                })
            )
        );
        return;
    }

    // Network first for pages (always fresh content)
    e.respondWith(
        fetch(e.request)
            .then(res => {
                const clone = res.clone();
                caches.open(CACHE).then(c => c.put(e.request, clone));
                return res;
            })
            .catch(() => caches.match(e.request))
    );
});
