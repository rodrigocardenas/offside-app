/**
 * 🌍 Sincronización Automática de Zona Horaria
 * Script SIMPLE y ROBUSTO - SIN IIFE para evitar errores silenciosos
 */

console.log('%c🌍 [TZ-SYNC] Script cargado correctamente', 'color: #00deb0; font-weight: bold; font-size: 14px;');

// ✅ Crear objeto global para almacenar funciones
window.TZSync = window.TZSync || {};

/**
 * Obtener la zona horaria del dispositivo
 */
window.TZSync.getDeviceTimezone = function() {
    try {
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        console.log('%c✅ Timezone detectado: ' + tz, 'color: #00deb0; font-weight: bold;');
        return tz;
    } catch (e) {
        console.error('%c❌ Error al detectar timezone:', 'color: #ff6b6b; font-weight: bold;', e);
        return null;
    }
};

/**
 * Obtener CSRF token
 */
window.TZSync.getCsrfToken = function() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        console.log('%c✅ CSRF token encontrado', 'color: #00deb0; font-weight: bold;');
        return token.getAttribute('content');
    } else {
        console.warn('%c⚠️ CSRF token NO encontrado', 'color: #ffd93d; font-weight: bold;');
        return null;
    }
};

/**
 * Sincronizar timezone con servidor
 */
window.TZSync.syncTimezone = function(timezone, attemptNum, maxAttempts) {
    attemptNum = attemptNum || 1;
    maxAttempts = maxAttempts || 3;

    console.log('%c🔄 Intento ' + attemptNum + '/' + maxAttempts + ' - Sincronizando: ' + timezone, 'color: #00deb0; font-weight: bold;');

    const csrfToken = window.TZSync.getCsrfToken();
    if (!csrfToken) {
        console.error('%c❌ No hay CSRF token disponible', 'color: #ff6b6b; font-weight: bold;');
        return;
    }

    fetch('/api/set-timezone', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ timezone: timezone }),
    })
        .then(function(response) {
            console.log('%c📡 Response status: ' + response.status, 'color: #00deb0;');
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            console.log('%c✅ ✅ ÉXITO: Timezone sincronizado', 'color: #51cf66; font-weight: bold; font-size: 13px;', data);
            localStorage.setItem('lastSyncedTimezone', timezone);
            localStorage.setItem('lastSyncTimestamp', new Date().toISOString());
        })
        .catch(function(error) {
            console.warn('%c⚠️ Error en intento ' + attemptNum + ': ' + error.message, 'color: #ffd93d; font-weight: bold;');

            // Reintentar
            if (attemptNum < maxAttempts) {
                var delayMs = 1000 * attemptNum;
                console.log('%c⏳ Reintentando en ' + delayMs + 'ms...', 'color: #74b9ff;');
                setTimeout(function() {
                    window.TZSync.syncTimezone(timezone, attemptNum + 1, maxAttempts);
                }, delayMs);
            } else {
                console.error('%c❌ Fallo después de ' + maxAttempts + ' intentos', 'color: #ff6b6b; font-weight: bold;', error);
            }
        });
};

/**
 * Verificar y sincronizar si es necesario
 */
window.TZSync.checkAndSync = function() {
    console.log('%c--- Verificando timezone ---', 'color: #00deb0; font-weight: bold;');

    const deviceTimezone = window.TZSync.getDeviceTimezone();
    if (!deviceTimezone) {
        console.error('%c❌ No se pudo obtener timezone del dispositivo', 'color: #ff6b6b; font-weight: bold;');
        return;
    }

    const lastSynced = localStorage.getItem('lastSyncedTimezone');
    const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');

    console.log('%c📋 Estado:', 'color: #00deb0; font-weight: bold;');
    console.log('  Device TZ: ' + deviceTimezone);
    console.log('  Last synced TZ: ' + (lastSynced || 'NINGUNO'));
    console.log('  Last timestamp: ' + (lastSyncTimestamp || 'NINGUNO'));

    // Si son iguales y sincronizado hace poco, saltar
    if (lastSynced === deviceTimezone && lastSyncTimestamp) {
        const lastSyncDate = new Date(lastSyncTimestamp);
        const fourHoursAgo = new Date(Date.now() - 4 * 60 * 60 * 1000);

        if (lastSyncDate > fourHoursAgo) {
            console.log('%c✅ Timezone sincronizado recientemente, saltando', 'color: #51cf66; font-weight: bold;');
            return;
        }
    }

    // Sincronizar
    console.log('%c🔄 Sincronizando timezone...', 'color: #00deb0; font-weight: bold;');
    window.TZSync.syncTimezone(deviceTimezone);
};

/**
 * Forzar sincronización manual
 */
window.forceTimezoneSync = function() {
    console.log('%c🌍 🌍 FORZANDO SINCRONIZACIÓN MANUAL 🌍 🌍', 'color: #00deb0; font-weight: bold; font-size: 16px;');
    localStorage.removeItem('lastSyncedTimezone');
    localStorage.removeItem('lastSyncTimestamp');
    window.TZSync.checkAndSync();
};

console.log('%c⏳ Esperando a que el documento esté listo...', 'color: #74b9ff;');

/**
 * Inicializar cuando esté listo
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('%c✅ DOMContentLoaded - Iniciando verificación', 'color: #00deb0; font-weight: bold;');
        window.TZSync.checkAndSync();
    });
} else {
    console.log('%c✅ Documento ya listo - Iniciando verificación', 'color: #00deb0; font-weight: bold;');
    window.TZSync.checkAndSync();
}

/**
 * Re-sincronizar cuando regresa el usuario
 */
window.addEventListener('focus', function() {
    console.log('%c👁️ Página recuperó focus - Verificando timezone', 'color: #74b9ff;');
    const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');

    if (lastSyncTimestamp) {
        const lastSyncDate = new Date(lastSyncTimestamp);
        const fifteenMinutesAgo = new Date(Date.now() - 15 * 60 * 1000);

        if (lastSyncDate < fifteenMinutesAgo) {
            console.log('%c🔄 Más de 15 minutos desde última sincronización, re-sincronizando...', 'color: #00deb0;');
            window.TZSync.checkAndSync();
        }
    } else {
        console.log('%c🔄 Primera sincronización al regreso', 'color: #00deb0;');
        window.TZSync.checkAndSync();
    }
});

/**
 * Sincronización periódica cada 2 horas
 */
setInterval(function() {
    if (document.hidden) {
        return;
    }
    console.log('%c⏰ Sincronización periódica (cada 2 horas)', 'color: #74b9ff;');
    window.TZSync.checkAndSync();
}, 2 * 60 * 60 * 1000);

console.log('%c✅ ✅ TIMEZONE SYNC COMPLETAMENTE LISTO', 'color: #51cf66; font-weight: bold; font-size: 14px;');
console.log('%c💡 Ejecuta: window.forceTimezoneSync() para forzar sincronización', 'color: #74b9ff; font-style: italic;');
