# 🔔 Bug 3: Notificaciones Firebase Solo en Web, No en Mobile - Análisis Técnico

**Fecha:** 4 febrero 2026  
**Rama:** `feature/bug3-firebase-notifications`  
**Status:** En Progreso

---

## 📋 Resumen Ejecutivo

### Problema
Las notificaciones push configuradas con Firebase + Admin SDK solo se reciben en la web app, no en la app móvil generada con Capacitor.

### Raíz del Problema
1. **Capacitor no tiene Firebase Messaging integrado** - No existe plugin de Firebase en Capacitor
2. **Service Workers no se registran en contexto Capacitor** - El código de Firebase Messaging en `firebase-messaging-sw.js` es para web, no para apps nativas
3. **Device tokens de web ≠ device tokens de mobile** - La web genera tokens diferentes a los que generaría Capacitor
4. **Falta infraestructura de manejo foreground/background** - No hay handler de notificaciones en contexto nativo

---

## 🔍 Estado Actual del Código

### Infraestructura Existente ✅
**Lo que ya funciona para Web:**

1. **Models & DB:**
   - ✅ `app/Models/PushSubscription.php` - Modelo para guardar tokens
   - ✅ `database/migrations/2025_06_20_create_push_subscriptions_table.php` - Tabla con `device_token`

2. **Backend:**
   - ✅ `app/Http/Controllers/PushTokenController.php` - Endpoint para guardar tokens
   - ✅ `app/Services/FCMService.php` - Servicio FCM legacy (deprecated, usando Admin SDK)
   - ✅ `app/Jobs/SendNewPredictiveQuestionsPushNotification.php` - Job para notificaciones
   - ✅ `app/Jobs/SendChatPushNotification.php` - Job para chat
   - ✅ `app/Jobs/SendPredictiveResultsPushNotification.php` - Job para resultados
   - ✅ `app/Jobs/SendSocialQuestionPushNotification.php` - Job para preguntas sociales

3. **Frontend (Web):**
   - ✅ `public/sw.js` - Service Worker principal
   - ✅ `public/firebase-messaging-sw.js` - SW de Firebase (solo web)
   - ✅ `firebase` v11.9.0 en `package.json`

### Lo que Falta ❌
1. **Plugin Capacitor Firebase** - No existe `@capacitor-firebase/messaging`
2. **Configuración Firebase en Capacitor** - `capacitor.config.ts` no tiene config Firebase
3. **Handler de notificaciones nativas** - No existe código para manejar push en foreground/background de Android/iOS
4. **Servicio de sincronización de tokens** - No hay lógica para registrar tokens de app móvil en BD

---

## 🏗️ Arquitectura de Solución

### Fase 1: Instalación de Dependencias
1. Agregar `@capacitor-firebase/messaging` (plugin oficial)
2. Agregar `@capacitor/device` para obtener ID de dispositivo
3. Actualizar Capacitor core a versión compatible

### Fase 2: Configuración
1. Agregar credenciales Firebase a `capacitor.config.ts`
2. Vincular proyecto Android con Firebase Console
3. Descargar `google-services.json`

### Fase 3: Frontend - Servicio de Notificaciones
1. Crear `public/js/firebase-notification-service.js` con:
   - Detección de contexto (web vs Capacitor)
   - Inicialización diferenciada
   - Handler de tokens

2. Crear `public/js/capacitor-notification-handler.js` con:
   - Inicialización de `@capacitor-firebase/messaging`
   - Listener de notificaciones en foreground
   - Listener de notificaciones en background
   - Sincronización de tokens con backend

### Fase 4: Backend
1. Actualizar `PushTokenController` para aceptar tokens de Capacitor
2. Crear endpoint de relleno de tokens para debug
3. Actualizar Jobs para enviar a AMBOS tipos de tokens (web + mobile)

### Fase 5: Testing
1. Testing de notificaciones en web (debe seguir funcionando)
2. Testing de notificaciones en app móvil en foreground
3. Testing de notificaciones en app móvil en background
4. Testing de sincronización de tokens

---

## 📦 Dependencies a Instalar

```bash
npm install @capacitor-firebase/messaging @capacitor/device
```

**Versiones sugeridas:**
- `@capacitor-firebase/messaging`: ^6.1.2
- `@capacitor/device`: ^6.0.1

---

## 🔐 Configuración Firebase Console

**Pasos necesarios:**
1. Firebase Project ID: `offside-dd226`
2. Agregar app Android con paquete: `com.offsideclub.app` (del `capacitor.config.ts`)
3. Descargar `google-services.json` → `android/app/google-services.json`
4. Nota: La configuración ya existe, solo necesita conectar Android app

---

## 📝 Archivos a Crear/Modificar

### Crear (Nuevos)
- [ ] `public/js/firebase-notification-service.js` - Servicio unificado
- [ ] `public/js/capacitor-notification-handler.js` - Handler para Capacitor
- [ ] `app/Traits/HandlesPushNotifications.php` - Trait para lógica compartida
- [ ] `database/migrations/2025_02_04_add_platform_to_push_subscriptions.php` - Agregar columna `platform` (web/android/ios)

### Modificar
- [ ] `package.json` - Agregar `@capacitor-firebase/messaging`
- [ ] `capacitor.config.ts` - Configuración Firebase
- [ ] `resources/views/layouts/app.blade.php` - Incluir nuevos scripts
- [ ] `app/Http/Controllers/PushTokenController.php` - Agregar `platform`
- [ ] `app/Jobs/SendNewPredictiveQuestionsPushNotification.php` - Enviar a ambos tipos
- [ ] `app/Jobs/SendChatPushNotification.php` - Enviar a ambos tipos
- [ ] `app/Jobs/SendPredictiveResultsPushNotification.php` - Enviar a ambos tipos
- [ ] `app/Jobs/SendSocialQuestionPushNotification.php` - Enviar a ambos tipos
- [ ] `app/Models/PushSubscription.php` - Agregar `platform` field

---

## 🎯 Plan de Implementación Paso a Paso

### Paso 1: Preparar entorno
- [x] Crear rama `feature/bug3-firebase-notifications`
- [ ] Instalar dependencias npm
- [ ] Actualizar package.json
- [ ] Sincronizar Capacitor

### Paso 2: Configuración Base
- [ ] Crear migration para agregar columna `platform`
- [ ] Actualizar `capacitor.config.ts` con Firebase
- [ ] Crear servicio de notificaciones unificado

### Paso 3: Frontend
- [ ] Crear `firebase-notification-service.js`
- [ ] Crear `capacitor-notification-handler.js`
- [ ] Integrar en `app.blade.php`

### Paso 4: Backend
- [ ] Actualizar `PushTokenController`
- [ ] Actualizar Jobs de notificaciones
- [ ] Crear trait para lógica compartida

### Paso 5: Testing
- [ ] Testing manual en web
- [ ] Testing en simulador Android
- [ ] Testing en dispositivo Android real

---

## 🔗 Referencias

- [Capacitor Firebase Messaging](https://capacitorjs.com/docs/apis/firebase-messaging)
- [Firebase Cloud Messaging (FCM)](https://firebase.google.com/docs/cloud-messaging)
- [Admin SDK - Messaging](https://firebase.google.com/docs/reference/admin/node/admin.messaging)
- [Capacitor Android Configuration](https://capacitorjs.com/docs/android/configuration)

---

## 📊 Checklist de Implementación

- [ ] Fase 1: Instalación de Dependencias
- [ ] Fase 2: Configuración
- [ ] Fase 3: Frontend - Servicio de Notificaciones
- [ ] Fase 4: Backend
- [ ] Fase 5: Testing
- [ ] Documentación de operación
- [ ] Deploy a producción

---

## 🚀 Timeline Estimado

- **Día 1 (Hoy):** Fases 1-2 (Setup)
- **Día 2:** Fases 3-4 (Implementación)
- **Día 3:** Fase 5 (Testing)
- **Total:** 3 días de desarrollo

