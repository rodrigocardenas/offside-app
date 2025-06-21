// Firebase Messaging Service Worker
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

// Manejar mensajes de Firebase en background
messaging.onBackgroundMessage(function(payload) {
    console.log('Mensaje de Firebase recibido en background:', payload);

    const notificationTitle = payload.notification?.title || 'Offside Club';
    const notificationOptions = {
        body: payload.notification?.body || 'Tienes una nueva notificación',
        icon: '/images/logo_white_bg.png',
        badge: '/images/logo_white_bg.png',
        vibrate: [100, 50, 100],
        data: payload.data || {},
        requireInteraction: true,
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
    console.log('Notificación clickeada (Firebase):', event);

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
