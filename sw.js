const CACHE_NAME = 'player99-pwa-cache-v2';

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

async function serveMediaFromCacheOrNetwork(request) {
    const cache = await caches.open(CACHE_NAME);
    const url = new URL(request.url);
    const cleanUrl = url.origin + url.pathname;

    // Procura pela URL limpa (sem query string e independente de header Range)
    let cached = await cache.match(cleanUrl);
    if (!cached) {
        cached = await cache.match(request, { ignoreSearch: true });
    }

    if (!cached) {
        try {
            // Baixa arquivo completo (sem header Range) para poder armazenar 200 OK no Cache API
            const fullRes = await fetch(cleanUrl, { credentials: 'same-origin' });
            if (fullRes && fullRes.status === 200) {
                await cache.put(cleanUrl, fullRes.clone());
                cached = fullRes;
            } else {
                return fullRes;
            }
        } catch (err) {
            return new Response('', { status: 503, statusText: 'Offline' });
        }
    }

    const rangeHeader = request.headers.get('range');
    if (rangeHeader && cached) {
        try {
            const buf = await cached.arrayBuffer();
            const total = buf.byteLength;
            const parts = rangeHeader.replace(/bytes=/, '').split('-');
            const start = parseInt(parts[0], 10) || 0;
            const end = parts[1] ? Math.min(parseInt(parts[1], 10), total - 1) : (total - 1);

            if (start >= total || start > end) {
                return new Response(null, {
                    status: 416,
                    headers: { 'Content-Range': `bytes */${total}` }
                });
            }

            const sliced = buf.slice(start, end + 1);
            return new Response(sliced, {
                status: 206,
                statusText: 'Partial Content',
                headers: {
                    'Content-Type': cached.headers.get('Content-Type') || 'video/mp4',
                    'Content-Range': `bytes ${start}-${end}/${total}`,
                    'Accept-Ranges': 'bytes',
                    'Content-Length': String(sliced.byteLength)
                }
            });
        } catch (e) {
            return cached;
        }
    }

    return cached;
}

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Ignora chamadas de API em tempo real (ping, pareamento, painel, admin)
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
        event.respondWith(serveMediaFromCacheOrNetwork(request));
        return;
    }

    // Network-first com fallback para cache offline para páginas do player, widgets e assets da mesma origem
    if (url.origin === self.location.origin) {
        event.respondWith(
            fetch(request).then(netRes => {
                if (netRes && netRes.status === 200) {
                    const clone = netRes.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                }
                return netRes;
            }).catch(() => {
                return caches.open(CACHE_NAME).then(cache => {
                    return cache.match(request).then(exact => exact || cache.match(request, { ignoreSearch: true }));
                });
            })
        );
    }
});

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'CLEANUP_CACHE' && Array.isArray(event.data.urls)) {
        const keepSet = new Set(event.data.urls.map(u => {
            try {
                const parsed = new URL(u, self.location.origin);
                return parsed.origin + parsed.pathname;
            } catch (e) {
                return '';
            }
        }).filter(Boolean));

        caches.open(CACHE_NAME).then(cache => {
            cache.keys().then(keys => {
                keys.forEach(req => {
                    try {
                        const u = new URL(req.url);
                        const isUploadMedia = u.pathname.includes('/uploads/clientes/') && /\.(mp4|webm|ogg|jpg|jpeg|png|gif|webp)$/i.test(u.pathname);
                        if (isUploadMedia && !keepSet.has(u.origin + u.pathname)) {
                            cache.delete(req);
                        }
                    } catch (e) {}
                });
            });
        });
    }
});
