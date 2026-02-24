# PASOS 3 & 4: Permission Service + Token Service

**Fecha:** 19 de Febrero de 2026  
**Status:** ✅ COMPLETADO  
**Archivos Creados:**
- `public/js/permission-service.js` (PASO 3)
- `public/js/token-service.js` (PASO 4)

---

## PASO 3: Permission Service (Android 13+)

### 📋 Archivo: `permission-service.js`

**Propósito:** Centralizar la lógica de solicitud de permisos POST_NOTIFICATIONS con manejo inteligente de rechazos.

### 🎯 Características

#### 1. **Solicitud de Permisos**
```javascript
const result = await window.requestNotificationPermission();
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Notification permission granted",
  "data": { "display": "granted" }
}
```

**Respuesta denegada:**
```json
{
  "success": false,
  "error": "PERMISSION_DENIED",
  "message": "User denied notification permission",
  "data": {
    "display": "denied",
    "denialCount": 1,
    "maxRetries": 2,
    "canRetry": true
  }
}
```

#### 2. **Control de Rechazos (Retry Logic)**

| Escenario | Comportamiento |
|-----------|---|
| Primer rechazo | Pueden reintentar (denialCount: 1) |
| Segundo rechazo | Pueden reintentar (denialCount: 2) |
| Tercer rechazo | Máximo alcanzado, sin más reintentos |
| En cooldown | Espera 60 segundos antes de reintentar |

**Códigos de error posibles:**
- `PERMISSION_DENIED` - Usuario negó explícitamente
- `COOLDOWN` - En período de espera (60s)
- `MAX_RETRIES_EXCEEDED` - Max 2 rechazos alcanzado
- `PLUGIN_UNAVAILABLE` - Plugin no disponible
- `PERMISSION_REQUEST_ERROR` - Error durante solicitud

#### 3. **Persistencia de Estado**

El servicio guarda en localStorage:
```javascript
localStorage.getItem('fcm_permission_denial_count')  // Número de rechazos
localStorage.getItem('fcm_permission_last_denial')   // Fecha último rechazo
```

Esto permite:
- No spamear al usuario con solicitudes
- Mantener el estado entre recargas de página
- Implementer la lógica de cooldown

#### 4. **API Global**

```javascript
// Solicitar permiso (con reintentos inteligentes)
window.requestNotificationPermission()

// Verificar estado actual (sin solicitar)
window.checkNotificationPermission()

// Ver estado del servicio
window.getPermissionServiceState()

// Resetear tracking (para testing)
window.resetPermissionTracking()
```

#### 5. **Configuración Personalizable**

```javascript
window.PermissionService.configure({
    maxRetries: 3,           // Aumentar max intentos
    cooldownMs: 120000,      // Cambiar cooldown a 2 min
    retryDelayMs: 5000       // Cambiar delay entre intentos
});
```

### 📝 Estado Retornado

```javascript
window.getPermissionServiceState()
// {
//   "permissionDeniedCount": 1,
//   "lastDenialTime": "2026-02-19T14:30:00Z",
//   "isInCooldown": true,
//   "maxRetries": 2
// }
```

### 🧪 Ejemplo de Uso en HTML

```html
<button id="enableNotifications">Enable Notifications</button>

<script>
document.getElementById('enableNotifications').addEventListener('click', async () => {
    const result = await window.requestNotificationPermission();
    
    if (result.success) {
        alert('✅ Notifications enabled!');
    } else {
        const msg = window.PermissionService.getHumanReadableMessage(result);
        alert(`⚠️ ${msg}`);
    }
});
</script>
```

---

## PASO 4: Token Service

### 📋 Archivo: `token-service.js`

**Propósito:** Centralizar la gestión del token FCM con sincronización con backend, rotación automática y validación.

### 🎯 Características

#### 1. **Obtención y Almacenamiento de Token**

```javascript
window.TokenService.setToken(token, markedSyncedWithBackend);
```

**Características:**
- ✅ Validación de formato (> 100 chars)
- ✅ Almacenamiento en localStorage
- ✅ Timestamp de obtención
- ✅ Detección de cambios (rotación de Firebase)

#### 2. **Sincronización con Backend**

```javascript
const result = await window.syncTokenWithBackend();
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Token synced",
  "data": {
    "success": true,
    "message": "Token registrado correctamente"
  }
}
```

**Validaciones:**
- ✅ HTTP response status
- ✅ JSON response parsing
- ✅ `success` flag en respuesta
- ✅ User ID presente

#### 3. **Detección de Token Obsoleto**

```javascript
window.getTokenServiceState()
// {
//   "hasToken": true,
//   "tokenPreview": "dYnL4aD7Xo...",
//   "obtainedAt": "2026-02-10T14:30:00Z",
//   "lastSyncedAt": "2026-02-19T14:30:00Z",
//   "isValid": true,
//   "isStale": false,
//   "isDaysOld": 9.5,
//   "syncedWithBackend": true,
//   "autoRefreshEnabled": false
// }
```

**Token se considera obsoleto si:**
- Tiene más de 30 días
- Viene de una rotación de Firebase

#### 4. **Auto-Refresh Automático**

```javascript
// Inicia verificación cada 1 hora
window.startTokenAutoRefresh();

// Si token está obsoleto, lo refresca automáticamente
// Y lo resincroniza con backend

// Detener auto-refresh
window.stopTokenAutoRefresh();
```

#### 5. **Rotación de Tokens (Firebase)**

Cuando Firebase rota el token (cada ~2 semanas), el servicio:

1. **Detecta** el nuevo token desde `tokenRefreshed` evento
2. **Valida** el nuevo token
3. **Guarda** en localStorage
4. **Resincroniza** con backend automáticamente
5. **Dispara** custom event `tokenChanged`

#### 6. **API Global**

```javascript
// Obtener token actual
window.getDeviceToken()

// Sincronizar con backend
window.syncTokenWithBackend()

// Force refresh desde plugin
window.forceTokenRefresh()

// Ver estado
window.getTokenServiceState()

// Auto-refresh
window.startTokenAutoRefresh()
window.stopTokenAutoRefresh()

// Clear token
window.clearDeviceToken()
```

### 📝 Custom Events

```javascript
// Cuando se obtiene token por primera vez
window.addEventListener('tokenSet', (event) => {
    console.log('Token:', event.detail.token);
});

// Cuando Firebase rota el token
window.addEventListener('tokenChanged', (event) => {
    console.log('Viejo:', event.detail.oldToken.substring(0, 20));
    console.log('Nuevo:', event.detail.newToken.substring(0, 20));
});
```

### 🧪 Ejemplo de Uso Completo

```javascript
// En tu app.js o main.js

// 1. Obtener token actual (ya guardado)
const currentToken = window.getDeviceToken();
console.log('Current token:', currentToken);

// 2. Si no hay token o está obsoleto, forzar refresh
if (!currentToken || window.getTokenServiceState()?.isStale) {
    const refreshResult = await window.forceTokenRefresh();
    if (refreshResult.success) {
        console.log('✅ Token refreshed and synced');
    }
}

// 3. Iniciar auto-refresh (verifica cada hora)
window.startTokenAutoRefresh();

// 4. Escuchar cambios de token
window.addEventListener('tokenChanged', async (e) => {
    // Notificar al usuario si es necesario
    console.log('Token was rotated. New token synced.');
});
```

---

## 🔄 Flujo Completo de Integración

```
┌─────────────────────────────────────────────────────────────┐
│                     APP INITIALIZATION                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  1. Firebase Messaging Service Loads                         │
│     └─ Detecta plataforma (Android/iOS/Web)                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Permission Service Loaded                               │
│     └─ Restaura estado de rechazos anteriores              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  3. Token Service Loaded                                    │
│     └─ Carga token guardado de localStorage               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Firebase.initialize() llamado                           │
│     ├─ requestNotificationPermission()                      │
│     │  └─ Solicita POST_NOTIFICATIONS                      │
│     │     ├─ Si granted → continúa                         │
│     │     └─ Si denied → 1er rechazo, cooldown 60s        │
│     │                                                       │
│     ├─ requestTokenFromNative()                            │
│     │  └─ Obtiene token FCM                                │
│     │     └─ TokenService.setToken(token)                  │
│     │                                                       │
│     └─ setupMessageListeners()                             │
│        ├─ messageReceived → dispara pushMessageReceived    │
│        └─ tokenReceived → TokenService detecta rotación   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  5. registerTokenWithBackend()                              │
│     └─ POST /api/push/token con token + platform + user_id │
│        └─ TokenService.lastSyncedAt = now                  │
│           syncedWithBackend = true                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  6. Auto-refresh Iniciado (Opcional)                        │
│     └─ Cada 1 hora: verifica si token es obsoleto          │
│        ├─ Si es viejo → forceTokenRefresh()               │
│        └─ Si nuevo → resincroniza con backend             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Comparación de Estados

### Firebase Messaging Service
- Gestiona: conexión con plugin nativo
- Responsable: inicialización, listeners de mensajes
- Scope: lifecycle general

### Permission Service
- Gestiona: solicitud de permisos POST_NOTIFICATIONS
- Responsable: rechazos, reintentos, cooldown
- Scope: interacción con usuario

### Token Service
- Gestiona: obtención, almacenamiento, sincronización de token
- Responsable: validación, rotación, backend sync
- Scope: ciclo de vida del token

---

## ✅ Checklist PASOS 3 & 4

**PASO 3: Permission Service**
- ✅ Solicitud de permisos POST_NOTIFICATIONS
- ✅ Control de rechazos con contador
- ✅ Cooldown después de rechazo
- ✅ Max retries (2)
- ✅ Persistencia en localStorage
- ✅ Código de errores específicos
- ✅ Mensajes amigables para UI
- ✅ API global (4 funciones)

**PASO 4: Token Service**
- ✅ Obtención y almacenamiento de token
- ✅ Sincronización con backend
- ✅ Detección de token obsoleto (30 días)
- ✅ Detección de rotación de Firebase
- ✅ Auto-refresh cada 1 hora
- ✅ Custom events (tokenSet, tokenChanged)
- ✅ Persistencia en localStorage
- ✅ API global (7 funciones)

---

## 🎯 Próximos Pasos

- ✅ PASO 1: Análisis
- ✅ PASO 2: Refactorización JS
- ✅ **PASO 3: Permission Service**
- ✅ **PASO 4: Token Service**
- ⏳ **PASO 7: Migrar FCMService a HTTP v1** (CRÍTICO PARA BACKEND)
- ⏳ PASO 8-10: Configuración Android

---

**Generado:** 2026-02-19  
**Rama:** `feature/firebase-android-fix`  
**Versión Permission Service:** 1.0  
**Versión Token Service:** 1.0
