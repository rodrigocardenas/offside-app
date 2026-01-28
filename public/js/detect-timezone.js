/**
 * Auto-detectar zona horaria del navegador y guardarla en el servidor
 * Se ejecuta cuando la página carga
 */
document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar si el usuario está autenticado (verificar si existe meta csrf-token)
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        return; // Usuario no autenticado
    }

    // Detectar zona horaria del navegador
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Si ya está guardada, no hacer nada
    if (localStorage.getItem('lastDetectedTimezone') === timezone) {
        return;
    }

    console.log('🌍 Detectada zona horaria:', timezone);

    // Guardar en localStorage para evitar múltiples requests
    localStorage.setItem('lastDetectedTimezone', timezone);

    // Enviar al servidor para guardar en BD
    fetch('/api/user/timezone', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ timezone: timezone })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('✓ Zona horaria guardada en servidor:', timezone);

            // Limpiar cache del navegador
            if ('caches' in window) {
                caches.keys().then(names => {
                    names.forEach(cacheName => {
                        caches.delete(cacheName);
                    });
                });
            }

            // Esperar un poco antes de recargar para asegurar que se guardó
            setTimeout(() => {
                // Agregar timestamp para forzar recarga fresh
                window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
            }, 500);
        }
    })
    .catch(error => {
        console.error('❌ Error al detectar zona horaria:', error);
    });
