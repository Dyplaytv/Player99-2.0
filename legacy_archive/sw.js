const CACHE_NAME = 'player-video-cache-v1';

// O que fazer durante a instalação do SW (nada de especial por enquanto)
self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

// Interceptar as requisições
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Fazer cache de vídeos e imagens da pasta uploads ou widgets
    const isMedia = url.pathname.endsWith('.mp4') || url.pathname.endsWith('.webm') || url.pathname.endsWith('.ogg') || 
                    url.pathname.endsWith('.jpg') || url.pathname.endsWith('.jpeg') || url.pathname.endsWith('.png') || 
                    url.pathname.endsWith('.gif') || url.pathname.endsWith('.webp');

    if ((url.pathname.includes('/uploads/') || url.pathname.includes('/widgets_images/')) && isMedia) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(request).then(response => {
                    // Se o vídeo já está no cache interno da TV, devolve na hora (mesmo sem internet)
                    if (response) {
                        return response;
                    }
                    // Se não está, baixa da internet e salva no cache
                    return fetch(request).then(networkResponse => {
                        // Só salva no cache requisições de sucesso completas (200)
                        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                            cache.put(request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(error => {
                        console.error('Falha ao baixar vídeo, e não está no cache:', error);
                    });
                });
            })
        );
    } else if (request.method === 'GET' && !url.pathname.includes('api_')) {
        // Para páginas HTML (player.php, tv.php, etc) e scripts/estilos
        // Estratégia Network First (Tenta a rede, se falhar pega do cache da última vez)
        event.respondWith(
            fetch(request).then(networkResponse => {
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                console.log('Modo Offline: Tentando carregar página ' + request.url + ' do Cache...');
                return caches.match(request);
            })
        );
    }
});

// Ouvir mensagens da página principal para limpeza de memória
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'CLEANUP_CACHE') {
        const currentUrls = event.data.urls.map(u => new URL(u, self.location.origin).href);
        
        caches.open(CACHE_NAME).then(cache => {
            cache.keys().then(cachedRequests => {
                cachedRequests.forEach(request => {
                    if (!currentUrls.includes(request.url)) {
                        console.log('Removendo vídeo antigo da memória da TV:', request.url);
                        cache.delete(request);
                    }
                });
            });
        });
    }
});
