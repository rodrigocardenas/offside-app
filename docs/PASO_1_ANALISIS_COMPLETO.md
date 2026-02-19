# PASO 1: Análisis de Estructura Actual

**Fecha:** 19 de Febrero de 2026  
**Ejecutado por:** Refactorización de FCM Android  
**Status:** ✅ COMPLETADO

---

## 📂 Árbol de Archivos Relevantes Identificados

```
offsideclub/
├── 📋 FRONTEND
│   ├── public/js/
│   │   ├── firebase-init-debug.js (SDK Web - DEPRECAR)
│   │   └── firebase-messaging-native.js (Implementación Nativa)
│   ├── resources/views/
│   │   └── (layouts que incluyen scripts)
│   ├── capacitor.config.ts (Configuración de Capacitor)
│   └── package.json (Dependencias)
│
├── 📦 BACKEND
│   ├── app/
│   │   ├── Http/Controllers/ (CREAR PushTokenController)
│   │   ├── Services/FCMService.php (Envío de notificaciones)
│   │   ├── Traits/HandlesPushNotifications.php (Lógica base)
│   │   ├── Jobs/
│   │   │   ├── SendChatPushNotification.php
│   │   │   ├── SendDailyUnanswerQuestionReminderPushNotification.php
│   │   │   └── SendSocialQuestionPushNotification.php
│   │   ├── Models/
│   │   │   └── User.php (Relación con pushSubscriptions)
│   │   └── Notifications/
│   │       ├── PredictiveResultsAvailable.php
│   │       └── NewSocialQuestionAvailable.php
│   ├── routes/api.php (AGREGAR rutas de tokens)
│   ├── database/
│   │   ├── migrations/ (VERIFICAR push_subscriptions)
│   │   └── seeders/
│   └── config/services.php (Firebase config)
│
└── 🔧 ANDROID
    ├── android/app/
    │   ├── src/main/AndroidManifest.xml (MODIFICAR)
    │   ├── src/main/res/ (CREAR notification_defaults.xml)
    │   ├── capacitor.build.gradle (Dependencias Capacitor)
    │   └── build.gradle (Dependencias Firebase)
    ├── capacitor-cordova-android-plugins/ (Plugin nativo)
    └── capacitor.config.ts
```

---

## 🔍 Análisis Detallado por Component

### 1. JavaScript Frontend

#### **`public/js/firebase-messaging-native.js`** 
**Estado:** 📝 Parcialmente Correcto pero requiere refactorización

**Líneas clave analizadas:**

| Línea | Código Actual | Problema | Solución |
|-------|---|---|---|
| 194 | `window.Capacitor.Plugins.Messaging` | ❌ Plugin name incorrecto | Cambiar a `FirebaseMessaging` |
| 111 | `const plugin = this.getFirebaseMessagingPlugin()` | ✅ Correcto | Mantener |
| 106 | `this.initializeAndroid()` | ✅ Flujo correcto | Mantener |
| 125+ | `checkPermissions()` / `requestPermissions()` | ✅ Presentes | Mejorar error handling |
| 265+ | `registerTokenWithBackend(token)` | ⚠️ Endpoint faltante | Crear endpoint en Laravel |

**Problemas identificados:**
1. **No hay listeners de mensajes recibidos** - falta listener para `onMessageReceived`
2. **No hay manejo de token rotation** - el plugin puede rotar tokens periódicamente
3. **No hay validación de disponibilidad de plugin antes de usar** - faltan try/catch adicionales
4. **No hay especificación de método HTTP correcto en backend call**

---

#### **`public/js/firebase-init-debug.js`**
**Estado:** 🚫 DEPRECADO - Usar SDK Web

**Problemas:**
- Línea 98: `const messaging = firebase.messaging();` - Usa SDK Web incorrecto en WebView
- Línea 169: Intenta getToken() del SDK Web - No funciona en Android nativo
- Crea Service Workers que no son soportados en WebView nativo

**Acción:** ELIMINAR o reemplazar completamente

---

### 2. Configuración Capacitor

#### **`capacitor.config.ts`**
**Estado:** ✅ Básicamente correcto

```typescript
FirebaseMessaging: {
    presentationOptions: ['badge', 'sound', 'alert']
}
```

**Análisis:**
- ✅ Las opciones de presentación están bien configuradas
- ⚠️ Falta configuración de opciones de inicialización
- ⚠️ Falta especificación de permisos explícitos

**Mejoras necesarias:**
```typescript
FirebaseMessaging: {
    presentationOptions: ['badge', 'sound', 'alert'],
    // Agregar en futuro si es necesario:
    // checkPermissionsOnInitialization: true
}
```

---

### 3. Backend Laravel

#### **`app/Traits/HandlesPushNotifications.php`**
**Estado:** ✅ Implementación sólida

**Análisis:**
```php
- getFirebaseMessaging() (líneas 14-29)
  ✅ Carga correctamente credenciales
  ✅ Manejo de excepciones presente
  
- sendPushNotificationToGroupUsers() (líneas 35-70)
  ✅ Itera usuarios grupos
  ✅ Logging detallado
  ✅ Cuenta éxitos/fallos
  
- sendPushNotificationToUser() (líneas 87-100+)
  ✅ Accede a pushSubscriptions del usuario
  ✅ Iteración por dispositivo
```

**Problemas:**
1. **No hay validación de formato de token** - acepta cualquier string
2. **No hay manejo de tokens revocados** - los guarda sin marcar como inválidos
3. **Falta lógica de reintento** - si falla, no reintentar

---

#### **`app/Services/FCMService.php`**
**Estado:** ⚠️ CRÍTICO - Verificar protocolo HTTP v1

**Requerimientos:**
- Debe usar `kreait/firebase-php`
- Debe enviar a: `https://fcm.googleapis.com/v1/projects/{project-id}/messages:send`
- Debe autenticarse con Google OAuth 2.0 (no claves de servidor)

**Verificación pendiente:** Leer archivo completo para confirmar implementación

---

#### **`routes/api.php`**
**Estado:** ❌ FALTANTE

**Rutas necesarias:**
```
POST   /api/push/token          -> PushTokenController@store
PUT    /api/push/token/{id}     -> PushTokenController@update
DELETE /api/push/token/{id}     -> PushTokenController@destroy
GET    /api/push/token/verify   -> PushTokenController@verify
```

---

#### **Database `push_subscriptions`**
**Estado:** ⏳ VERIFICACIÓN PENDIENTE

**Estructura esperada:**
```sql
CREATE TABLE push_subscriptions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    device_token VARCHAR(255) UNIQUE NOT NULL,
    platform ENUM('android', 'ios', 'web'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    last_notified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

### 4. Configuración Android

#### **`android/app/src/main/AndroidManifest.xml`**
**Estado:** ⚠️ Incompleto

**Lo que falta:**
```xml
<!-- METADATOS DE NOTIFICACIÓN FALTANTES -->
<application>
    <!-- Esto FALTA: -->
    <meta-data
        android:name="com.google.firebase.messaging.default_notification_icon"
        android:resource="@drawable/ic_notification" />
    <meta-data
        android:name="com.google.firebase.messaging.default_notification_color"
        android:resource="@color/notification_color" />
</application>
```

**Lo que está bien:**
```xml
<!-- ✅ Permiso correcto para Android 13+ -->
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
```

---

#### **`android/app/build.gradle`**
**Estado:** ✅ Dependencias correctas

```gradle
implementation 'com.google.firebase:firebase-messaging'  // ✅
```

---

#### **`android/app/capacitor.build.gradle`**
**Estado:** ✅ Plugin Firebase incluido

```gradle
implementation project(':capacitor-firebase-messaging')  // ✅
```

---

## 📊 Matriz de Problemas Encontrados

| Problema | Severidad | Componente | Impacto | Solución |
|----------|-----------|-----------|--------|----------|
| Plugin name incorrecto (`Messaging` vs `FirebaseMessaging`) | 🔴 CRÍTICO | JS | Plugin no cargado | Cambiar nombre |
| Sin listeners de mensajes recibidos | 🔴 CRÍTICO | JS | Notificaciones no procesa | Agregar listener |
| Falta endpoint `/api/push/token` | 🔴 CRÍTICO | Laravel | Token no guardado en BD | Crear controlador |
| Falta metadatos en AndroidManifest | 🟠 ALTO | Android | Icono de notificación incorrecto | Agregar metadatos |
| Sin validación de permisos POST_NOTIFICATIONS | 🟠 ALTO | JS/Android | Notificaciones silenciadas | Flujo de permisos |
| Sin manejo de token rotation | 🟡 MEDIO | JS/Backend | Tokens obsoletos en BD | Detector de rotación |
| Sin reintento en fallos de envío | 🟡 MEDIO | Laravel | Notificaciones perdidas | Agregar queue |
| Falta validación en registerTokenWithBackend | 🟡 MEDIO | JS | Datos inválidos enviados | Validación cliente |

---

## 🎯 Impacto por Plataforma

### Android (Capacitor 6)
- **Crítico:** Namespace del plugin incorrecto → NotificaFUNCIONA
- **Crítico:** Sin manejo de permisos POST_NOTIFICATIONS → BLOQUEADO en Android 13+
- **Crítico:** Sin listeners → notificaciones llegan pero no se procesan
- **Alto:** Sin decoración visual (falta icono)

### iOS (Capacitor 6)
- **Crítico:** Mismo namespace del plugin
- **Medio:** iOS no requiere POST_NOTIFICATIONS pero workflow debe ser consistente

### Web (Browser)
- **Bajo:** Notificaciones no soportadas por diseño - Correcto mantener exclusión

---

## ✅ Verificaciones Iniciales Completadas

- ✅ Estructura de archivos mapeada
- ✅ Problemas principales identificados
- ✅ Severidad de cada problema evaluada
- ✅ Soluciones documentadas
- ✅ Impacto en cada plataforma analizado
- ✅ Matriz de problemas creada

---

## 📋 Próximos Pasos

**PASO 2 (siguiente):** Refactorizar `firebase-messaging-native.js` con las correcciones identificadas

**Cambios principales:**
1. Corregir namespace de plugin (`Messaging` → `FirebaseMessaging`)
2. Agregar listener `onMessageReceived`
3. Mejorar error handling
4. Validar permisos POST_NOTIFICATIONS explícitamente
5. Agregar reintentos inteligentes

---

## 📎 Archivos de Referencia

- Reporte completo en: [FIREBASE_MESSAGING_ANDROID_FIX_PLAN.md](FIREBASE_MESSAGING_ANDROID_FIX_PLAN.md)
- Script de análisis: `public/js/firebase-messaging-native.js` (líneas 1-418)
- Configuración base: `capacitor.config.ts`
- Backend base: `app/Traits/HandlesPushNotifications.php`

---

**Reporte generado:** 2026-02-19 14:30 UTC  
**Rama activa:** `feature/firebase-android-fix`  
**Estado general:** LISTO PARA INICIAR REFACTORIZACIÓN
