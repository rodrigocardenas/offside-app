{{-- 
    Firebase Cloud Messaging Initialization Component
    PASO 10: Blade View para Inicializar Notificaciones Push
    
    Uso en cualquier página:
    @include('components.firebase-messaging-init')
    
    o en layout base:
    @stackPush('scripts')
        @include('components.firebase-messaging-init')
    @endStackPush
--}}

@if(auth()->check() && config('app.enable_fcm_notifications', true))
    {{-- Meta tags requeridos para autenticación y contexto --}}
    <meta name="user-id" content="{{ auth()->id() }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    
    {{-- Firebase Cloud Messaging Services (PASO 2, 3, 4) --}}
    <script src="{{ asset('js/firebase-messaging-native.js') }}" defer></script>
    <script src="{{ asset('js/permission-service.js') }}" defer></script>
    <script src="{{ asset('js/token-service.js') }}" defer></script>
    
    {{-- Auto-initialization script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Log: Inicialización de notificaciones push
            console.log('📱 Firebase Messaging - Inicializando...');
            
            // Verificar que los servicios estén disponibles
            if (typeof window.initializePushNotifications === 'undefined') {
                console.warn('⚠️  Firebase Messaging service no disponible. Esperando scripts...');
                setTimeout(() => {
                    if (typeof window.initializePushNotifications !== 'undefined') {
                        window.initializePushNotifications();
                    }
                }, 1000);
                return;
            }
            
            // Inicializar notificaciones push
            window.initializePushNotifications();
            
            console.log('✅ Notificaciones push inicializadas');
            console.log('📊 Estado:', window.getPushNotificationState?.());
        });
        
        {{-- Escuchar eventos de notificaciones (opcional) --}}
        document.addEventListener('pushMessageReceived', function(event) {
            console.log('📬 Nueva notificación recibida:', event.detail);
            // Aquí puedes hacer algo cuando se reciba una notificación
            // Por ejemplo: reproducir sonido, mostrar toast, etc
        });
        
        document.addEventListener('pushTokenRefreshed', function(event) {
            console.log('🔄 Token de Firebase renovado');
        });
        
        document.addEventListener('tokenChanged', function(event) {
            console.log('🔀 Token ha cambiado:', event.detail);
        });
    </script>
@else
    {{-- Usuario no autenticado o notificaciones deshabilitadas --}}
    @if(!auth()->check())
        {{-- No incluir scripts si no está autenticado --}}
    @else
        {{-- Show warning if FCM is disabled --}}
        <script>
            console.log('ℹ️  Firebase Cloud Messaging está deshabilitado en la configuración');
        </script>
    @endif
@endif
