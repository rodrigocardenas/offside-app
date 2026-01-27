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
    });
} else {
    new DeepLinksHandler();
}

export default DeepLinksHandler;
