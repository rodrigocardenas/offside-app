importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQC0MJVke0ijWEeb\nNrmfT7aqMXzFfl2cLBV5S8DhzjgIQsrWY8kz1Fvm9tJ/NmIVtpNRitIg32HODN+A\nzCOQJfUv1JldVi3ofdMa0vYnvtmhOTH6EQULfr6cZfnyd9ySIHGo+YFtNwH81aYx\nHfwZiQwtRhuGxHLhFwHizYDqUbEepXMOcOwWeXpaW4e/oYINKaCDHTR0oKNmdjyo\nDQZAXgbVMtGu31D3y1KZWIQEI/cEJN1b/K5QgDYXYfJ5y2tIMfnlVYIYPk9BngfX\ndSeg7dzhO1xIGvJSvpYfurP7PfSKn2u5Fz7ZcHRwHHWUzJsSexYb4Qw7K4JWvmaQ\nAFhOzXk7AgMBAAECggEAANScft0zZ4IiUuQWSCM7DbRMjg7glILUCfnzV5LUfMB1\nywLGmRlsvWpAn1Kug1HoT0EKFKJwz7KEbPAmEYwV6Fr/GbL3HxISuq+ntEMPZd1v\n9KqCe6SdLPazGLER8FNIfX8XTqApJbfIxunYZnxljnAhjUgzLxW+ZVknIqCMkKSW\nllIXwnaQFiDMb/wvFYrPCyXJzK5TdJVfRJXb+3SqbEJ2awLu1MQUETf3uwWlp4ti\nMotjv6GgrogPhDrdwRBrkWFt6kKbL4RzUyMIBc46QUTXnictaHHoVj5FwMXuCQFh\nuWSwd6JppTW28wr2yh6p4EMIwCcGkKmMDPdL3sl/KQKBgQDvdu21A4WiCHSqF+sX\nlf7eX1Jdwm4el6HRXPfK+h/0itxZYVYx/OS/wJBCQk0IDSZn0Z0UT0+TtQ8ELq1U\nugIMReVC8RO8n6mSbFizjd//5n4dz0sNMvb96LPEe1e4BNEN/Aa/U9USPDqalbcl\nE8CfBAOj6gYLurVJKo938mcnTQKBgQDAodc39XgKW6kkEUrWnKkaitIdi2OAXVrm\nuX0MghdqYVE0x49bPOmYsnIjUAL+rctuw9g0vUwqsiZ7PWcZYga2en1xaxbQymDK\nEM4t3tzFKvbM2uZNSmU+eolIj5ex3vLBT+VH7zGMcgJgmQF9NmRc/0Aoikmi8ZgT\nhqawmr8upwKBgFp8Gf5YQlqjz1GmkBLtfs0QP9Nl8K5mpaKy+n8cXI7PGcw8V5Gz\n+cvrO5eN7gWo0mZQcoaGIY+yzuXJrX1ie/ufPa454jdYLX9CqZHPfmD3+5fQCJAI\nPgRbtfH8mXzTdRtPrE0HpG+riol3ISlFeXec0LNTbs1n07C+AFBWrFvRAoGAYU7f\n+0Ki2wimjrO1jGgi/Gd38LjDEMsX6kl1I+Zrka+LaBAPGNXSYvJxuejnOmPsyg1g\noHOnkEHiMos4E5hzL6b8y1h/dAVnk2ud24ojF+62MZG6mPyl3EPmKaNvy8iF9KU5\ne3cXHo1RKh7go7HgTSIb9N62h/tnCSYR+lCRly0CgYBS2g+KiSm8cmsSZsx+W7gJ\nLvvjTGFiZNnw7CPW2aTZll48VAhflk7Lo01CsTvOQAlApB0/czQaWr8Tax8/6ueI\nRvdirm6jzakDubwFWARqMVJLYOp5tZXr21l7VEt5CsAPuzz10+5FP22GUxsKULch\nFCm77Lo9GSbgdNo/yC/S1w==',
    authDomain: 'offside-dd226.firebaseapp.com',
    projectId: 'offside-dd226',
    storageBucket: 'offside-dd226.appspot.com',
    messagingSenderId: '123456789',
    appId: '1:123456789:web:abcdef'
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
