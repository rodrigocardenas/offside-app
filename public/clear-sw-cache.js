// Script para limpiar el cache del Service Worker
if ('serviceWorker' in navigator) {
  console.log('Limpiando cache del Service Worker...');

  // Desregistrar todos los service workers
  navigator.serviceWorker.getRegistrations().then(function(registrations) {
    for (let registration of registrations) {
      registration.unregister().then(function(boolean) {
        console.log('Service Worker desregistrado:', boolean);
      });
    }
  });

  // Limpiar cache
  if ('caches' in window) {
    caches.keys().then(function(names) {
      for (let name of names) {
        caches.delete(name).then(function(boolean) {
          console.log('Cache eliminado:', name, boolean);
        });
      }
    });
  }
}

// Recargar la página después de limpiar
setTimeout(function() {
  window.location.reload();
}, 1000);
