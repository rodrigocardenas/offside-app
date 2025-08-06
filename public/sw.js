const CACHE_NAME = 'offside-club-v1.0.6';
const ASSETS_TO_CACHE = [
  '/',
  '/login'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching app shell');
        // Cachear recursos uno por uno para manejar errores individualmente
        return Promise.allSettled(
          ASSETS_TO_CACHE.map(url =>
            cache.add(url).catch(error => {
              console.warn(`[Service Worker] Error al cachear ${url}:`, error);
              return null; // Continuar con otros recursos
            })
          )
        );
      })
      .catch(error => {
        console.error('[Service Worker] Error general al cachear recursos:', error);
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

// Estrategia de red con caché
self.addEventListener('fetch', function(event) {
  // Permitir todas las solicitudes POST sin interferencia
  if (event.request.method === 'POST') {
    event.respondWith(fetch(event.request));
    return;
  }

  // No cachear rutas dinámicas, de autenticación y redirecciones
  if (event.request.url.includes('/groups') ||
      event.request.url.includes('/predictions') ||
      event.request.url.includes('/profile') ||
      event.request.url.includes('/ranking') ||
      event.request.url.includes('/login') ||
      event.request.url.includes('/logout') ||
      event.request.url.includes('/home') ||
      event.request.url.includes('?') ||
      event.request.url.includes('#')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // Para todas las demás solicitudes, usar estrategia de red primero
  event.respondWith(
    fetch(event.request)
      .then(function(response) {
        // Solo cachear respuestas exitosas y GET
        if (response && response.status === 200 && event.request.method === 'GET') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME)
            .then(function(cache) {
              cache.put(event.request, responseToCache);
            });
        }
        return response;
      })
      .catch(function() {
        // Si falla la red, intentar desde el cache
        return caches.match(event.request);
      })
  );
});

// Permitir que el frontend fuerce la activación del nuevo SW
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Importar Firebase Messaging Service Worker
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Configuración de Firebase
const firebaseConfig = {
    apiKey: "AIzaSyDCTXfOTcgYozlv2E6pjV_QD0QZJ47aYN8",
    authDomain: "offside-dd226.firebaseapp.com",
    projectId: "offside-dd226",
    storageBucket: "offside-dd226.appspot.com",
    messagingSenderId: "249528682190",
    appId: "1:249528682190:web:c2be461351ccc44474f29f",
    measurementId: "G-EZ0VLLBGZN"
};

// Inicializar Firebase
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Manejar mensajes de Firebase
messaging.onBackgroundMessage(function(payload) {
    console.log('Mensaje de Firebase recibido en background (SW principal):', payload);

    const notificationTitle = payload.notification?.title || 'Offside Club';
    const notificationOptions = {
        body: payload.notification?.body || 'Tienes una nueva notificación',
        icon: '/images/logo_white_bg.png',
        badge: '/images/logo_white_bg.png',
        vibrate: [100, 50, 100],
        data: payload.data || {},
        requireInteraction: true,
        tag: 'offside-notification', // Evita duplicados
        actions: [
            {
                action: 'explore',
                title: 'Ver',
                icon: '/images/logo_white_bg.png'
            },
            {
                action: 'close',
                title: 'Cerrar',
                icon: '/images/logo_white_bg.png'
            }
        ]
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Manejar clics en notificaciones
self.addEventListener('notificationclick', function(event) {
    console.log('Notificación clickeada (SW principal):', event);

    event.notification.close();

    // Obtener el enlace específico de los datos de la notificación
    const notificationData = event.notification.data || {};
    const link = notificationData.link || '/';

    console.log('Enlace a abrir:', link);

    event.waitUntil(
        // Primero verificar si ya hay una ventana abierta
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Buscar si ya hay una ventana con la URL
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes(link) && 'focus' in client) {
                    console.log('Enfocando ventana existente:', client.url);
                    return client.focus();
                }
            }

            // Si no hay ventana existente, abrir una nueva
            if (clients.openWindow) {
                console.log('Abriendo nueva ventana:', link);
                return clients.openWindow(link);
            }
        }).catch(function(error) {
            console.error('Error al manejar clic en notificación:', error);
            // Fallback: intentar abrir en nueva ventana
            return clients.openWindow(link);
        })
    );
});
