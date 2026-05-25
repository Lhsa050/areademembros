/**
 * Service Worker — Cache básico para PWA
 * 
 * Estratégia: Network-first com fallback para cache
 */

const CACHE_NAME = 'membros-v2';
const PRECACHE_URLS = [
    '/assets/css/styles.css'
];

// Install: pré-cache dos assets essenciais (com fallback individual)
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return Promise.allSettled(
                    PRECACHE_URLS.map(url =>
                        cache.add(url).catch(err => {
                            console.warn('[SW] Falha ao cachear:', url, err.message);
                        })
                    )
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Activate: limpa caches antigos
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames =>
            Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch: Network-first para HTML/API, Cache-first para assets
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Ignora requisições não-GET
    if (event.request.method !== 'GET') return;

    // Ignora requisições de outras origens
    if (url.origin !== location.origin) return;

    // Assets estáticos: cache-first
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|webp|ico|woff2?)$/)) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // HTML/API: network-first
    event.respondWith(
        fetch(event.request)
            .then(response => {
                if (response.ok && url.pathname.includes('/m/')) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => caches.match(event.request))
    );
});

// Push notifications listener
self.addEventListener('push', event => {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'Nova notificação', body: event.data.text() };
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'Notificação', {
            body: data.body || '',
            icon: data.icon || '/assets/images/icon-192.png',
            badge: '/assets/images/icon-72.png',
            data: { url: data.url || '/' },
            vibrate: [200, 100, 200]
        })
    );
});

// Notification click handler
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                for (const client of clientList) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                return clients.openWindow(url);
            })
    );
});
