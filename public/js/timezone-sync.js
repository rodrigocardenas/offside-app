/**
 * 🌍 Sincronización Automática de Zona Horaria
 * 
 * Este script se ejecuta en cada página para:
 * 1. Detectar la zona horaria del dispositivo del usuario
 * 2. Sincronizarla con el servidor si es diferente a la guardada
 * 3. Actualizar automáticamente aunque el usuario ya tenga un timezone guardado
 * 
 * Funciona para:
 * - Nuevos usuarios en login (mediante el formulario)
 * - Usuarios ya autenticados sin necesidad de volver a iniciar sesión
 * - Cambios de dispositivo/zona horaria automáticamente
 */

(function() {
    'use strict';

    const DEBUG = false; // Cambiar a true para ver logs en consola

    function log(msg, data = null) {
        if (DEBUG) {
            if (data) {
                console.log(`[TZ-SYNC] ${msg}`, data);
            } else {
                console.log(`[TZ-SYNC] ${msg}`);
            }
        }
    }

    /**
     * Obtener la zona horaria del dispositivo usando Intl API
     */
    function getDeviceTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            console.warn('[TZ-SYNC] No se pudo detectar el timezone del dispositivo:', e);
            return null;
        }
    }

    /**
     * Obtener el CSRF token
     */
    function getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : null;
    }

    /**
     * Sincronizar el timezone con el servidor (con reintentos)
     * @param {string} timezone - Zona horaria a sincronizar
     * @param {number} retries - Número de reintentos
     */
    function syncTimezoneWithServer(timezone, retries = 3) {
        const csrfToken = getCsrfToken();
        if (!csrfToken) {
            console.warn('[TZ-SYNC] CSRF token no encontrado');
            return;
        }

        const attempt = (attemptNum) => {
            log(`Intento ${attemptNum}/${retries} de sincronizar timezone: ${timezone}`);

            fetch('/api/set-timezone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ timezone: timezone }),
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    log(`✅ Zona horaria sincronizada: ${data.timezone}`);
                    // Guardar en localStorage para optimizar futuros checks
                    localStorage.setItem('lastSyncedTimezone', timezone);
                    localStorage.setItem('lastSyncTimestamp', new Date().toISOString());
                })
                .catch(error => {
                    console.warn(`[TZ-SYNC] Error en intento ${attemptNum}: ${error.message}`);
                    
                    // Reintentar si quedan intentos
                    if (attemptNum < retries) {
                        const delayMs = 1000 * attemptNum; // Backoff: 1s, 2s, 3s
                        log(`Reintentando en ${delayMs}ms...`);
                        setTimeout(() => {
                            attempt(attemptNum + 1);
                        }, delayMs);
                    } else {
                        console.error(`[TZ-SYNC] ❌ Fallo definitivo sincronizando timezone después de ${retries} intentos`);
                    }
                });
        };

        attempt(1);
    }

    /**
     * Verificar y sincronizar el timezone si es necesario
     */
    function checkAndSyncTimezone() {
        const deviceTimezone = getDeviceTimezone();
        
        if (!deviceTimezone) {
            console.warn('[TZ-SYNC] No se pudo obtener el timezone del dispositivo');
            return;
        }

        log(`Timezone del dispositivo detectado: ${deviceTimezone}`);

        // Verificar si ya fue sincronizado recientemente (dentro de las últimas 4 horas)
        const lastSynced = localStorage.getItem('lastSyncedTimezone');
        const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');
        
        if (lastSynced === deviceTimezone && lastSyncTimestamp) {
            const lastSyncDate = new Date(lastSyncTimestamp);
            const fourHoursAgo = new Date(Date.now() - 4 * 60 * 60 * 1000);
            
            if (lastSyncDate > fourHoursAgo) {
                log(`✅ Timezone sincronizado recientemente (${lastSyncDate.toLocaleTimeString()}), saltando...`);
                return;
            }
        }

        // Sincronizar si es diferente o no se ha sincronizado recientemente
        if (lastSynced !== deviceTimezone) {
            log(`🔄 Timezone cambió o nunca fue sincronizado. Anterior: ${lastSynced || 'ninguno'}, Actual: ${deviceTimezone}`);
            syncTimezoneWithServer(deviceTimezone);
        } else if (lastSyncTimestamp) {
            const lastSyncDate = new Date(lastSyncTimestamp);
            const fourHoursAgo = new Date(Date.now() - 4 * 60 * 60 * 1000);
            if (lastSyncDate < fourHoursAgo) {
                log(`🔄 Hace más de 4 horas que se sincronizó. Re-sincronizando...`);
                syncTimezoneWithServer(deviceTimezone);
            }
        }
    }

    /**
     * Inicializar cuando el documento esté disponible
     */
    function initialize() {
        // Solo ejecutar si el usuario está autenticado
        const userMeta = document.querySelector('meta[name="user-id"]');
        if (!userMeta) {
            log('Usuario no autenticado, saltando sincronización');
            return;
        }

        log('Inicializando sincronización de timezone para usuario autenticado');
        checkAndSyncTimezone();
    }

    // ✅ Intentar ejecutar lo antes posible (no esperar DOMContentLoaded)
    if (document.readyState === 'loading') {
        // Documento aún se está cargando
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        // Documento ya está listo (ej: si el script se carga tarde)
        initialize();
    }

    // ✅ También ejecutar cuando el documento esté listo (por si acaso)
    document.addEventListener('DOMContentLoaded', function() {
        log('DOMContentLoaded fired');
        initialize();
    });

    // ✅ Re-sincronizar cuando el usuario regresa a la app después de inactividad
    window.addEventListener('focus', function() {
        log('Página recuperó focus, verificando timezone...');
        
        // Sincronizar nuevamente cuando el usuario regresa
        // Pero solo si han pasado más de 15 minutos desde la última sincronización
        const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');
        if (lastSyncTimestamp) {
            const lastSyncDate = new Date(lastSyncTimestamp);
            const fifteenMinutesAgo = new Date(Date.now() - 15 * 60 * 1000);
            
            if (lastSyncDate < fifteenMinutesAgo) {
                log('Re-sincronizando timezone después de regreso a app');
                checkAndSyncTimezone();
            }
        } else {
            // Primera vez que recupera focus sin ninguna sincronización
            log('Primera sincronización al regreso');
            checkAndSyncTimezone();
        }
    });

    // ✅ Re-sincronizar periódicamente cada 2 horas (background update)
    setInterval(function() {
        if (document.hidden) {
            log('Página en background, saltando sincronización periódica');
            return;
        }
        log('Sincronización periódica cada 2 horas');
        checkAndSyncTimezone();
    }, 2 * 60 * 60 * 1000); // 2 horas

    // ✅ Exponer función global para forzar sincronización manual (debug)
    window.forceTimezoneSync = function() {
        console.log('[TZ-SYNC] Forzando sincronización manual de timezone...');
        localStorage.removeItem('lastSyncedTimezone');
        localStorage.removeItem('lastSyncTimestamp');
        checkAndSyncTimezone();
    };
})();
