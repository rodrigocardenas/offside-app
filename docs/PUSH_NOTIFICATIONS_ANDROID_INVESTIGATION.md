# 🔍 Investigación: Push Notifications Android — Estado Actual y Plan de Acción

**Fecha:** Mayo 2026  
**Foco:** Android (Capacitor 6 + FCM)  
**Estado:** ❌ No funcional en producción

---

## 📐 Arquitectura Actual

```
[Android App (WebView)]
    ↓ firebase-messaging-native.js
    ↓ @capacitor-firebase/messaging plugin (nativo)
    → FCM Token → POST /api/push/token
    → push_subscriptions (MySQL)

[Backend - Laravel]
    ↓ Job (SendChatPushNotification, etc.)
    ↓ HandlesPushNotifications trait
    ↓ kreait/firebase-php (service account)
    → FCM HTTP v1 API → Android Device
```

### Archivos clave
| Capa | Archivo |
|------|---------|
| Android nativo | `android/app/src/main/java/com/offsideclub/app/NotificationChannelManager.java` |
| Android nativo | `android/app/src/main/java/com/offsideclub/app/MainActivity.java` |
| Frontend JS | `public/js/firebase-messaging-native.js` (v2.0) |
| Frontend JS | `public/js/permission-service.js` |
| Frontend JS | `public/js/token-service.js` |
| Blade component | `resources/views/components/firebase-messaging-init.blade.php` |
| Layout | `resources/views/layouts/app.blade.php` (carga los JS) |
| Backend API | `app/Http/Controllers/PushTokenController.php` |
| Backend envío | `app/Traits/HandlesPushNotifications.php` |
| Backend envío (alternativo) | `app/Services/FCMService.php` |
| Jobs | `app/Jobs/SendChatPushNotification.php` + 4 más |
| Modelo | `app/Models/PushSubscription.php` |

---

## 🚨 Issues Encontrados (Ordenados por Severidad)

---

### 🔴 CRÍTICO #1 — Credenciales Firebase NO existen en producción

**Ubicación:** `app/Services/FCMService.php` (L31) + `app/Traits/HandlesPushNotifications.php` (L16)

**Problema:** Ambos archivos **hardcodean** el path de las credenciales del service account:
```php
// FCMService.php
$this->credentialsPath = base_path("storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json");

// HandlesPushNotifications.php
$credentials_path = base_path("storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json");
```

**Verificado en producción:**
```bash
# storage/app/ en producción SOLO contiene:
ls /var/www/html/storage/app/
# → public/
# → public.backup.2026-03-26/
# El archivo JSON NO existe
```

**El .env de producción tiene:**
```
FIREBASE_CREDENTIALS=offside-dd226-13a14c6c6a5c.json
```
Pero esta variable **nunca se lee** en el código. Nadie hace `env('FIREBASE_CREDENTIALS')`.

**Impacto:** Cada vez que un Job intenta enviar una notificación, lanza la excepción:
```
Firebase credentials not found at: .../storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json
```
El Job falla silenciosamente (la excepción está atrapada en `try/catch`).

---

### 🔴 CRÍTICO #2 — Variables de credenciales no usadas / path inconsistente

**Problema triple:**
1. `.env` de producción referencia un archivo (`offside-dd226-13a14c6c6a5c.json`) diferente al hardcodeado
2. Localmente existen **DOS** archivos de credenciales (`54f29fd43f` y `f84b3f8e09`) — ninguno es el de producción
3. No hay variable de entorno `FIREBASE_CREDENTIALS_PATH` que permita configurar el path sin tocar código

**Impacto:** Cualquier deploy que no incluya el JSON correcto en el servidor rompe las notificaciones silenciosamente.

---

### 🟠 MAYOR #3 — Version mismatch: Capacitor CLI 7.x + Core/Android 6.x

**Verificado en `node_modules`:**
| Paquete | Especificado | Instalado |
|---------|-------------|-----------|
| `@capacitor/cli` | `^7.4.5` | `7.4.5` |
| `@capacitor/core` | `^6.1.4` | `6.2.1` |
| `@capacitor/android` | `^6.1.4` | (via gradle) |
| `@capacitor-firebase/messaging` | `^6.3.1` | `6.3.1` |
| `firebase` | `^11.9.0` | `11.9.0` |
| `@capacitor/browser` | `^8.0.3` | `8.x` |

**Problema:** `@capacitor/cli` v7 genera configuraciones y código de sync diferente al que espera `@capacitor/core` v6. Esto puede causar:
- Plugins nativos no cargados correctamente después de `cap sync`
- `window.Capacitor.Plugins.FirebaseMessaging` no disponible en el WebView
- Comportamientos inesperados en la capa bridge nativa ↔ JS

El `@capacitor/browser` a v8 es inconsistente con el resto del stack en v6.

**Impacto:** El plugin `FirebaseMessaging` puede no estar disponible en runtime aunque esté instalado.

---

### 🟠 MAYOR #4 — `HandlesPushNotifications` envía `webpush` config para tokens Android

**Ubicación:** `app/Traits/HandlesPushNotifications.php` L91-110

```php
// SIEMPRE incluye webpush, incluso para android:
$message = [
    'notification' => [...],
    'data' => $data,
    'webpush' => [          // ← Esta config es para web, no Android
        'headers' => ['Urgency' => 'high'],
        'notification' => ['icon' => '...', 'click_action' => '...'],
        'fcm_options' => ['link' => '...'],
    ],
    'token' => $subscription->device_token,
];

// Luego TAMBIÉN agrega android config:
if (in_array($subscription->platform, ['android', 'ios'])) {
    $message['android'] = [...];
    $message['apns'] = [...];   // ← iOS siempre se agrega aunque sea Android
}
```

**Problemas:**
1. Para un token Android se envían simultáneamente `webpush`, `android` y `apns` — solo debería ir `android`
2. Enviar configuración `apns` a un token Android puede causar rechazo o comportamiento indefinido en FCM

---

### 🟠 MAYOR #5 — Token Android único en producción, posiblemente expirado

**Verificado en BD:**
```sql
id=36, user_id=230, platform=android, created_at=2026-02-18
```
Solo 1 token Android registrado, con **3+ meses de antigüedad**. FCM puede haber rotado/expirado ese token. Si el backend intentara enviar a ese token, FCM respondería `UNREGISTERED` o `NOT_FOUND`, y no hay lógica para limpiar tokens inválidos.

---

### 🟡 MODERADO #6 — Dos implementaciones paralelas de envío (FCMService vs Trait)

Existe `FCMService.php` con `sendPushNotification()` pero **ningún Job lo usa**. Todos los Jobs usan `HandlesPushNotifications` trait. Esto crea código muerto y confusión sobre cuál es la implementación canónica.

---

### 🟡 MODERADO #7 — `firebase-test-simple.js` se carga en todos los entornos

**Ubicación:** `resources/views/layouts/app.blade.php` L131

```blade
<script src="{{ asset('js/firebase-test-simple.js') }}"></script>
```
Este script de debug se carga en **todos los entornos**, incluyendo producción. No está condicionado a `app()->environment('local')`.

---

### 🟡 MODERADO #8 — `APP_ENV=local` en producción

**Verificado:** El `.env` de producción tiene `APP_ENV=local`. Esto no rompe las notificaciones directamente, pero:
- Puede afectar optimizaciones de caché y config
- La condición `app()->environment('local')` para el debug widget se activa en producción
- Indica que la configuración de producción no está correctamente mantenida

---

### 🟡 MODERADO #9 — `endpoint` UNIQUE en tabla pero Android no lo envía

**Esquema en producción:**
```
endpoint  varchar(255)  YES  UNI  NULL
```

El `PushTokenController` hace `updateOrCreate(['device_token' => $token], [...])`. Si se registra el mismo device_token dos veces, funciona (el `updateOrCreate` por `device_token` lo actualiza). Pero la restricción UNIQUE en `endpoint` con NULL es tratada diferente según versiones de MySQL — algunos permiten múltiples NULL, otros no. En producción usa MySQL (no SQLite), que permite múltiples NULL en UNIQUE, así que este no es un bloqueador activo.

---

### 🟢 INFO #10 — Service Worker usa Firebase SDK v9 compat (solo afecta web)

`public/firebase-messaging-sw.js` usa scripts de Firebase v9 compat. Para Android nativo, el Service Worker no se usa (Capacitor usa APIs nativas), así que esto no afecta Android.

---

## 📊 Flujo de Registro del Token (Android) — Diagnóstico Paso a Paso

```
[App Android inicia]
    ↓
[WebView carga https://app.offsideclub.es] ✅ (verificado en capacitor.config.json)
    ↓
[firebase-messaging-native.js se carga] ✅
    ↓
[initializeOnDOMReady() detecta Android] ✅ (window.Capacitor.isNativePlatform())
    ↓
[getFirebaseMessagingPlugin() busca window.Capacitor.Plugins.FirebaseMessaging]
    ⚠️ POTENCIAL FALLA: mismatch CLI 7 + Core 6 puede dejar el plugin sin registrar
    ↓
[checkPermissions() + requestPermissions()] ✅ (POST_NOTIFICATIONS en AndroidManifest)
    ↓
[plugin.getToken()] 
    ⚠️ POTENCIAL FALLA: si google-services.json / app_id no coincide con el proyecto Firebase
    ↓
[POST /api/push/token] ✅ (ruta pública, sin auth)
    ↓
[push_subscriptions guardado en BD] ✅
```

## 📊 Flujo de Envío de Notificación (Backend → Android) — Diagnóstico Paso a Paso

```
[Evento: nuevo mensaje en chat]
    ↓
[SendChatPushNotification::dispatch($messageId)] ✅
    ↓
[HandlesPushNotifications::sendPushNotificationToGroupUsers()] ✅
    ↓
[getFirebaseMessaging() → Factory::withServiceAccount($credentials_path)]
    ❌ FALLA AQUÍ: archivo JSON no existe en producción
    ↓ (excepción atrapada en try/catch, Log::error())
[Notificación NO se envía]
```

---

## 🔑 Resumen de Causas Raíz

| # | Causa | Probabilidad de ser el bloqueador principal |
|---|-------|---------------------------------------------|
| 1 | Credenciales Firebase ausentes en producción | **100% — Bloqueador confirmado** |
| 2 | Variables de entorno no usadas en código | **100% — Relacionado con #1** |
| 3 | Version mismatch Capacitor CLI 7 + Core 6 | Alta — puede bloquear registro de token |
| 4 | Webpush config enviada a tokens Android | Media — puede causar rechazo FCM |
| 5 | Token Android expirado en BD | Media — afectaría post-fix |

---

## 🗺️ Plan de Acción

### Fase 1 — Fix Crítico: Credenciales Firebase en producción (BLOQUEADOR)

#### Paso 1.1 — Leer credenciales desde variable de entorno
Modificar `FCMService.php` y `HandlesPushNotifications.php` para usar `env('FIREBASE_CREDENTIALS_PATH')`.

**`FCMService.php`:**
```php
$this->credentialsPath = base_path(
    "storage/app/" . env('FIREBASE_CREDENTIALS', 'offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json')
);
```

**`HandlesPushNotifications.php`:**
```php
$credentials_path = base_path(
    "storage/app/" . env('FIREBASE_CREDENTIALS', 'offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json')
);
```

#### Paso 1.2 — Subir credenciales al servidor de producción
```bash
# Desde local, subir el archivo JSON al servidor
scp -i ~/.ssh/offside-deploy-key.pem \
  storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json \
  ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com:/var/www/html/storage/app/

# Verificar
ssh -i ~/.ssh/offside-deploy-key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com \
  "ls -la /var/www/html/storage/app/"
```

#### Paso 1.3 — Actualizar .env de producción
```bash
# En producción, actualizar FIREBASE_CREDENTIALS para que coincida con el archivo subido
FIREBASE_CREDENTIALS=offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json
```

#### Paso 1.4 — Limpiar config cache en producción
```bash
php artisan config:clear && php artisan config:cache
```

---

### Fase 2 — Fix: Corregir lógica de envío por plataforma

#### Paso 2.1 — Separar configs por plataforma en `HandlesPushNotifications`
Eliminar el `webpush` para tokens Android/iOS y el `apns` para tokens Android.

```php
// Para android: solo 'android' config
// Para ios: solo 'apns' config  
// Para web: solo 'webpush' config
if ($subscription->platform === 'android') {
    $message['android'] = [...];
    // sin 'webpush', sin 'apns'
} elseif ($subscription->platform === 'ios') {
    $message['apns'] = [...];
    // sin 'webpush', sin 'android'
} else {
    $message['webpush'] = [...];
    // sin 'android', sin 'apns'
}
```

#### Paso 2.2 — Unificar en `FCMService` como implementación canónica
Los Jobs deberían usar `FCMService` directamente en lugar del trait `HandlesPushNotifications`.  
O bien, deprecar `FCMService` y dejar solo el trait. Elegir una.

---

### Fase 3 — Fix: Version mismatch Capacitor

#### Paso 3.1 — Alinear versiones
Hay dos opciones:

**Opción A: Bajar CLI a v6 (más seguro, menos breaking changes)**
```bash
npm install @capacitor/cli@^6.1.4 --save-dev
npx cap sync android
```

**Opción B: Subir todo a v7 (más trabajo, más nueva)**
```bash
npm install @capacitor/core@^7.0.0 @capacitor/android@^7.0.0 @capacitor/ios@^7.0.0
npm install @capacitor-firebase/messaging@^7.0.0  # verificar disponibilidad
npx cap sync android
```

**Recomendación:** Opción A a corto plazo (menor riesgo).

#### Paso 3.2 — Corregir `@capacitor/browser` 
```bash
npm install @capacitor/browser@^6.0.0
```

---

### Fase 4 — Diagnóstico de registro del token Android

#### Paso 4.1 — Agregar logging al endpoint de registro
En `PushTokenController::update()` añadir logs detallados para confirmar que el token llega.

#### Paso 4.2 — Verificar plugin disponible en WebView
Agregar una ruta de diagnóstico temporal:
```
GET /api/push/diagnostics
→ Devuelve tokens registrados por plataforma y fecha
```

#### Paso 4.3 — Limpiar tokens expirados
Después de un envío fallido con error FCM `NOT_FOUND` o `UNREGISTERED`, eliminar el registro de `push_subscriptions`:
```php
// En HandlesPushNotifications, después de catch:
if (str_contains($e->getMessage(), 'NOT_FOUND') || str_contains($e->getMessage(), 'UNREGISTERED')) {
    $subscription->delete();
    Log::info('Token inválido eliminado', ['user_id' => $user->id]);
}
```

---

### Fase 5 — Testing y verificación

#### Checklist de testing Android:
```
□ Abrir app en dispositivo Android
□ Verificar en Chrome DevTools (remote debugging) que:
  - window.Capacitor.isNativePlatform() === true
  - window.Capacitor.Plugins.FirebaseMessaging existe
  - initializePushNotifications() no da error
  - POST /api/push/token devuelve 200

□ Verificar en BD que el token se guardó con platform='android'

□ Disparar notificación de prueba desde tinker:
  $user = User::find(230);
  dispatch(new App\Jobs\SendChatPushNotification(1));

□ Verificar en logs Laravel que no hay error de credenciales
□ Verificar que la notificación llega al dispositivo
```

---

## 📋 Orden de Ejecución Recomendado

| Prioridad | Tarea | Esfuerzo | Impacto |
|-----------|-------|---------|---------|
| 1 | Subir credenciales Firebase a producción | 5 min | **Desbloquea todo** |
| 2 | Refactorizar código para leer desde env | 15 min | Sostenibilidad |
| 3 | Fix lógica webpush/android/apns en Trait | 20 min | Corrección |
| 4 | Alinear versiones Capacitor (CLI → v6) | 30 min + rebuild | Estabilidad |
| 5 | Limpiar tokens expirados automáticamente | 30 min | Mantenimiento |
| 6 | Remover `firebase-test-simple.js` del layout | 5 min | Limpieza |
| 7 | Corregir `APP_ENV=production` en producción | 2 min | Corrección |

---

## 🗂️ Estado de la BD en Producción

```sql
-- Tokens registrados actualmente:
SELECT id, user_id, platform, LEFT(device_token,30) as token, created_at 
FROM push_subscriptions ORDER BY created_at DESC;

id=38  user_id=229  platform=web      created_at=2026-02-18
id=36  user_id=230  platform=android  created_at=2026-02-18  ← ÚNICO token Android
id=37  user_id=229  platform=web      created_at=2026-02-18
id=35  user_id=3    platform=web      (test-token)
id=33  user_id=8    platform=web      created_at=2025-12
id=32  user_id=104  platform=web      created_at=2025-12
id=29  user_id=8    platform=web      created_at=2025-11
```

El token Android (id=36) tiene 3+ meses — **probablemente expirado**.

---

## 🔧 Comandos Útiles para el Fix

```bash
# 1. Subir credenciales a producción
scp -i ~/.ssh/offside-deploy-key.pem \
  storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json \
  ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com:/var/www/html/storage/app/

# 2. Verificar en producción
ssh -i ~/.ssh/offside-deploy-key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com \
  "ls -la /var/www/html/storage/app/"

# 3. Limpiar config cache en producción
ssh -i ~/.ssh/offside-deploy-key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com \
  "cd /var/www/html && php artisan config:clear && php artisan config:cache"

# 4. Probar envío de notificación desde tinker (producción)
ssh -i ~/.ssh/offside-deploy-key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com \
  "cd /var/www/html && php artisan tinker --execute=\"dispatch(new App\\Jobs\\SendChatPushNotification(1));\""

# 5. Ver logs después del test
ssh -i ~/.ssh/offside-deploy-key.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com \
  "cat /var/www/html/storage/logs/laravel.log | grep -A5 'firebase\|FCM\|push' | head -50"
```
