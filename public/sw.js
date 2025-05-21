const CACHE_NAME = 'offside-club-v1.0.0';
const ASSETS_TO_CACHE = [
  '/',
  '/login',
  '/css/app.css',
  '/js/app.js',
  '/js/navigation.js',
  '/images/logo-offside-192x192.png',
  '/images/logo-offside-512x512.png',
  '/manifest.json',
  '/favicon.ico',
  '/offline.html'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching app shell');
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .catch(error => {
        console.error('Error al cachear recursos:', error);
      })
  );
  // Activa el service worker inmediatamente
  self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating Service Worker...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  // Toma el control de los clientes inmediatamente
  event.waitUntil(clients.claim());
});

// Estrategia de red con caché
self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // Cache hit - return response
        if (response) {
          return response;
        }

        // Solo cachear solicitudes GET
        if (event.request.method !== 'GET') {
          return fetch(event.request);
        }

        return fetch(event.request).then(
          function(response) {
            // Verificar si la respuesta es válida
            if(!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clonar la respuesta
            var responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(function(cache) {
                cache.put(event.request, responseToCache);
              });

            return response;
          }
        );
      })
    );
});
