const CACHE_NAME = 'offside-club-v1.0.0';
const ASSETS_TO_CACHE = [
  '/',
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
self.addEventListener('fetch', (event) => {
  // No procesar peticiones que no sean HTTP/HTTPS
  if (!event.request.url.startsWith('http')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Si la respuesta está en caché, la devolvemos
        if (response) {
          return response;
        }

        // Si no está en caché, hacemos la petición a la red
        return fetch(event.request)
          .then((response) => {
            // No cacheamos respuestas que no sean exitosas o que no sean del mismo origen
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clonamos la respuesta para guardarla en caché
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then((cache) => {
                cache.put(event.request, responseToCache);
              })
              .catch(error => {
                console.error('Error al guardar en caché:', error);
              });

            return response;
          })
          .catch(error => {
            console.error('Error en la petición:', error);
            // Si estamos offline, intentamos servir offline.html
            if (event.request.mode === 'navigate') {
              return caches.match('/offline.html');
            }
            return new Response('Error de conexión', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain'
              })
            });
          });
      })
  );
});
