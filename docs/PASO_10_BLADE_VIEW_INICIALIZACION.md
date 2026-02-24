# PASO 10: Blade View para Inicialización de Firebase Cloud Messaging

**Estado:** ✅ COMPLETADO
**Fecha:** 2026-02-19
**Rama:** `feature/firebase-android-fix`
**Archivos:**
- `resources/views/components/firebase-messaging-init.blade.php` ✅
- `config/app.php` (modificado) ✅

---

## 🎯 Objetivo

Crear un componente Blade reutilizable que incluya y inicialice automáticamente los tres servicios JavaScript de Firebase Cloud Messaging (firebase-messaging-native.js, permission-service.js, token-service.js) en cualquier página de la aplicación.

**Beneficio:** Una línea de código en cualquier Blade template inicializa todo:
```blade
@include('components.firebase-messaging-init')
```

---

## 📋 Qué Incluye El Componente

### 1. **Meta Tags Requeridos**
```html
<meta name="user-id" content="{{ auth()->id() }}" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
```

**Usado por:**
- `token-service.js` - Obtiene user_id para sincronizar con backend
- `firebase-messaging-native.js` - Obtiene CSRF token para POST a `/api/push/token`

### 2. **Scripts de Servicios** (PASOS 2, 3, 4)
```html
<script src="{{ asset('js/firebase-messaging-native.js') }}" defer></script>
<script src="{{ asset('js/permission-service.js') }}" defer></script>
<script src="{{ asset('js/token-service.js') }}" defer></script>
```

**Orden de carga:**
- `firebase-messaging-native.js` - Principal, gestiona plugin Capacitor
- `permission-service.js` - Maneja permisos Android 13+
- `token-service.js` - Gestiona ciclo de vida del token

**`defer` attribute:**
- Scripts se cargan en paralelo (no bloquean HTML)
- Se ejecutan después de que carga el DOM
- Orden garantizada

### 3. **Auto-Inicialización**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    window.initializePushNotifications();
});
```

**Ejecuta:**
- Solicita permiso POST_NOTIFICATIONS (Android 13+)
- Obtiene token FCM
- Registra token con backend
- Configura listeners de mensajes

### 4. **Event Listeners** (Opcional)
```javascript
document.addEventListener('pushMessageReceived', function(event) {
    // Ejecuta cuando se recibe una notificación en foreground
});

document.addEventListener('pushTokenRefreshed', function(event) {
    // Ejecuta cuando Firebase rota el token
});

document.addEventListener('tokenChanged', function(event) {
    // Ejecuta cuando el token local cambia
});
```

---

## 🚀 Cómo Usar

### Opción 1: En Layout Base (RECOMENDADO)

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
    <!-- ... otros styles/scripts ... -->
</head>
<body>
    {{-- Contenido principal --}}
    @yield('content')
    
    {{-- Inicializar notificaciones push --}}
    @include('components.firebase-messaging-init')
</body>
</html>
```

**Ventaja:** Se inicializa automáticamente en TODAS las páginas

### Opción 2: En Página Específica

```blade
{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="dashboard">
        {{-- Contenido --}}
    </div>
    
    @include('components.firebase-messaging-init')
@endsection
```

**Ventaja:** Control granular de dónde inicializar

### Opción 3: En Stack de Scripts

```blade
{{-- En layout --}}
@stack('scripts')

{{-- En página --}}
@push('scripts')
    @include('components.firebase-messaging-init')
@endPush
```

**Ventaja:** Permite múltiples componentes en el mismo stack

---

## ⚙️ Configuración

### Habilitar/Deshabilitar FCM

**En `.env`:**
```env
# Habilitar notificaciones push
ENABLE_FCM_NOTIFICATIONS=true

# Deshabilitar en desarrollo o testing
ENABLE_FCM_NOTIFICATIONS=false
```

**En código:**
```php
// config/app.php
'enable_fcm_notifications' => env('ENABLE_FCM_NOTIFICATIONS', true),
```

**Comportamiento:**
- Si `ENABLE_FCM_NOTIFICATIONS=true` → Los scripts se incluyen
- Si `ENABLE_FCM_NOTIFICATIONS=false` → Se ignora el componente

### Solo para Usuarios Autenticados

El componente verifica:
```blade
@if(auth()->check() && config('app.enable_fcm_notifications', true))
    {{-- Incluir scripts --}}
@endif
```

**Protección:**
- ✅ No carga scripts si usuario no está autenticado
- ✅ No intenta obtener `auth()->id()` si no hay usuario
- ✅ Evita errores de CSRF token

---

## 📊 Flujo Completo: Desde Blade Hasta Notificación

```
User visita página
    ↓
Blade renderiza @include('components.firebase-messaging-init')
    ↓
Se incluyen meta tags: user-id, csrf-token
    ↓
Se cargan 3 scripts con defer
    ↓
HTML termina de cargar
    ↓
DOMContentLoaded dispara window.initializePushNotifications()
    ↓
firebase-messaging-native.js:
  1. getFirebaseMessagingPlugin()
  2. (Android) requestPermission(POST_NOTIFICATIONS)
  3. getToken() del plugin
  4. registerTokenWithBackend() → POST /api/push/token
  5. setupMessageListeners()
    ↓
permission-service.js:
  - Tracking de rechazos de permiso
  - Cooldown logic
    ↓
token-service.js:
  - localStorage: guardar token
  - Detección de staleness (30 días)
  - Auto-refresh (1 hora)
    ↓
Backend FCMService:
  - Notificación guardada en DB
    ↓
Firebase Cloud Messaging:
  - Envía a dispositivo
    ↓
App recibe en foreground:
  - setupMessageListeners() → pushMessageReceived event
    ↓
Event listener personalizado:
  - Reproduce sonido, muestra toast, etc
```

---

## 🔍 Debugging

### Ver Logs en Console

```javascript
// En DevTools Console
window.getPushNotificationLogs()
// Retorna array de logs con texto, tipo, timestamp

window.getPushNotificationState()
// Muestra estado actual del servicio

window.getTokenServiceState()
// Muestra estado del token

window.getPermissionServiceState()
// Muestra estado de permisos
```

### Habilitar Logs Manuales

```javascript
// En consola del navegador
JSON.parse(localStorage.getItem('push_notification_logs'))
// Ver todos los logs guardados

localStorage.clear()
// Limpiar todo (cuidado con esto)
```

---

## 🧪 Verificación

### 1. Verificar Meta Tags

```bash
# Abrir DevTools (F12) → Elements
# Buscar:
<meta name="user-id" content="1" />
<meta name="csrf-token" content="..." />
```

### 2. Verificar Scripts Cargados

```bash
# DevTools → Network → Filter by XHR o JS
# Buscar:
firebase-messaging-native.js ✅
permission-service.js ✅
token-service.js ✅
```

### 3. Ver Inicialización en Consola

```bash
# DevTools → Console (recargar página)
# Buscar:
📱 Firebase Messaging - Inicializando...
✅ Notificaciones push inicializadas
📊 Estado: { initialized: true, ... }
```

### 4. Verificar Token en Base de Datos

```bash
# En terminal
php artisan tinker
>>> $user = User::find(1);
>>> $user->pushSubscriptions;
Illuminate\Database\Eloquent\Collection {
  all: [
    Eloquent\Collection: {id: 1, user_id: 1, device_token: "...", platform: "android"}
  ]
}
```

---

## 📝 Personalización

### Cambiar Auto-Inicialización

**Deshabilitarla si quieres inicializar manualmente:**

```blade
{{-- Cambiar this en el componente: --}}
@if(false) {{-- Deshabilitar auto-init --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.initializePushNotifications();
        });
    </script>
@endif

{{-- Y luego, inicializar manualmente cuando necesites: --}}
<button onclick="window.initializePushNotifications()">
    Enable Notifications
</button>
```

### Personalizar Handlers de Eventos

```blade
<script>
// Override del manejador de notificaciones recibidas
document.addEventListener('pushMessageReceived', function(event) {
    const {title, body, data} = event.detail;
    
    // Reproducir sonido custom
    const audio = new Audio('/sounds/notification.mp3');
    audio.play();
    
    // Mostrar toast custom
    showToast(title, body);
    
    // Analítica
    trackEvent('push_notification_received', {title, body});
});
</script>
```

### Agregar Retardo a Inicialización

```blade
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Esperar 2 segundos antes de inicializar
    setTimeout(() => {
        window.initializePushNotifications();
    }, 2000);
});
</script>
```

---

## ⚠️ Errores Comunes

### "initializePushNotifications is not defined"
```
❌ Problema: Scripts no cargaron
✅ Solución: 
   - Verificar que los archivos .js existen en public/js/
   - Verificar que {{ asset() }} retorna URL correcta
   - Abrir DevTools → Network → ver si faltan requests
```

### "user-id meta tag is missing"
```
❌ Problema: auth()->id() retorna null
✅ Solución:
   - Verificar que el usuario está autenticado
   - El componente solo incluye si auth()->check()
```

### "CSRF token mismatch"
```
❌ Problema: csrf_token() no se incluye correctamente
✅ Solución:
   - Verificar que {{ csrf_token() }} está en meta tag
   - No está entre comillas extra
   - La página renderiza Blade correctamente
```

### "Platform is not defined"
```
❌ Problema: token-service.js obtiene window.location.href incorrectamente
✅ Solución:
   - Abrir DevTools → Console → window.location.href
   - Verificar que se detecte la plataforma correctamente
```

---

## 📦 Resumen de Cambios

| Archivo | Acción | Líneas |
|---------|--------|--------|
| `firebase-messaging-init.blade.php` | Creado | 67 |
| `config/app.php` | Modificado | +8 |

**Total:** 2 archivos, 75 líneas

---

## ✨ Conclusión

**PASO 10 ✅ COMPLETADO - PROYECTO FINALIZADO**

Componente Blade creado que:
- ✅ Incluye automáticamente los 3 servicios JS
- ✅ Proporciona meta tags (user-id, csrf-token)
- ✅ Auto-inicializa en DOMContentLoaded
- ✅ Listeners de eventos para notificaciones
- ✅ Control de configuración con ENABLE_FCM_NOTIFICATIONS
- ✅ Solo para usuarios autenticados

---

## 🎉 RESUMEN FINAL: REFACTORING FIREBASE COMPLETADO

```
██████████████████████████████  100% (10/10 PASOS) ✅
├─ PASO 1-4:  Frontend JavaScript Services        ✅
├─ PASO 5-6:  Omitidos (pre-implementados)
├─ PASO 7:    FCMService HTTP v1                 ✅
├─ PASO 8:    AndroidManifest + Metadatos         ✅
├─ PASO 9:    NotificationChannelManager           ✅
└─ PASO 10:   Blade View de Inicialización        ✅
```

### Tecnologías Implementadas:
- ✅ **Frontend:** JavaScript vanilla + Capacitor 6 bridge
- ✅ **Backend:** Laravel + Kreait Firebase SDK + HTTP v1 API
- ✅ **Android:** Manifest metadata + NotificationManager + Channels
- ✅ **iOS:** Compatible (APNS payload incluido)
- ✅ **Web:** Compatible (WebPush payload incluido)

### Características Completadas:
- ✅ Soporte multiplataforma (web, android, iOS)
- ✅ Android 13+ permission handling
- ✅ Token lifecycle management (30 días de staleness)
- ✅ Auto-refresh cada hora
- ✅ Rotation detection
- ✅ Notification channels (Android 8.0+)
- ✅ Comprehensive error handling y logging
- ✅ localStorage persistence
- ✅ Custom events dispatching

---

### 🚀 Próximos Pasos Recomendados:

1. **Testing:**
   ```bash
   npm run build && ./gradlew assembleDebug
   # Instalar en dispositivo
   # Autorizar POST_NOTIFICATIONS
   # Enviar notificación de prueba
   ```

2. **Merge a main:**
   ```bash
   git checkout main
   git merge feature/firebase-android-fix
   git push origin main
   ```

3. **Deploy a Producción:**
   ```bash
   # Ver deploy.sh en scripts/
   ./scripts/deploy.sh
   ```

4. **Monitoreo:**
   - Verificar logs: `storage/logs/laravel.log`
   - DevTools Console en dispositivo
   - Firebase Console → Cloud Messaging → Estadísticas

---

**Documentación Generada:** 10 archivos .md en `docs/PASO_*.md`  
**Código Implementado:** 13 archivos creados/modificados  
**Commits:** 10 commits en rama `feature/firebase-android-fix`  
**Tiempo Total:** ~75 minutos (4/8 horas de trabajo)

---

**Ver referencias completas,  commits, y documentación en:**
- Rama: `git log feature/firebase-android-fix --oneline`
- Docs: `ls docs/PASO_*.md | wc -l` → 10 archivos
- Files: `git diff main..feature/firebase-android-fix --stat`

