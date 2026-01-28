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

    const DEBUG = true; // ✅ ACTIVADO por defecto para ver logs

    function log(msg, data = null) {
        if (DEBUG) {
            if (data) {
                console.log(`%c[TZ-SYNC] ${msg}`, 'color: #00deb0; font-weight: bold;', data);
            } else {
                console.log(`%c[TZ-SYNC] ${msg}`, 'color: #00deb0; font-weight: bold;');
            }
        }
    }

    /**
     * Obtener la zona horaria del dispositivo usando Intl API
     */
    function getDeviceTimezone() {
        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            log(`✅ Timezone del dispositivo detectado: ${tz}`);
            return tz;
        } catch (e) {
            console.error('[TZ-SYNC] ❌ No se pudo detectar el timezone del dispositivo:', e);
            return null;
        }
    }

    /**
     * Obtener el CSRF token
     */
    function getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            log('✅ CSRF token encontrado');
            return token.getAttribute('content');
        } else {
            console.warn('[TZ-SYNC] ⚠️ CSRF token no encontrado');
            return null;
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    function isUserAuthenticated() {
        const userMeta = document.querySelector('meta[name="user-id"]');
        const isAuth = !!userMeta;
        const userId = userMeta ? userMeta.getAttribute('content') : 'N/A';
        
        if (isAuth) {
            log(`✅ Usuario autenticado (ID: ${userId})`);
        } else {
            log('⚠️ Usuario NO autenticado - script seguirá ejecutándose igualmente');
        }
        
        return { isAuth, userId };
    }

    /**
     * Sincronizar el timezone con el servidor (con reintentos)
     * @param {string} timezone - Zona horaria a sincronizar
     * @param {number} retries - Número de reintentos
     */
    function syncTimezoneWithServer(timezone, retries = 3) {
        const csrfToken = getCsrfToken();
        if (!csrfToken) {
            console.error('[TZ-SYNC] ❌ CSRF token no encontrado - no se puede sincronizar');
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
                    log(`Response status: ${response.status}`);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    log(`✅ Zona horaria sincronizada exitosamente: ${data.timezone}`, data);
                    // Guardar en localStorage para optimizar futuros checks
                    localStorage.setItem('lastSyncedTimezone', timezone);
                    localStorage.setItem('lastSyncTimestamp', new Date().toISOString());
                })
                .catch(error => {
                    console.warn(`[TZ-SYNC] ⚠️ Error en intento ${attemptNum}: ${error.message}`);
                    
                    // Reintentar si quedan intentos
                    if (attemptNum < retries) {
                        const delayMs = 1000 * attemptNum; // Backoff: 1s, 2s, 3s
                        log(`Reintentando en ${delayMs}ms...`);
                        setTimeout(() => {
                            attempt(attemptNum + 1);
                        }, delayMs);
                    } else {
                        console.error(`[TZ-SYNC] ❌ Fallo definitivo sincronizando timezone después de ${retries} intentos`, error);
                    }
                });
        };

        attempt(1);
    }

    /**
     * Verificar y sincronizar el timezone si es necesario
     */
    function checkAndSyncTimezone() {
        log('--- Iniciando verificación de timezone ---');
        
        const deviceTimezone = getDeviceTimezone();
        
        if (!deviceTimezone) {
            console.error('[TZ-SYNC] ❌ No se pudo obtener el timezone del dispositivo');
            return;
        }

        log(`Timezone del dispositivo: ${deviceTimezone}`);

        // Verificar si ya fue sincronizado recientemente (dentro de las últimas 4 horas)
        const lastSynced = localStorage.getItem('lastSyncedTimezone');
        const lastSyncTimestamp = localStorage.getItem('lastSyncTimestamp');
        
        log(`LastSynced: ${lastSynced || 'NINGUNO'}, LastTimestamp: ${lastSyncTimestamp || 'NINGUNO'}`);
        
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
        log('=== INICIALIZANDO TIMEZONE SYNC ===');
        
        const { isAuth, userId } = isUserAuthenticated();
        
        // ✅ IMPORTANTE: Ejecutar SIEMPRE, aunque no esté autenticado
        // (El script de login también lo usará)
        log('Ejecutando checkAndSyncTimezone...');
        checkAndSyncTimezone();
    }

    // ✅ Intentar ejecutar lo antes posible (no esperar DOMContentLoaded)
    log('Script timezone-sync.js cargado');
    
    if (document.readyState === 'loading') {
        // Documento aún se está cargando
        log('Documento aún se está cargando, esperando DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        // Documento ya está listo (ej: si el script se carga tarde)
        log('Documento ya está listo, ejecutando initialize...');
        initialize();
    }

    // ✅ También ejecutar cuando el documento esté listo (por si acaso)
    document.addEventListener('DOMContentLoaded', function() {
        log('DOMContentLoaded event fired');
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
        console.log('%c🌍 FORZANDO SINCRONIZACIÓN MANUAL DE TIMEZONE', 'color: #00deb0; font-weight: bold; font-size: 14px;');
        localStorage.removeItem('lastSyncedTimezone');
        localStorage.removeItem('lastSyncTimestamp');
        checkAndSyncTimezone();
    };

    // ✅ Exponer función para desactivar debug (opcional)
    window.disableTzDebug = function() {
        console.log('Debug de timezone desactivado');
        // Cambiar la variable (esto no funcionará porque es const, pero lo dejamos como referencia)
    };

    log('=== TIMEZONE SYNC LISTO ===');
    console.log('%c💡 Tip: Ejecuta window.forceTimezoneSync() para forzar sincronización manual', 'color: #00deb0; font-style: italic;');

})();

