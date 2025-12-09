const CACHE_NAME = 'sam-store-v1';
const FILES_TO_CACHE = [
  'index.php',
  'latest.php',
  'favorites.php',
  'track.php',
  'category.php',
  'style.min.css',
  'script.js',
  'manifest.json',
  'logo.png',
  'icons/icon-192x192.png',
  'icons/icon-512x512.png'
];

// Install event: cache the app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[ServiceWorker] Pre-caching offline page');
        return cache.addAll(FILES_TO_CACHE);
      })
  );
  self.skipWaiting();
});

// Activate event: clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          console.log('[ServiceWorker] Removing old cache', key);
          return caches.delete(key);
        }
      }));
    })
  );
  self.clients.claim();
});

// Fetch event: serve cached content when offline
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Cache hit - return response
        if (response) {
          return response;
        }

        return fetch(event.request).then(
          (response) => {
            // Check if we received a valid response
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // IMPORTANT: Clone the response. A response is a stream
            // and because we want the browser to consume the response
            // as well as the cache consuming the response, we need
            // to clone it so we have two streams.
            var responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then((cache) => {
                cache.put(event.request, responseToCache);
              });

            return response;
          }
        );
      })
  );
});

// --- Push Notification Event Listener ---
self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push Received.');
    const data = event.data.json();

    const title = data.title || 'رسالة جديدة';
    const options = {
        body: data.body || 'لديك رسالة جديدة من متجر سام.',
        icon: data.icon || 'icons/icon-192x192.png',
        badge: data.badge || 'icons/icon-192x192.png',
        data: {
            url: data.data ? data.data.url : self.location.origin,
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// --- Notification Click Event Listener ---
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification click Received.');

    event.notification.close();

    const urlToOpen = event.notification.data.url || self.location.origin;

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true,
        }).then((clientList) => {
            // If a window for the app is already open, focus it.
            for (const client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // Otherwise, open a new window.
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
