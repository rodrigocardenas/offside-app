const CACHE_NAME = 'offside-club-v1.0.3';
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

  // Notificar a los clientes que hay una nueva versión
  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then(clientsArr => {
      clientsArr.forEach(client => {
        client.postMessage({ type: 'NEW_VERSION_AVAILABLE' });
      });
    })
  );
});

// Sistema de bloqueo de solicitudes duplicadas
const pendingRequests = new Map();

// Estrategia de red con caché
self.addEventListener('fetch', function(event) {
  // Manejar solicitudes POST
  if (event.request.method === 'POST') {
    const requestId = event.request.url + event.request.headers.get('X-CSRF-TOKEN');

    // Si hay una solicitud pendiente con el mismo ID, bloquearla
    if (pendingRequests.has(requestId)) {
      console.log('[Service Worker] Bloqueando solicitud POST duplicada:', requestId);
      event.respondWith(
        new Response(JSON.stringify({
          error: 'Solicitud duplicada detectada'
        }), {
          status: 429,
          headers: {
            'Content-Type': 'application/json'
          }
        })
      );
      return;
    }

    // Marcar la solicitud como pendiente
    pendingRequests.set(requestId, true);

    // Limpiar después de 5 segundos
    setTimeout(() => {
      pendingRequests.delete(requestId);
    }, 5000);

    // Continuar con la solicitud original
    event.respondWith(
      fetch(event.request.clone())
        .then(response => {
          pendingRequests.delete(requestId);
          return response;
        })
        .catch(error => {
          pendingRequests.delete(requestId);
          throw error;
        })
    );
    return;
  }

  // No cachear rutas dinámicas
  if (event.request.url.includes('/groups') ||
      event.request.url.includes('/predictions') ||
      event.request.url.includes('/profile') ||
      event.request.url.includes('/ranking')) {
    return fetch(event.request);
  }

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

// Permitir que el frontend fuerce la activación del nuevo SW
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
