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

    // NO mostrar notificación aquí - solo el Service Worker principal lo hará
    // Esto evita duplicados
    console.log('Notificación manejada por el Service Worker principal');
});
