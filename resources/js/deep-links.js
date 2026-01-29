/**
 * Deep Links Handler para Capacitor
 * Maneja URLs custom como offsideclub://group/123
 */

import { App } from '@capacitor/app';

class DeepLinksHandler {
    constructor() {
        this.init();
    }

    async init() {
        // Detectar si estamos en Capacitor
        if (typeof window.Capacitor === 'undefined') {
            console.log('[DeepLinks] No estamos en Capacitor, skipping');
            return;
        }

        try {
            // Escuchar deep links
            App.addListener('appUrlOpen', (event) => {
                this.handleDeepLink(event.url);
            });

            console.log('[DeepLinks] Handler inicializado correctamente');
        } catch (error) {
            console.error('[DeepLinks] Error al inicializar:', error);
        }
    }

    /**
     * Procesar deep link y navegar a la URL interna correspondiente
     * Ejemplos:
     *  - offsideclub://group/123 → /groups/123
     *  - offsideclub://match/456 → /matches/456
     *  - offsideclub://user/789 → /profile/789
     */
    handleDeepLink(url) {
        console.log('[DeepLinks] Deep link recibido:', url);

        try {
            // Parsear la URL
            const parsedUrl = new URL(url);
            const host = parsedUrl.hostname;
            const pathname = parsedUrl.pathname;

            console.log('[DeepLinks] Host:', host, 'Path:', pathname);

            // Rutas soportadas
            if (host === 'group' || host === 'groups') {
                const groupId = pathname.replace(/\//g, '');
                if (groupId) {
                    this.navigateTo(`/groups/${groupId}`);
                    return;
                }
            }

            if (host === 'match' || host === 'matches') {
                const matchId = pathname.replace(/\//g, '');
                if (matchId) {
                    this.navigateTo(`/matches/${matchId}`);
                    return;
                }
            }

            if (host === 'profile' || host === 'user') {
                const userId = pathname.replace(/\//g, '');
                if (userId) {
                    this.navigateTo(`/profile/${userId}`);
                    return;
                }
            }

            // Soportar: offsideclub://invite/code O https://app.offsideclub.es/invite/code
            if (host === 'invite' || pathname.includes('/invite/')) {
                // Extraer el código desde el pathname
                const parts = pathname.split('/').filter(p => p);
                const codeIndex = parts.indexOf('invite');
                const inviteCode = codeIndex >= 0 ? parts[codeIndex + 1] : pathname.replace(/\//g, '');

                if (inviteCode) {
                    this.navigateTo(`/invite/${inviteCode}`);
                    return;
                }
            }

            console.log('[DeepLinks] Ruta no reconocida:', host);
        } catch (error) {
            console.error('[DeepLinks] Error procesando deep link:', error);
        }
    }

    /**
     * Navegar a una ruta interna de la app
     */
    navigateTo(route) {
        console.log('[DeepLinks] Navegando a:', route);

        // Si estamos en Alpine/Vue, usar el router
        if (window.Alpine) {
            // Alpine no tiene router integrado, usamos window.location
            window.location.href = route;
        } else {
            // Fallback: cambiar URL
            window.location.href = route;
        }
    }
}

// Inicializar automáticamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new DeepLinksHandler();
        requestDeepLinksPermission();
    });
} else {
    new DeepLinksHandler();
    requestDeepLinksPermission();
}

/**
 * Solicitar al usuario configurar OffsideClub como handler preferido
 * Solo se muestra una vez en la primera apertura
 */
async function requestDeepLinksPermission() {
    try {
        // Detectar Android
        const userAgent = navigator.userAgent.toLowerCase();
        if (!userAgent.includes('android')) {
            return;
        }

        // Verificar si ya hemos pedido permiso
        const PERMISSION_KEY = 'offsideclub_deep_links_permission_requested';
        const alreadyRequested = localStorage.getItem(PERMISSION_KEY);
        if (alreadyRequested === 'true') {
            return;
        }

        // Esperar a que Capacitor esté listo
        if (typeof window.Capacitor === 'undefined') {
            return;
        }

        // Marcar como pedido
        localStorage.setItem(PERMISSION_KEY, 'true');

        // Esperar un poco para que la UI esté lista
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Mostrar dialog
        const confirmed = await showDeepLinksDialog();
        if (confirmed) {
            openDeepLinksSettings();
        }
    } catch (error) {
        console.error('[DeepLinks] Error en requestDeepLinksPermission:', error);
    }
}

/**
 * Mostrar dialog pidiendo configuración de deep links
 */
async function showDeepLinksDialog() {
    return new Promise((resolve) => {
        // Crear overlay
        const overlay = document.createElement('div');
        overlay.id = 'deep-links-dialog-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease-in;
        `;

        // Crear dialog
        const dialog = document.createElement('div');
        dialog.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 320px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        `;

        dialog.innerHTML = `
            <h2 style="margin: 0 0 8px 0; font-size: 18px; color: #1a1a1a;">⚙️ Configuración Recomendada</h2>
            <p style="margin: 0 0 16px 0; font-size: 14px; color: #666; line-height: 1.5;">
                Para que los links de invitación se abran correctamente en OffsideClub,
                configura esta app como handler preferido para nuestro dominio.
            </p>
            <div style="display: flex; gap: 8px;">
                <button id="deep-links-skip" style="
                    flex: 1;
                    padding: 10px;
                    border: 1px solid #ddd;
                    background: white;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    color: #666;
                ">Más Tarde</button>
                <button id="deep-links-confirm" style="
                    flex: 1;
                    padding: 10px;
                    border: none;
                    background: #007AFF;
                    color: white;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                ">Continuar</button>
            </div>
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                #deep-links-skip:active { background: #f5f5f5; }
                #deep-links-confirm:active { background: #0051d5; }
            </style>
        `;

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        // Event listeners
        document.getElementById('deep-links-skip').addEventListener('click', () => {
            overlay.remove();
            resolve(false);
        });

        document.getElementById('deep-links-confirm').addEventListener('click', () => {
            overlay.remove();
            resolve(true);
        });
    });
}

/**
 * Abrir Settings para configurar handler preferido
 * Intenta múltiples rutas ya que varían según versión y fabricante
 */
async function openDeepLinksSettings() {
    try {
        const { AppLauncher } = await import('@capacitor/app-launcher');

        // Lista de URLs a intentar en orden de preferencia
        const settingsUrls = [
            // Android 12+ (API 31+) - Ruta directa a "Abrir por defecto"
            'android-app://com.android.settings/action/app_open_by_default_settings',

            // Android 11 - Ruta alternativa
            'android-app://com.android.settings/action/manage_app_links',

            // Settings general (último recurso)
            'android-app://com.android.settings',

            // Intent intent via intents
            'intent://com.android.settings/action/app_open_by_default_settings#Intent;action=android.intent.action.VIEW;end',
        ];

        for (const url of settingsUrls) {
            try {
                console.log('[DeepLinks] Intentando abrir:', url);

                // Verificar si se puede abrir
                try {
                    const canOpen = await AppLauncher.canOpenUrl({ url });
                    if (!canOpen.canOpen) {
                        console.log('[DeepLinks] No se puede abrir:', url);
                        continue;
                    }
                } catch (e) {
                    console.log('[DeepLinks] Error verificando URL:', url, e);
                    // Continuar intentando aunque no pueda verificar
                }

                // Intentar abrir
                await AppLauncher.openUrl({ url });
                console.log('[DeepLinks] Abierto exitosamente:', url);
                return;
            } catch (error) {
                console.log('[DeepLinks] Fallo al abrir:', url, error.message);
                continue;
            }
        }

        // Si nada funcionó, mostrar instrucciones manuales
        console.error('[DeepLinks] No se pudo abrir Settings con ningún URL');
        showManualInstructions();
    } catch (error) {
        console.error('[DeepLinks] Error en openDeepLinksSettings:', error);
        showManualInstructions();
    }
}

/**
 * Mostrar instrucciones si no podemos abrir settings automáticamente
 */
function showManualInstructions() {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        overflow-y: auto;
    `;

    const dialog = document.createElement('div');
    dialog.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 380px;
        text-align: left;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        margin: 20px;
    `;

    const userAgent = navigator.userAgent.toLowerCase();
    let deviceType = 'Android';
    if (userAgent.includes('samsung')) {
        deviceType = 'Samsung';
    } else if (userAgent.includes('redmi') || userAgent.includes('xiaomi')) {
        deviceType = 'Xiaomi/Redmi';
    }

    let instructionsHtml = `
        <h2 style="margin: 0 0 16px 0; font-size: 16px; color: #1a1a1a;">Configuración Manual</h2>
        <p style="margin: 0 0 12px 0; font-size: 13px; color: #666; line-height: 1.6;">
            <strong>Dispositivo detectado:</strong> ${deviceType}
        </p>
    `;

    if (deviceType === 'Samsung') {
        instructionsHtml += `
            <p style="margin: 0 0 12px 0; font-size: 13px; color: #666; line-height: 1.6; font-weight: 600;">
                📱 Samsung - Pasos:
            </p>
            <ol style="margin: 0 0 16px 0; padding-left: 20px; font-size: 13px; color: #666; line-height: 1.8;">
                <li>Abre <strong>Configuración</strong></li>
                <li>Ve a <strong>Aplicaciones</strong></li>
                <li>Selecciona <strong>Aplicaciones predeterminadas</strong></li>
                <li>Busca y toca <strong>Abrir vínculos admitidos</strong></li>
                <li>En la lista, busca <strong>app.offsideclub.es</strong></li>
                <li>Toca en <strong>OffsideClub</strong></li>
            </ol>
        `;
    } else if (deviceType === 'Xiaomi/Redmi') {
        instructionsHtml += `
            <p style="margin: 0 0 12px 0; font-size: 13px; color: #666; line-height: 1.6; font-weight: 600;">
                📱 Xiaomi/Redmi - Pasos:
            </p>
            <ol style="margin: 0 0 16px 0; padding-left: 20px; font-size: 13px; color: #666; line-height: 1.8;">
                <li>Abre <strong>Configuración</strong></li>
                <li>Ve a <strong>Aplicaciones</strong> o <strong>Gestor de aplicaciones</strong></li>
                <li>Selecciona <strong>Aplicaciones predeterminadas</strong></li>
                <li>Toca en <strong>Navegador predeterminado</strong> o <strong>Abrir enlaces</strong></li>
                <li>Busca <strong>app.offsideclub.es</strong></li>
                <li>Selecciona <strong>OffsideClub</strong></li>
            </ol>
        `;
    } else {
        instructionsHtml += `
            <p style="margin: 0 0 12px 0; font-size: 13px; color: #666; line-height: 1.6; font-weight: 600;">
                📱 Android - Pasos:
            </p>
            <ol style="margin: 0 0 16px 0; padding-left: 20px; font-size: 13px; color: #666; line-height: 1.8;">
                <li>Abre <strong>Configuración</strong></li>
                <li>Ve a <strong>Aplicaciones</strong></li>
                <li>Selecciona <strong>Aplicaciones predeterminadas</strong></li>
                <li>Toca en <strong>Abrir enlaces</strong> o <strong>Direcciones web admitidas</strong></li>
                <li>Busca <strong>app.offsideclub.es</strong></li>
                <li>Toca en <strong>OffsideClub</strong></li>
            </ol>
        `;
    }

    instructionsHtml += `
        <div style="background: #f0f0f0; border-left: 4px solid #007AFF; padding: 12px; border-radius: 4px; margin: 0 0 16px 0;">
            <p style="margin: 0; font-size: 12px; color: #666;">
                <strong>💡 Nota:</strong> Si no encuentras <strong>app.offsideclub.es</strong> en la lista,
                primero abre un link de invitación en la app para que Android la registre como handler disponible.
            </p>
        </div>
        <button id="close-instructions" style="
            width: 100%;
            padding: 10px;
            border: none;
            background: #007AFF;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        ">Entendido</button>
    `;

    dialog.innerHTML = instructionsHtml;
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);

    document.getElementById('close-instructions').addEventListener('click', () => {
        overlay.remove();
    });
}

export default DeepLinksHandler;
