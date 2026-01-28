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
        const userLanguage = navigator.language || navigator.userLanguage;
        
        console.log('%c✅ Timezone detectado: ' + tz, 'color: #00deb0; font-weight: bold;');
        console.log('%c📍 Idioma/Locale: ' + userLanguage, 'color: #74b9ff;');
        console.log('%c🕐 Offset actual: ' + (new Date().getTimezoneOffset() / -60) + ' horas', 'color: #74b9ff;');
        
        return tz;
    } catch (e) {
        console.error('%c❌ Error al detectar timezone:', 'color: #ff6b6b; font-weight: bold;', e);
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

    // Preparar headers
    var headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    // Intenta obtener Bearer token de localStorage (para APIs móviles/Capacitor)
    var bearerToken = localStorage.getItem('api_token');
    if (bearerToken) {
        headers['Authorization'] = 'Bearer ' + bearerToken;
        console.log('%c🔐 Usando Bearer token para autenticación', 'color: #00deb0; font-weight: bold;');
    } else {
        console.log('%c🔐 Usando autenticación por sesión de navegador', 'color: #00deb0; font-weight: bold;');
    }

    fetch('/api/set-timezone', {
        method: 'POST',
        headers: headers,
        credentials: 'include', // Incluir cookies de sesión
        body: JSON.stringify({ timezone: timezone }),
    })
        .then(function(response) {
            console.log('%c📡 Response status: ' + response.status, 'color: #00deb0;');
            
            // Log del contenido si no es JSON
            return response.text().then(function(text) {
                console.log('%c📝 Response body (primeros 500 chars): ' + text.substring(0, 500), 'color: #74b9ff; font-size: 10px;');
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Response no es JSON: ' + text.substring(0, 200));
                }
            });
        })
        .then(function(data) {
            if (data.success) {
                console.log('%c✅ ✅ ÉXITO: Timezone sincronizado', 'color: #51cf66; font-weight: bold; font-size: 13px;', data);
                localStorage.setItem('lastSyncedTimezone', timezone);
                localStorage.setItem('lastSyncTimestamp', new Date().toISOString());
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
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
console.log('%c💡 Ejecuta: window.TZSync.debugInfo() para ver información de debug', 'color: #74b9ff; font-style: italic;');

/**
 * Función de debug para verificar todo
 */
window.TZSync.debugInfo = function() {
    console.log('%c========== DEBUG INFO ==========', 'color: #00deb0; font-weight: bold; font-size: 14px;');
    
    const deviceTz = window.TZSync.getDeviceTimezone();
    const lastSynced = localStorage.getItem('lastSyncedTimezone');
    const lastTimestamp = localStorage.getItem('lastSyncTimestamp');
    
    console.log('%c📋 Información de Dispositivo:', 'color: #00deb0; font-weight: bold;');
    console.log('  Timezone: ' + deviceTz);
    console.log('  Idioma: ' + (navigator.language || navigator.userLanguage));
    console.log('  Offset: ' + (new Date().getTimezoneOffset() / -60) + ' horas');
    
    console.log('%c📋 Información en LocalStorage:', 'color: #00deb0; font-weight: bold;');
    console.log('  Último sincronizado: ' + (lastSynced || 'NUNCA'));
    console.log('  Timestamp: ' + (lastTimestamp || 'NUNCA'));
    
    // Verificar cookies de sesión
    console.log('%c📋 Cookies:', 'color: #00deb0; font-weight: bold;');
    console.log('  XSRF-TOKEN: ' + (document.cookie.includes('XSRF-TOKEN') ? 'SÍ' : 'NO'));
    console.log('  LARAVEL_SESSION: ' + (document.cookie.includes('LARAVEL_SESSION') ? 'SÍ' : 'NO'));
    
    // User ID del meta tag
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    console.log('%c📋 Autenticación:', 'color: #00deb0; font-weight: bold;');
    console.log('  User ID (meta): ' + (userIdMeta ? userIdMeta.getAttribute('content') : 'NO ENCONTRADO'));
    
    console.log('%c================================', 'color: #00deb0; font-weight: bold;');
};
