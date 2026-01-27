/**
 * Pull-to-Refresh Implementation para Offside Club
 * Proporciona gesto de arrastrar desde arriba para recargar contenido
 * Compatible con Capacitor (mobile) y web browsers
 */

class OffsidePullToRefresh {
    constructor(options = {}) {
        this.options = {
            threshold: 80,  // Pixels para desencadenar refresh
            timeout: 2000,  // Timeout para la recarga
            onRefresh: null, // Callback personalizado
            ...options
        };

        this.container = document.documentElement;
        this.initialY = 0;
        this.currentY = 0;
        this.pulling = false;
        this.refreshing = false;

        this.init();
    }

    init() {
        // Solo inicializar en dispositivos móviles o con Capacitor
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        const isCapacitor = typeof window.Capacitor !== 'undefined';

        if (!isMobile && !isCapacitor) {
            console.log('[PullToRefresh] Deshabilitado en desktop');
            return;
        }

        this.createUI();
        this.attachListeners();
        console.log('[PullToRefresh] Inicializado correctamente');
    }

    createUI() {
        // Crear elemento visual para el pull-to-refresh
        const refreshIndicator = document.createElement('div');
        refreshIndicator.id = 'offside-pull-indicator';
        refreshIndicator.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #17b796, #00deb0);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 40;
            transition: height 0.3s ease-out;
        `;

        const icon = document.createElement('div');
        icon.style.cssText = `
            color: #003b2f;
            font-size: 24px;
            font-weight: bold;
            transform: rotateZ(0deg);
            transition: transform 0.3s ease;
        `;
        icon.innerHTML = '<i class="fas fa-arrow-down"></i>';

        refreshIndicator.appendChild(icon);
        document.body.insertBefore(refreshIndicator, document.body.firstChild);

        this.indicator = refreshIndicator;
        this.icon = icon.querySelector('i');
    }

    attachListeners() {
        document.addEventListener('touchstart', (e) => this.handleTouchStart(e), false);
        document.addEventListener('touchmove', (e) => this.handleTouchMove(e), false);
        document.addEventListener('touchend', (e) => this.handleTouchEnd(e), false);

        // Soporte para mouse en desarrollo
        if (/localhost|127.0.0.1/.test(window.location.hostname)) {
            document.addEventListener('mousedown', (e) => this.handleTouchStart(e), false);
            document.addEventListener('mousemove', (e) => this.handleTouchMove(e), false);
            document.addEventListener('mouseup', (e) => this.handleTouchEnd(e), false);
        }
    }

    handleTouchStart(e) {
        // Solo si estamos al tope del scroll
        if (window.scrollY === 0 && !this.refreshing) {
            const touch = e.touches ? e.touches[0] : e;
            this.initialY = touch.clientY;
            this.pulling = true;
        } else {
            this.pulling = false;
        }
    }

    handleTouchMove(e) {
        if (!this.pulling || this.refreshing) return;

        const touch = e.touches ? e.touches[0] : e;
        this.currentY = touch.clientY - this.initialY;

        if (this.currentY > 0) {
            e.preventDefault();

            // Actualizar altura del indicador
            const height = Math.min(this.currentY, this.options.threshold);
            this.indicator.style.height = height + 'px';

            // Rotar icono según progreso
            const rotation = (height / this.options.threshold) * 180;
            this.icon.style.transform = `rotateZ(${rotation}deg)`;

            // Cambiar color cuando se alcanza el threshold
            if (height === this.options.threshold) {
                this.indicator.style.background = 'linear-gradient(135deg, #00deb0, #17b796)';
            } else {
                this.indicator.style.background = 'linear-gradient(135deg, #17b796, #00deb0)';
            }
        }
    }

    handleTouchEnd(e) {
        if (!this.pulling) return;

        this.pulling = false;

        if (this.currentY >= this.options.threshold) {
            this.triggerRefresh();
        } else {
            // Animar collapse del indicador
            this.indicator.style.height = '0px';
            this.icon.style.transform = 'rotateZ(0deg)';
            this.indicator.style.background = 'linear-gradient(135deg, #17b796, #00deb0)';
        }

        this.initialY = 0;
        this.currentY = 0;
    }

    async triggerRefresh() {
        if (this.refreshing) return;

        this.refreshing = true;
        this.indicator.style.height = this.options.threshold + 'px';
        this.icon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        console.log('[PullToRefresh] Refresh trigger');

        try {
            if (this.options.onRefresh) {
                // Callback personalizado
                await this.options.onRefresh();
            } else {
                // Comportamiento por defecto: recargar la página
                await this.defaultRefresh();
            }

            // Éxito
            this.icon.innerHTML = '<i class="fas fa-check" style="color: #003b2f;"></i>';
            console.log('[PullToRefresh] Refresh completed');
            await new Promise(resolve => setTimeout(resolve, 500));
        } catch (error) {
            console.error('[PullToRefresh] Error:', error);
            this.icon.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #ff6b6b;"></i>';
        } finally {
            // Colapsar indicador
            this.indicator.style.height = '0px';
            this.icon.innerHTML = '<i class="fas fa-arrow-down"></i>';
            this.icon.style.transform = 'rotateZ(0deg)';
            this.indicator.style.background = 'linear-gradient(135deg, #17b796, #00deb0)';
            this.refreshing = false;
        }
    }

    async defaultRefresh() {
        // Esperar 2 segundos simulando carga
        await new Promise(resolve => setTimeout(resolve, this.options.timeout));

        // Recargar datos
        this.reloadPageContent();
    }

    reloadPageContent() {
        // Opción 1: Recargar toda la página (más simple)
        // window.location.reload();

        // Opción 2: Limpiar cache y recargar via AJAX (más eficiente)
        this.clearCacheAndReload();
    }

    async clearCacheAndReload() {
        try {
            // Llamar endpoint para limpiar cache del usuario
            const response = await fetch('/api/cache/clear-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            if (response.ok) {
                // Recargar la página
                window.location.reload();
            } else {
                // Si falla, recargar igual
                window.location.reload();
            }
        } catch (error) {
            console.error('[PullToRefresh] Error limpiando cache:', error);
            // Recargar igual
            window.location.reload();
        }
    }

    /**
     * Detener el pull-to-refresh (útil para cuando cambias de vista)
     */
    destroy() {
        this.pulling = false;
        this.refreshing = false;
        if (this.indicator) {
            this.indicator.remove();
        }
    }
}

// Inicializar automáticamente cuando el módulo se cargue
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initPullToRefresh();
    });
} else {
    initPullToRefresh();
}

function initPullToRefresh() {
    // Solo inicializar en mobile o Capacitor
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const isCapacitor = typeof window.Capacitor !== 'undefined';

    if (isMobile || isCapacitor) {
        window.offsidePullToRefresh = new OffsidePullToRefresh({
            threshold: 80,
            timeout: 2000,
        });
    }
}

export default OffsidePullToRefresh;
