// Este script fuerza la actualización del service worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
      for (let registration of registrations) {
        registration.update().then(() => {
          console.log('Service Worker actualizado');
        }).catch(error => {
          console.error('Error actualizando Service Worker:', error);
        });
      }
    });
  });
}
