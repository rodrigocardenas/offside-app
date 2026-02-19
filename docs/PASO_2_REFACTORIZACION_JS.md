# PASO 2: Refactorización de firebase-messaging-native.js

**Fecha:** 19 de Febrero de 2026  
**Status:** ✅ COMPLETADO  
**Archivo:** `public/js/firebase-messaging-native.js`  
**Versión:** 2.0 (Capacitor 6 - Fixed Namespaces)

---

## 🎯 Objetivos Alcanzados

- ✅ Corregir namespace del plugin
- ✅ Agregar listeners para mensajes recibidos
- ✅ Mejorar error handling
- ✅ Validar disponibilidad de plugin
- ✅ Mejorar validación de response del backend
- ✅ Agregar soporte para detección de token rotation
- ✅ Crear API global mejorada

---

## 📝 Cambios Realizados (Detallados)

### 1. **Cabecera de Archivo y Constructor**

```diff
+ VERSION: 2.0 - Capacitor 6 Namespace Fixes + Listeners
+ this.tokenRefreshListener = null;  // Nueva propiedad
+ this.messageListener = null;       // Nueva propiedad
```

**Propósito:** Preparar el servicio para manejar listeners de mensajes

---

### 2. **Método: `getFirebaseMessagingPlugin()`** 🔴 CRÍTICO FIX

**Cambios clave:**

```diff
// INCORRECTO (podría no funcionar):
- if (window.Capacitor?.Plugins?.Messaging)

// CORRECTO (Capacitor 6 namespace):
+ if (window.Capacitor?.Plugins?.FirebaseMessaging)

// Mejorado con logging diferenciado:
+ ✅ Plugin found at: Capacitor.Plugins.FirebaseMessaging (CORRECT)
+ ❌ WARNING: Found Capacitor.Plugins.Messaging (INCORRECT namespace)
+ ❌ Firebase Messaging plugin NOT FOUND at any expected path
```

**Impacto:** Este fue el problema crítico que impedía cargar el plugin

---

### 3. **Método: `requestTokenFromNative()`** - Mejorado

```diff
+ Agregado: setupMessageListeners() call después de obtener token
+ Agregado: Validación de token format (no solo existence)
+ Agregado: Logging con emoji diferenciado
+ Cambio: return object con success flag
```

---

### 4. **NUEVO Método: `setupMessageListeners()`** 📨

```javascript
/**
 * Configura listeners para dos eventos:
 * 1. messageReceived - Mensajes recibidos en foreground
 * 2. tokenReceived - Token rotado por Firebase
 */
async setupMessageListeners() {
    // Usa plugin.addListener() para registrar handlers
    // Maneja ambos eventos: message + token refresh
    // Error handling si listeners no están disponibles
}
```

**Propósito:** 
- Capturar notificaciones que llegan mientras la app está abierta
- Detectar cuando Firebase rota el token
- Permitir que la app reaccione a estos eventos

---

### 5. **NUEVO Método: `handleMessageReceived()`** 📬

```javascript
/**
 * Manejador de mensajes recibidos en foreground
 * 1. Logea detalles del mensaje
 * 2. Dispara custom event "pushMessageReceived"
 * 3. App puede escuchar: window.addEventListener('pushMessageReceived', ...)
 */
handleMessageReceived(message) {
    // Extrae: title, body, data
    // Dispara: new CustomEvent('pushMessageReceived')
}
```

**Ejemplo de uso:**

```html
<script>
window.addEventListener('pushMessageReceived', (event) => {
    console.log('Nueva notificación:', event.detail.title);
    // Actualizar UI, reproducir sonido, etc.
});
</script>
```

---

### 6. **NUEVO Método: `handleTokenRefresh()`** 🔄

```javascript
/**
 * Manejador de token rotation
 * 1. Logea el nuevo token
 * 2. Re-registra el nuevo token con backend
 * 3. Dispara custom event "pushTokenRefreshed"
 */
async handleTokenRefresh(result) {
    // Push nuevo token a: /api/push/token
    // Notifica app: new CustomEvent('pushTokenRefreshed')
}
```

**Propósito:** Mantener sincronizado el token en BD cuando Firebase lo rota

---

### 7. **Métodos: `initializeAndroid()` y `initializeIos()`** - Mejorados

**Cambios:**

```diff
+ Mejor estructura de flujo (4 pasos claros)
+ Logging con emoji diferenciado por plataforma
  🤖 Android
  🍎 iOS
+ Mensajes de diagnóstico más detallados
+ NO retorna false inmediatamente si permission denied
  (sigue adelante, user puede habilitar después)
+ Return object con {success, error, token} format
```

**Ejemplo de nuevo flujo:**

```
🤖 Android: Initializing...
✅ Android: Plugin reference obtained
📋 Android: Checking POST_NOTIFICATIONS permission...
   Status: denied
📲 Android: Permission not granted, requesting from user...
   User response: granted
✅ Android: POST_NOTIFICATIONS permission already granted
🔑 Android: Requesting FCM token from native layer...
✅ Android: Initialization completed successfully
```

---

### 8. **Método: `registerTokenWithBackend()`** - REFACTORIZADO

**Mejoras principales:**

```diff
+ Token format validation (length check)
+ User ID validation with helpful error message
+ Detailed logging de cada paso
+ Response VALIDATION:
  ✅ HTTP status check (response.ok)
  ✅ JSON parsing try/catch
  ✅ success flag check en response
  ✅ Detalles de error del servidor
+ Manejo de Network errors diferenciado
```

**Nueva validación:**

```javascript
// ANTES:
if (response.ok) { ... }

// AHORA:
1. Valida token format
2. Valida user-id meta tag
3. Valida HTTP response (response.ok)
4. Valida respuesta es JSON válido
5. Valida success flag en JSON: response.success === true
6. Logea respuesta completa si error
```

---

### 9. **NUEVO Método: `destroy()`**

```javascript
/**
 * Limpia listeners cuando app se cierra o service se destruye
 */
destroy() {
    this.messageListener?.remove();
    this.tokenRefreshListener?.remove();
}
```

---

### 10. **NUEVO Método: `getState()`**

```javascript
/**
 * Exporta state completo del servicio (debugging)
 */
getState() {
    return {
        platform,
        initialized,
        pluginAvailable,
        messageListenerActive,
        tokenRefreshListenerActive,
        logsCount
    };
}
```

**Uso:** `console.log(window.getPushNotificationState())`

---

### 11. **API Global - NUEVA** 🌍

Se exponen 6 funciones globales:

```javascript
// 1. Inicializar
window.initializePushNotifications()

// 2. Solicitar token manual
window.requestPushToken()

// 3. Ver estado
window.getPushNotificationState()

// 4. Ver logs
window.getPushNotificationLogs()

// 5. Limpiar logs
window.clearPushNotificationLogs()

// 6. Verificar si está inicializado
window.isPushNotificationInitialized()
```

**Plus: Dos custom events:**

```javascript
// Cuando llega mensaje en foreground
window.addEventListener('pushMessageReceived', (event) => {
    console.log(event.detail.title);
})

// Cuando Firebase rota el token
window.addEventListener('pushTokenRefreshed', (event) => {
    console.log('Nuevo token:', event.detail.token);
})
```

---

## 📊 Matriz de Cambios

| Componente | Antes | Después | Cambio |
|-----------|-------|---------|--------|
| **Namespaces** | Plugins.Messaging (⚠️) | Plugins.FirebaseMessaging (✅) | CRÍTICO |
| **Listeners** | ❌ No hay | ✅ MessageReceived + TokenRefresh | AGREGADO |
| **Error Handling** | Básico | Robusto con validaciones | MEJORADO |
| **Token Validation** | Solo existence | Format + integrity checks | MEJORADO |
| **Backend Response** | Solo HTTP status | Full JSON validation | MEJORADO |
| **Logging** | Simple | Con emoji + contexto | MEJORADO |
| **API Global** | 2 funciones | 6 funciones + 2 events | EXPANDIDO |
| **Cleanup** | ❌ No hay | ✅ destroy() method | AGREGADO |

---

## 🧪 Testing Manual

Para verificar los cambios:

```javascript
// En DevTools console de Android AppC

// 1. Ver estado
console.log(window.getPushNotificationState())

// 2. Ver logs
window.getPushNotificationLogs()

// 3. Escuchar mensajes
window.addEventListener('pushMessageReceived', (e) => {
    console.log('📨 Mensaje:', e.detail.title);
})

// 4. Enviar notificación desde backend
// POST /api/admin/test-notification con user_id=1

// 5. Verificar que se escucha el evento
// Deberías ver: 📨 Mensaje: "Título de prueba"
```

---

## ⚠️ Breaking Changes

Ninguno - Este es Release COMPATIBLE

Todos los métodos anteriores siguen funcionando:
- `window.initializePushNotifications()` ✅
- `window.requestPushToken()` ✅
- `window.NativeFirebaseMessaging.initialize()` ✅

El cambio es 100% backward compatible.

---

## 🎯 Próximos Pasos

Esta refactorización **completa PASO 2**.

Para continuar:

- [ ] **PASO 3**: Crear Permission Service (si la app necesita lógica de permisos adicional)
- [ ] **PASO 4**: Crear Token Service para refresh automático
- [x] **PASO 5-6**: OMITIDOS (Endpoint y Controlador ya existen)
- [ ] **PASO 7**: Migrar FCMService a HTTP v1 (crítico para backend)
- [ ] **PASO 8-10**: Configuración Android + BD

---

## 📎 Archivos Modificados

- `public/js/firebase-messaging-native.js` (refactorizado completamente, 418 líneas)
- `docs/PASO_1_ANALISIS_COMPLETO.md` (análisis previo)
- `docs/ANALISIS_CONTEXTUAL_DOCUMENTACION.md` (contexto de implementación)

---

## ✅ Checklist PASO 2

- ✅ Plugin namespace corregido (Messaging → FirebaseMessaging)
- ✅ Listeners agregados (messageReceived + tokenReceived)
- ✅ Error handling mejorado
- ✅ Backend response validation completa
- ✅ Custom events implementados
- ✅ API global expandida
- ✅ Logging diferenciado con emoji
- ✅ Backward compatible
- ✅ Documentación actualizada

**STATUS: LISTO PARA PASO 3**

---

**Generado:** 2026-02-19  
**Rama:** `feature/firebase-android-fix`  
**Versión del script:** 2.0
