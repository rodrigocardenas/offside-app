/**
 * Firebase Notification Service
 * Maneja notificaciones para web y Capacitor (Android/iOS)
 * 
 * Detecta automáticamente el contexto y usa la API apropiada
 */

class FirebaseNotificationService {
    constructor() {
        this.platform = this.detectPlatform();
        this.isCapacitor = window.Capacitor?.isNativePlatform() ?? false;
        this.currentToken = null;
        this.messageHandlers = [];
        
        console.log(`[FirebaseNotificationService] Inicializado en plataforma: ${this.platform}`);
    }

    /**
     * Detecta la plataforma actual
     */
    detectPlatform() {
        if (window.Capacitor?.isNativePlatform?.()) {
            const platform = window.Capacitor.getPlatform();
            return platform === 'android' ? 'android' : platform === 'ios' ? 'ios' : 'web';
        }
        return 'web';
    }

    /**
     * Inicializa el servicio de notificaciones según la plataforma
     */
    async initialize() {
        try {
            if (this.isCapacitor) {
                console.log('[FirebaseNotificationService] Inicializando para Capacitor...');
                await this.initializeCapacitor();
            } else {
                console.log('[FirebaseNotificationService] Inicializando para Web...');
                await this.initializeWeb();
            }
        } catch (error) {
            console.error('[FirebaseNotificationService] Error durante inicialización:', error);
        }
    }

    /**
     * Inicializa Firebase para Web
     */
    async initializeWeb() {
        try {
            // Verificar que Firebase esté disponible
            if (typeof firebase === 'undefined') {
                console.warn('[FirebaseNotificationService] Firebase no está disponible en web');
                return;
            }

            const messaging = firebase.messaging();

            // Manejar mensaje en foreground
            messaging.onMessage((payload) => {
                console.log('[FirebaseNotificationService] Mensaje recibido en foreground:', payload);
                this.handleMessage(payload, 'web');
            });

            // Obtener token
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    const token = await messaging.getToken({
                        vapidKey: window.VAPID_KEY || 'BCrjLJBGzfhQqnXrzZRdT5xHMi5rHMdmkT6-4r2_b0rE-m2X1Q9E_zU3O_L7ZqZyZmQ9q_4V_wR_l8N_t_u_v'
                    });
                    
                    if (token) {
                        await this.registerToken(token, 'web');
                        this.currentToken = token;
                        console.log('[FirebaseNotificationService] Token de web obtenido y registrado');
                    }
                } else {
                    console.warn('[FirebaseNotificationService] Permisos de notificación denegados en web');
                }
            } catch (error) {
                console.error('[FirebaseNotificationService] Error obteniendo token web:', error);
            }
        } catch (error) {
            console.error('[FirebaseNotificationService] Error inicializando web:', error);
        }
    }

    /**
     * Inicializa Firebase para Capacitor (Android/iOS)
     */
    async initializeCapacitor() {
        try {
            // Verificar que el plugin esté disponible
            if (typeof FCM === 'undefined') {
                console.warn('[FirebaseNotificationService] @capacitor-firebase/messaging no disponible');
                return;
            }

            const { Messaging } = FCM;

            // Solicitar permisos de notificación
            const permission = await Messaging.requestPermissions();
            if (permission) {
                console.log('[FirebaseNotificationService] Permisos de notificación concedidos en Capacitor');
            }

            // Obtener token del dispositivo
            try {
                const result = await Messaging.getToken();
                if (result.token) {
                    await this.registerToken(result.token, this.platform);
                    this.currentToken = result.token;
                    console.log(`[FirebaseNotificationService] Token de ${this.platform} obtenido y registrado`);
                }
            } catch (error) {
                console.error('[FirebaseNotificationService] Error obteniendo token Capacitor:', error);
            }

            // Listener para notificaciones en foreground
            const unlistenForeground = await Messaging.addListener(
                'messageReceived',
                (event) => {
                    console.log('[FirebaseNotificationService] Notificación recibida en foreground (Capacitor):', event);
                    this.handleMessage(event.message || event, this.platform);
                }
            );

            // Listener para notificaciones en background
            const unlistenBackground = await Messaging.addListener(
                'notificationActionPerformed',
                (event) => {
                    console.log('[FirebaseNotificationService] Notificación presionada (background):', event);
                    this.handleMessageAction(event, this.platform);
                }
            );

            // Listener para cambios de token (por ejemplo, después de reinstalación)
            const unlistenTokenChanged = await Messaging.addListener(
                'tokenReceived',
                async (event) => {
                    console.log('[FirebaseNotificationService] Token renovado en Capacitor:', event);
                    if (event.token) {
                        await this.registerToken(event.token, this.platform);
                        this.currentToken = event.token;
                    }
                }
            );

            console.log('[FirebaseNotificationService] Listeners de Capacitor registrados exitosamente');
        } catch (error) {
            console.error('[FirebaseNotificationService] Error inicializando Capacitor:', error);
        }
    }

    /**
     * Registra el token en el backend
     */
    async registerToken(token, platform) {
        try {
            // Obtener user_id desde meta tag
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            const userId = userIdMeta ? parseInt(userIdMeta.getAttribute('content')) : null;

            if (!userId) {
                console.warn(`[FirebaseNotificationService] No se encontró user-id en meta tag. Token no será registrado.`);
                return;
            }

            const response = await fetch('/api/push/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    token,
                    platform,
                    user_id: userId,
                    endpoint: window.location.href,
                    public_key: null,
                    auth_token: null
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            console.log(`[FirebaseNotificationService] Token ${platform} registrado en backend`);
        } catch (error) {
            console.error(`[FirebaseNotificationService] Error registrando token ${platform}:`, error);
        }
    }

    /**
     * Maneja mensajes de notificación recibidos
     */
    handleMessage(payload, platform) {
        console.log(`[FirebaseNotificationService] Manejando mensaje de ${platform}:`, payload);

        // Extraer datos según el formato
        const notification = payload.notification || {};
        const data = payload.data || {};

        // Crear notificación visual si no está en background
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title || 'Nueva notificación', {
                body: notification.body,
                icon: notification.icon || '/images/logo_white_bg.png',
                tag: data.group_id || 'offside-notification',
                data: data
            }).addEventListener('click', () => {
                window.focus();
                if (data.link) {
                    window.location.href = data.link;
                }
            });
        }

        // Ejecutar handlers personalizados registrados
        this.messageHandlers.forEach(handler => {
            try {
                handler({
                    title: notification.title,
                    body: notification.body,
                    data: data,
                    platform: platform
                });
            } catch (error) {
                console.error('[FirebaseNotificationService] Error en handler:', error);
            }
        });
    }

    /**
     * Maneja acciones de notificaciones en background (Capacitor)
     */
    handleMessageAction(event, platform) {
        console.log(`[FirebaseNotificationService] Acción de notificación en ${platform}:`, event);

        // Navegar si hay link
        const link = event.notification?.data?.link;
        if (link) {
            window.location.href = link;
        }

        // Ejecutar handlers personalizados
        this.messageHandlers.forEach(handler => {
            try {
                handler({
                    title: event.notification?.title,
                    body: event.notification?.body,
                    data: event.notification?.data,
                    platform: platform,
                    action: 'clicked'
                });
            } catch (error) {
                console.error('[FirebaseNotificationService] Error en handler de acción:', error);
            }
        });
    }

    /**
     * Registra un handler para mensajes de notificación
     * Útil para aplicaciones que necesitan reaccionar a notificaciones
     */
    onMessage(callback) {
        this.messageHandlers.push(callback);
    }

    /**
     * Obtiene el token actual
     */
    getToken() {
        return this.currentToken;
    }

    /**
     * Obtiene la plataforma actual
     */
    getPlatform() {
        return this.platform;
    }

    /**
     * Verifica si se ejecuta en Capacitor
     */
    isRunningInCapacitor() {
        return this.isCapacitor;
    }
}

// Crear instancia global
const firebaseNotificationService = new FirebaseNotificationService();

// Auto-inicializar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        firebaseNotificationService.initialize();
    });
} else {
    firebaseNotificationService.initialize();
}
