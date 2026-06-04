const CACHE = 'milagrostv-v3';

const SHELL = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/icon.svg',
    '/icon-192.png',
    '/icon-512.png',
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

// Push notification received
self.addEventListener('push', e => {
    if (!e.data) return;
    let data = {};
    try { data = e.data.json(); } catch(_) { data = { title: 'MilagrosTV', body: e.data.text() }; }
    e.waitUntil(
        self.registration.showNotification(data.title || 'MilagrosTV', {
            body:  data.body  || '',
            icon:  '/icon.svg',
            badge: '/icon.svg',
            data:  { url: data.url || '/' },
        })
    );
});

// Notification click
self.addEventListener('notificationclick', e => {
    e.notification.close();
    e.waitUntil(
        clients.matchAll({ type: 'window' }).then(wins => {
            const url = e.notification.data?.url || '/';
            const existing = wins.find(w => w.url.includes(self.location.origin));
            if (existing) { existing.focus(); existing.navigate(url); }
            else           clients.openWindow(url);
        })
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
            .catch(() => caches.match(e.request).then(cached => cached || caches.match('/offline.html')))
    );
});
