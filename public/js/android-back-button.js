/**
 * Manejador del botón back de Android para Capacitor
 * Sincroniza el comportamiento nativo de Android con el historial del navegador
 */

export class AndroidBackButtonHandler {
    constructor() {
        this.isInitialized = false;
    }

    /**
     * Inicializar el manejador del botón back
     */
    async init() {
        // Solo inicializar en Capacitor/Android
        if (!this.isCapacitorApp()) {
            console.log('[AndroidBackButton] No estamos en Capacitor, no inicializando');
            return;
        }

        try {
            // Obtener el módulo de Capacitor dinamicamente
            const { App } = window.Capacitor.Plugins || {};

            if (!App) {
                console.error('[AndroidBackButton] App plugin no disponible');
                return;
            }

            // Escuchar el evento del botón back
            App.addListener('backButton', async (event) => {
                await this.handleBackButton();
            });

            this.isInitialized = true;
            console.log('[AndroidBackButton] Manejador inicializado correctamente');
        } catch (error) {
            console.error('[AndroidBackButton] Error al inicializar:', error);
        }
    }

    /**
     * Detectar si la app está corriendo en Capacitor
     */
    isCapacitorApp() {
        return typeof window.Capacitor !== 'undefined' &&
               typeof window.Capacitor.isNativePlatform === 'function' &&
               window.Capacitor.isNativePlatform();
    }

    /**
     * Manejar el botón back
     */
    async handleBackButton() {
        console.log('[AndroidBackButton] Back button presionado. History length:', window.history.length);

        // Verificar si el historial del navegador tiene más de 1 entrada
        if (window.history.length > 1) {
            console.log('[AndroidBackButton] Navegando atrás');
            // Usar history.back() para navegar a la pantalla anterior
            window.history.back();
        } else {
            console.log('[AndroidBackButton] No hay historial, mostrando diálogo de salida');
            // Si no hay historial, mostrar diálogo de confirmación para salir
            this.showExitConfirmDialog();
        }
    }

    /**
     * Mostrar diálogo de confirmación para salir de la app
     */
    async showExitConfirmDialog() {
        const confirmed = confirm('¿Deseas salir de Offside Club?');
        if (confirmed) {
            try {
                const { App } = window.Capacitor.Plugins || {};
                if (App) {
                    // Salir de la aplicación
                    await App.exitApp();
                }
            } catch (error) {
                console.error('[AndroidBackButton] Error al salir:', error);
            }
        }
    }
}
