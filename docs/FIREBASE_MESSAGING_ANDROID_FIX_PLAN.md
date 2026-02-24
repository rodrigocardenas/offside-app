# Plan de Acción: Corrección de Firebase Cloud Messaging en Android con Capacitor 6

**Fecha de Inicio:** 19 de Febrero de 2026  
**rama:** `feature/firebase-android-fix`  
**Objetivo:** Refactorizar la implementación de FCM para Android eliminando incompatibilidades con SDK Web y asegurando permisos correctos

---

## 📊 Diagnosis Actual

### ✅ Lo que está bien
- ✅ Plugin `@capacitor-firebase/messaging@^6.3.1` instalado en `package.json`
- ✅ Archivo nativo `firebase-messaging-native.js` existente con estructura básica correcta
- ✅ `capacitor.config.ts` configurado con opciones de presentación
- ✅ Backend Laravel con `kreait/firebase-php` usando FCM HTTP v1
- ✅ Trait `HandlesPushNotifications.php` implementado para envío de notificaciones
- ✅ Permiso `POST_NOTIFICATIONS` agregado en `AndroidManifest.xml`

### ❌ Los problemas
1. **Mapeo de Plugin Incorrecto**: El código intenta acceder a `window.Capacitor.Plugins.Messaging` cuando debería ser `window.Capacitor.Plugins.FirebaseMessaging`
2. **Polyfills de Web SDK**: Se mezcla lógica del SDK Web (Service Workers, Notification API) que no funciona en WebView nativo
3. **Incompleta Validación de Permisos**: No hay flujo claro de solicitud de permisos para Android 13+
4. **Token Registration API**: El endpoint `/api/push/token` podría no estar implementado en el backend
5. **Falta de Configuración de Notificación Nativa**: El `AndroidManifest.xml` falta metadatos para icono y canal de notificación
6. **Sin Documentación de Setup**: No hay guía clara de cómo activarlo en el frontend

---

## 🎯 Plan de Trabajo (10 Pasos)

### **FASE 1: REVISIÓN Y ANÁLISIS** ✓
1. ✅ **PASO 1**: Analizar estructura actual y documentar problemas
   - Revisar `firebase-messaging-native.js` línea por línea
   - Validar `capacitor.config.ts`
   - Revisar `HandlesPushNotifications.php`
   - Revisar `AndroidManifest.xml`
   - **STATUS**: COMPLETADO EN ESTA SESIÓN

### **FASE 2: REFACTORIZACIÓN DE FRONTEND** 🔄
2. **PASO 2**: Refactorizar `firebase-messaging-native.js` con correcciones críticas
   - Corregir mapeo de plugin a `FirebaseMessaging`
   - Eliminar referencias a Firebase Web SDK
   - Mejorar detección y error handling
   - Agregar listeners para mensajes recibidos
   - **TIEMPO ESTIMADO**: 15 minutos

3. **PASO 3**: Crear servicio de permiso unificado
   - Implementar `PermissionService` para Android 13+
   - Manejar rechazo de permisos
   - Reintentos inteligentes
   - **TIEMPO ESTIMADO**: 10 minutos

4. **PASO 4**: Crear servicio de token y sincronización
   - Implementar `TokenService` para obtener y registrar tokens
   - Manejo de rotación de tokens
   - Almacenamiento local de estado
   - **TIEMPO ESTIMADO**: 15 minutos

### **FASE 3: BACKEND LARAVEL** 📦
5. **PASO 5**: Crear endpoint para registro de tokens
   - Implementar ruta `POST /api/push/token`
   - Validación de usuario autenticado
   - Almacenamiento en tabla `push_subscriptions`
   - **TIEMPO ESTIMADO**: 20 minutos

6. **PASO 6**: Crear controlador para manejo de tokens
   - `PushTokenController@store` para registrar
   - `PushTokenController@update` para rotación
   - `PushTokenController@delete` para revocación
   - **TIEMPO ESTIMADO**: 15 minutos

7. **PASO 7**: Actualizar FCMService para HTTP v1
   - Validar que usa Google OAuth 2.0
   - Implementar reintentos
   - Logging detallado
   - **TIEMPO ESTIMADO**: 10 minutos

### **FASE 4: CONFIGURACIÓN ANDROID** 🔧
8. **PASO 8**: Actualizar `AndroidManifest.xml`
   - Agregar metadatos de icono predeterminado para notificaciones
   - Definir canal de notificación predeterminado
   - Revisar permisos necesarios
   - **TIEMPO ESTIMADO**: 10 minutos

9. **PASO 9**: Crear recurso de canal de notificación
   - Crear archivo `res/values/notification_defaults.xml` con configuración de canal
   - Importancia, sonido, vibración
   - **TIEMPO ESTIMADO**: 10 minutos

### **FASE 5: INTEGRACIÓN Y TESTING**
10. **PASO 10**: Crear vista Blade de inicialización
    - Script `push-notifications-init.blade.php`
    - Incluir en layout principal
    - Debug panel opcional
    - **TIEMPO ESTIMADO**: 10 minutos

---

## 🔧 Cambios Necesarios por Archivo

### Frontend (JavaScript/TypeScript)

#### `public/js/firebase-messaging-native.js` (REFACTORIZAR)
```diff
- Línea 194: window.Capacitor.Plugins.Messaging
+ Línea 194: window.Capacitor.Plugins.FirebaseMessaging
```
- Eliminar completamente SDK Web de Firebase
- Mejorar manejo de errores
- Agregar listeners para `onMessageReceived`
- Validar que `plugin.checkPermissions()` y `plugin.requestPermissions()` existendoctor...

#### `resources/views/components/push-notifications-init.blade.php` (CREAR)
- Script de inicialización para vistas Blade
- Meta tags para user-id y csrf-token
- Manejo de inicio automático vs manual

#### `public/js/permission-service.js` (CREAR)
- Servicio centralizado para permisos
- Reintento automático después de la solicitud

#### `public/js/token-service.js` (CREAR)
- Servicio de obtención y sincronización de tokens
- Almacenamiento local de estado
- Detección de rotación de tokens

### Backend (Laravel)

#### `app/Http/Controllers/PushTokenController.php` (CREAR)
```php
- POST /api/push/token -> store()
- PUT /api/push/token/{id} -> update()
- DELETE /api/push/token/{id} -> destroy()
```

#### `routes/api.php` (MODIFICAR)
- Agregar rutas de push tokens
- Proteger con middleware auth:sanctum

#### `app/Services/FCMService.php` (VERIFICAR)
- Validar uso de GoogleClient para OAuth 2.0
- Confirmar endpoint `https://fcm.googleapis.com/v1/projects/{project-id}/messages:send`

#### `database/migrations/xxxx_create_push_subscriptions_table.php` (VERIFICAR)
- Tabla debe existir y tener campos:
  - `id`
  - `user_id` (FK)
  - `device_token` (string, unique)
  - `platform` (enum: android, ios, web)
  - `created_at`
  - `updated_at`
  - `last_notified_at` (nullable)

### Configuración Android

#### `android/app/src/main/AndroidManifest.xml` (MODIFICAR)
```xml
<application>
  <meta-data
    android:name="com.google.firebase.messaging.default_notification_icon"
    android:resource="@drawable/ic_notification" />
  <meta-data
    android:name="com.google.firebase.messaging.default_notification_color"
    android:resource="@color/notification_color" />
</application>
```

#### `android/app/src/main/res/values/notification_defaults.xml` (CREAR)
```xml
<resources>
  <color name="notification_color">#FF6B35</color>
  <string name="notification_channel_id">offside_notifications</string>
  <string name="notification_channel_name">Notificaciones de Offside Club</string>
</resources>
```

---

## 📋 Checklist de Validación

después de cada paso, verificar:

- [ ] Compilación sin errores (NPM + Laravel)
- [ ] AVD ejecutándose correctamente
- [ ] Plugin accesible en console: `window.Capacitor.Plugins.FirebaseMessaging`
- [ ] Permisos solicitados correctamente en Android 13+
- [ ] Token registrado en tabla `push_subscriptions`
- [ ] Notificación recibida en dispositivo
- [ ] Datos de notificación parsados correctamente
- [ ] Backend registra logs correctamente
- [ ] No hay errores en console del WebView

---

## 🚀 Próximos Pasos

De esta sesión:

1. **AHORA**: Ejecutar PASO 1 (Análisis) ✅
2. **SIGUIENTE**: Ejecutar PASO 2 (Refactorización de javascript)
3. luego: Pasos 3-10 secuencialmente

---

## 📚 Referencias

- [Capacitor 6 - FirebaseMessaging Plugin](https://capacitorjs.com/docs/apis/push-notifications-firebase)
- [Firebase Cloud Messaging HTTP v1](https://firebase.google.com/docs/cloud-messaging/quickstart)
- [Android-13+ POST_NOTIFICATIONS Permission](https://developer.android.com/about/versions/13/changes/notification-permission)
- [kreait/firebase-php Documentation](https://firebase-php.readthedocs.io/)

---

## 🐛 Registro de Cambios

| Fecha | Paso | Descripción | Status |
|-------|------|-------------|--------|
| 2026-02-19 | 1 | Análisis de estructura | ✅ Completado |
| 2026-02-19 | 2 | Refactorización JS | ⏳ En Cola |
| 2026-02-19 | 3-10 | Resto de implementación | ⏳ En Cola |
