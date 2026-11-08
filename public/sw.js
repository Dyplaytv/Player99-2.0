const CACHE_NAME = 'player99-pwa-cache-v2';

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (
        url.pathname.includes('/api/') ||
        url.pathname.includes('api_pairing') ||
        url.pathname.includes('painel-cliente') ||
        url.pathname.includes('admin')
    ) {
        return;
    }

    const isMedia = /\.(mp4|webm|ogg|jpg|jpeg|png|gif|webp)$/i.test(url.pathname);

    if (url.pathname.includes('/uploads/') && isMedia) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(request).then(cached => {
                    if (cached) return cached;
                    return fetch(request).then(netRes => {
                        if (netRes && netRes.status === 200 && netRes.type === 'basic') {
                            cache.put(request, netRes.clone());
                        }
                        return netRes;
                    }).catch(() => cached);
                });
            })
        );
        return;
    }

    if (url.origin === self.location.origin) {
        event.respondWith(
            fetch(request).then(netRes => {
                if (netRes && netRes.status === 200 && netRes.type === 'basic') {
                    const clone = netRes.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                }
                return netRes;
            }).catch(() => caches.match(request))
        );
    }
});
