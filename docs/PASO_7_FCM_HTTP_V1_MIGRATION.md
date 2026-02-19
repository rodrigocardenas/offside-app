# PASO 7: Migración FCMService a HTTP v1 API

**Estado:** ✅ COMPLETADO
**Fecha:** 2026-02-19
**Rama:** `feature/firebase-android-fix`
**Archivo:** `app/Services/FCMService.php`

---

## 🎯 Objetivo

Refactorizar `FCMService.php` para usar la **API HTTP v1 de Firebase** (versión actual de Google) en lugar de la deprecated `fcm/send` API (versión legacy).

**Impacto:** Este es el último bloqueador crítico. Sin esto, el backend NO PUEDE ENVIAR notificaciones push incluso si el frontend obtiene y registra tokens correctamente.

---

## ⚠️ Problema Original (ANTES)

```php
// ❌ DEPRECATED - Esta API ya no funciona
class FCMService
{
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    
    // ❌ Autenticación usando API Key antiguo
    'Authorization' => 'key=' . $this->serverKey,
    
    // ❌ Formato de mensaje deprecated
    ['to' => $deviceToken, 'notification' => [...]]
}
```

### Por qué esto es un problema:
1. **Google deprecated `fcm/send` endpoint** - Puede dejar de funcionar en cualquier momento
2. **No usa OAuth 2.0** - Las credenciales usando `key=` son inseguras y limitadas
3. **No reconoce plataformas nativas** - Capacitor Android/iOS envían tokens FCM que necesitan formato v1
4. **Inconsistencia con resto del código** - `HandlesPushNotifications` ya usa HTTP v1 correctamente

---

## ✅ Solución Implementada (DESPUÉS)

### Cambios Principales:

#### 1. **Usar Kreait Firebase Factory**
```php
use Kreait\Firebase\Factory;

protected function initializeFirebaseMessaging()
{
    $factory = (new Factory)->withServiceAccount($this->credentialsPath);
    $this->messaging = $factory->createMessaging();  // HTTP v1 automáticamente
}
```

**Ventajas:**
- ✅ OAuth 2.0 automático (Kreait maneja tokens internamente)
- ✅ Usa credenciales del archivo JSON (offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json)
- ✅ Compatible con HTTP v1 de Google
- ✅ Mismo patrón usado en `HandlesPushNotifications`

#### 2. **Formato de Mensaje HTTP v1**
```php
// ✅ ACTUAL - Formato HTTP v1
$message = [
    'notification' => ['title' => $title, 'body' => $body],
    'data' => $data,
    'token' => $deviceToken,  // Campo correcto en v1
    
    // ✅ Opciones específicas por plataforma
    'android' => ['priority' => 'high', ...],
    'apns' => [/* iOS specific */],
    'webpush' => [/* Web specific */],
];

$this->messaging->send($message);
```

**Cambios clave:**
- Campo `token` en lugar de `to`
- Campos `android`, `apns`, `webpush` para opciones específicas por plataforma
- Mejor control de prioridad, canales, sonidos, etc.

#### 3. **Validación Mejorada**
```php
// ✅ Validación exhaustiva de token
if (empty($deviceToken) || strlen($deviceToken) < 50) {
    Log::warning('⚠️  Token de dispositivo inválido...');
    return false;
}
```

#### 4. **Logging con Emojis Diferenciados**
```php
Log::info('✅ Notificación push enviada exitosamente');
Log::error('❌ Error al enviar notificación push');
Log::warning('⚠️  Token de dispositivo inválido');
Log::info('📊 Envío batch de notificaciones completado');
```

---

## 📊 Comparación: API Legacy vs HTTP v1

| Aspecto | Legacy (fcm/send) | HTTP v1 (ACTUAL) |
|--------|-------------------|-----------------|
| **Endpoint** | `fcm.googleapis.com/fcm/send` | `fcm.googleapis.com/v1/...` |
| **Autenticación** | `key=SERVER_KEY` (inseguro) | `Authorization: Bearer {OAuth2}` |
| **Token field** | `to` | `token` |
| **Plataformas** | No diferenciadas | `android`, `apns`, `webpush` |
| **Prioridad** | `priority` (limitado) | `priority` + `channelId` + plataforma |
| **Estado Google** | ❌ Deprecated | ✅ Actual |
| **Soporte** | Podría fallar cualquier día | Garantizado por Google |

---

## 🔧 Nuevas Funciones del Servicio

### `sendPushNotification()`
**Uso:**
```php
$result = app(FCMService::class)->sendPushNotification(
    deviceToken: $token,
    title: 'Nuevo resultado',
    body: 'Tu predicción fue correcta',
    data: ['result_id' => 123, 'link' => '/results/123'],
    platform: 'android'  // web|android|ios
);
```

**Parámetros:**
- `$deviceToken` (string) - Token FCM del dispositivo
- `$title` (string) - Título de la notificación
- `$body` (string) - Cuerpo de la notificación
- `$data` (array) - Datos adicionales (default: [])
- `$platform` (string) - Plataforma: web|android|ios (default: web)

**Retorna:**
- `true` - Notificación enviada exitosamente
- `false` - Error (revisa logs para detalles)

### `sendPushNotificationBatch()`
**Uso:**
```php
$result = app(FCMService::class)->sendPushNotificationBatch(
    deviceTokens: [$token1, $token2, $token3],
    title: 'Anuncio',
    body: 'Evento próximo',
    platform: 'android'
);
// Retorna: ['success' => 2, 'failed' => 1]
```

---

## 🔐 Credenciales Requeridas

**Archivo:** `storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json`

Este archivo **DEBE EXISTIR** y contener:
```json
{
  "type": "service_account",
  "project_id": "offside-dd226",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----...",
  "client_email": "firebase-adminsdk-fbsvc@offside-dd226.iam.gserviceaccount.com",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "...",
  "client_x509_cert_url": "..."
}
```

**Nota:** Este archivo está en `.gitignore` (seguro) y se sincroniza directamente al servidor.

---

## 📝 Integración en Código

### Opción 1: Usar el Servicio Directamente
```php
// En cualquier controlador o job
$fcm = app(FCMService::class);
$fcm->sendPushNotification(
    $user->pushSubscriptions->first()->device_token,
    'Nuevo resultado',
    'Tu predicción fue exacta',
    ['result_id' => $result->id],
    $user->pushSubscriptions->first()->platform
);
```

### Opción 2: Usar HandlesPushNotifications Trait (RECOMENDADO)
```php
// Trait ya implementado, más completo
class NotificationJob
{
    use HandlesPushNotifications;
    
    public function handle()
    {
        $users = User::whereHas('pushSubscriptions')->get();
        $this->sendPushNotificationToGroupUsers(
            $group,
            'Nuevo evento',
            'Se agregó un evento importante',
            ['link' => '/events/123']
        );
    }
}
```

**El trait `HandlesPushNotifications` hace lo mismo internamente** y es la forma recomendada porque:
- ✅ Itera sobre múltiples suscripciones por usuario
- ✅ Diferencia las plataformas (web, android, ios)
- ✅ Mejor error handling
- ✅ Manejo de exclusiones de usuarios

---

## 🧪 Verificación / Testing

### 1. **Verificar Inicialización**
```php
// Terminal
php artisan tinker
>>> $fcm = app(\App\Services\FCMService::class);
>>> // Si no hay error, inicializó correctamente
```

### 2. **Probar Envío de Notificación**
```php
// Terminal
>>> $token = User::first()->pushSubscriptions->first()->device_token;
>>> $result = $fcm->sendPushNotification($token, 'Test', 'Body');
>>> dd($result);  // true si fue exitoso
```

### 3. **Ver Logs**
```bash
# Terminal
tail -f storage/logs/laravel.log | grep "notificación\|❌\|✅"
```

### 4. **Test en API**
```bash
# POST /api/admin/test-notification (si existe endpoint)
curl -X POST http://localhost:8000/api/admin/test-notification \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1}'
```

---

## 📦 Cambios Resumidos

**Archivo:** `app/Services/FCMService.php`
- **Lines:** +188, -44 (refactorización completa)
- **Compatibilidad:** Backward compatible (misma interfaz pública)
- **Breaking Changes:** Ninguno

**Métodos:**
- ✅ `__construct()` - Inicializa Firebase
- ✅ `initializeFirebaseMessaging()` - Setup de Kreait (privado)
- ✅ `sendPushNotification()` - Envía a un dispositivo
- ✅ `sendPushNotificationBatch()` - Nuevo, envía a múltiples (utility)

**Eliminados:**
- ❌ `$serverKey` - Ya no usamos API Key
- ❌ `$fcmUrl` con endpoint deprecated - Kreait maneja URL v1 internamente

---

## 🚨 Errores Comunes y Soluciones

### Error: "Firebase credentials not found"
```
❌ Archivo de credenciales de Firebase no encontrado en: storage/app/...json
```
**Solución:**
- Verificar que el archivo JSON existe en `storage/app/`
- Verificar ruta en `$credentialsPath`
- Descargar credenciales de Firebase Console si no existen

### Error: "Invalid JSON in credentials file"
**Solución:**
- Descargar nuevamente el archivo JSON desde Firebase Console
- Verificar que no tenga BOM (Byte Order Mark)

### Token inválido o muy corto
```
⚠️  Token de dispositivo inválido o muy corto
```
**Solución:**
- Verificar que el token viene del cliente correcto
- Verificar que el cliente ejecutó `window.initializePushNotifications()`
- Tokens válidos tienen típicamente 150+ caracteres

---

## 🎓 Conclusión

**PASO 7 completado:** FCMService ahora usa HTTP v1 de Google con OAuth 2.0 automático.

**Estado del Proyecto:**
- ✅ PASO 1-4: Frontend services (JavaScript)
- ✅ PASO 7: Backend service (PHP/FCMService)
- ⏳ PASO 8-10: Configuración Android

**Próximo Paso:** PASO 8 - Actualizar AndroidManifest.xml con metadata de Firebase

---

**Referencia:**
- Firebase HTTP v1 API: https://firebase.google.com/docs/cloud-messaging/migrate-v1
- Kreait Firebase PHP: https://github.com/kreait/firebase-php
