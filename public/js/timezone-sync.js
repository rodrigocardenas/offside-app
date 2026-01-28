/**
 * 🌍 Sincronización Automática de Zona Horaria
 * 
 * Este script se ejecuta en cada página para:
 * 1. Detectar la zona horaria del dispositivo del usuario
 * 2. Sincronizarla con el servidor si es diferente a la guardada
 * 3. Actualizar automáticamente aunque el usuario ya tenga un timezone guardado
 */

(function() {
    'use strict';

    /**
     * Obtener la zona horaria del dispositivo usando Intl API
     */
    function getDeviceTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            console.warn('No se pudo detectar el timezone del dispositivo:', e);
            return null;
        }
    }

    /**
     * Sincronizar el timezone con el servidor
     * @param {string} timezone - Zona horaria a sincronizar
     */
    function syncTimezoneWithServer(timezone) {
        // Obtener el CSRF token del meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.warn('CSRF token no encontrado');
            return;
        }

        fetch('/api/set-timezone', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            },
            body: JSON.stringify({ timezone: timezone }),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Zona horaria sincronizada con el servidor:', data.timezone);
                // Guardar en localStorage para optimizar futuros checks
                localStorage.setItem('lastSyncedTimezone', timezone);
                localStorage.setItem('lastSyncTimestamp', new Date().toISOString());
            })
            .catch(error => {
                console.error('❌ Error al sincronizar timezone:', error);
            });
    }

    /**
     * Verificar y sincronizar el timezone si es necesario
     */
    function checkAndSyncTimezone() {
        const deviceTimezone = getDeviceTimezone();
        
        if (!deviceTimezone) {
            console.warn('No se pudo obtener el timezone del dispositivo');
            return;
        }

        // Verificar si ya fue sincronizado recientemente (dentro de las últimas 6 horas)
        const lastSynced = localStorage.getItem('lastSyncedTimezone');
        const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');
        
        if (lastSynced === deviceTimezone && lastSyncTimestamp) {
            const lastSyncDate = new Date(lastSyncTimestamp);
            const sixHoursAgo = new Date(Date.now() - 6 * 60 * 60 * 1000);
            
            if (lastSyncDate > sixHoursAgo) {
                console.log('✅ Timezone ya fue sincronizado recientemente:', deviceTimezone);
                return;
            }
        }

        // Sincronizar si es diferente o no se ha sincronizado aún
        if (lastSynced !== deviceTimezone) {
            console.log('🔄 Sincronizando timezone del dispositivo:', deviceTimezone);
            syncTimezoneWithServer(deviceTimezone);
        }
    }

    /**
     * Inicializar cuando el documento esté listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Solo ejecutar si el usuario está autenticado (verificar si existe el meta tag de user)
        const userMeta = document.querySelector('meta[name="user-id"]');
        if (userMeta) {
            checkAndSyncTimezone();
        }
    });

    // También ejecutar cuando se gana focus (el usuario vuelve a la app)
    // Esto captura cuando el usuario sale y regresa a la app
    window.addEventListener('focus', function() {
        // Sincronizar nuevamente cuando el usuario regresa
        // Pero solo si han pasado más de 30 minutos desde la última sincronización
        const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');
        if (lastSyncTimestamp) {
            const lastSyncDate = new Date(lastSyncTimestamp);
            const thirtyMinutesAgo = new Date(Date.now() - 30 * 60 * 1000);
            
            if (lastSyncDate < thirtyMinutesAgo) {
                console.log('🔄 Re-sincronizando timezone después de inactividad');
                checkAndSyncTimezone();
            }
        }
    });
})();
