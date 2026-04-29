var CACHE_NAME = 'mylocal-comandero-v1';
var ASSETS = [
    '/comandero',
    '/dashboard/comandero.html',
    '/favicon.png',
    '/manifest.comandero.json'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(names) {
            return Promise.all(
                names.filter(function(name) { return name !== CACHE_NAME; })
                    .map(function(name) { return caches.delete(name); })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function(event) {
    if (event.request.method !== 'GET') return;
    event.respondWith(
        fetch(event.request).catch(function() {
            return caches.match(event.request);
        })
    );
});

self.addEventListener('push', function(event) {
    var data = event.data ? event.data.json() : {};
    var title = data.title || 'MyLocal';
    var options = {
        body: data.body || '',
        tag: data.tag || 'default',
        icon: '/favicon.png',
        badge: '/favicon.png',
        vibrate: [200, 100, 200]
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(function(list) {
            if (list.length > 0) return list[0].focus();
            return clients.openWindow('/comandero');
        })
    );
});
