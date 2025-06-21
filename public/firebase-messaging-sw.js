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
    console.log('Notificación clickeada:', event);

    event.notification.close();

    // Obtener el enlace específico de los datos de la notificación
    const notificationData = event.notification.data || {};
    const link = notificationData.link || '/';

    if (event.action === 'explore') {
        // Abrir la aplicación en el enlace específico
        event.waitUntil(
            clients.openWindow(link)
        );
    } else if (event.action === 'close') {
        // Solo cerrar la notificación
        event.notification.close();
    } else {
        // Clic en la notificación principal - abrir el enlace específico
        event.waitUntil(
            clients.openWindow(link)
        );
    }
});
