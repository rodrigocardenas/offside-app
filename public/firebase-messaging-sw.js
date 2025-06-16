importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyDCTXfOTcgYozlv2E6pjV_QD0QZJ47aYN8',
    authDomain: 'offside-dd226.firebaseapp.com',
    projectId: 'offside-dd226',
    storageBucket: 'offside-dd226.appspot.com',
    messagingSenderId: '249528682190',
    appId: '1:249528682190:web:c2be461351ccc44474f29f',
    measurementId: 'G-EZ0VLLBGZN'
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: payload.notification.icon,
        data: payload.data
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
