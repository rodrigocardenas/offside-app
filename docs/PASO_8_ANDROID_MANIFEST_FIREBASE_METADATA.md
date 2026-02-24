# PASO 8: Actualizar AndroidManifest.xml con Metadatos de Firebase

**Estado:** ✅ COMPLETADO
**Fecha:** 2026-02-19
**Rama:** `feature/firebase-android-fix`
**Archivos:**
- `android/app/src/main/AndroidManifest.xml` ✅
- `android/app/src/main/res/drawable/ic_notification.xml` ✅
- `android/app/src/main/res/values/colors.xml` ✅

---

## 🎯 Objetivo

Configurar los metadatos de Firebase Cloud Messaging en el AndroidManifest.xml para que Android 13+ maneje correctamente las notificaciones push con ícono, color y canal de notificación por defecto.

---

## 🔧 Cambios Realizados

### 1. **AndroidManifest.xml** - Agregar Metadatos Firebase

**Ubicación:** `android/app/src/main/AndroidManifest.xml` (dentro de `<application>`)

```xml
<!-- Firebase Cloud Messaging Configuration (PASO 8) -->
<!-- Default notification icon para push notifications -->
<meta-data
    android:name="com.google.firebase.messaging.default_notification_icon"
    android:resource="@drawable/ic_notification" />

<!-- Default notification color para push notifications -->
<meta-data
    android:name="com.google.firebase.messaging.default_notification_color"
    android:resource="@color/notification_color" />

<!-- Default notification channel ID -->
<meta-data
    android:name="com.google.firebase.messaging.default_notification_channel_id"
    android:value="high_importance_channel" />
```

### 2. **ic_notification.xml** - Ícono de Notificación

**Ubicación:** `android/app/src/main/res/drawable/ic_notification.xml`

```xml
<vector xmlns:android="http://schemas.android.com/apk/res/android"
    android:width="24dp"
    android:height="24dp"
    android:viewportWidth="24"
    android:viewportHeight="24">
    <!-- Bell icon - Icon de campana -->
    <path android:fillColor="#000000"
        android:pathData="M12,22C13.1,22 14,21.1 14,20H10C10,21.1 10.9,22 12,22ZM18,16V11C18,7.93 16.36,5.24 13.5,4.68V4C13.5,3.2 12.81,2.5 12,2.5C11.19,2.5 10.5,3.2 10.5,4V4.68C7.64,5.24 6,7.93 6,11V16L4,18V19H20V18L18,16Z" />
</vector>
```

**Características:**
- ✅ Vector escalable (24x24 dp)
- ✅ Ícono de campana (bell icon)
- ✅ Debe estar en escala de grises (se coloriza con `notification_color`)
- ✅ Será mostrado en la barra de estado de Android

### 3. **colors.xml** - Color de Notificación

**Ubicación:** `android/app/src/main/res/values/colors.xml`

```xml
<color name="notification_color">#1F77D2</color>
```

**Uso:**
- ✅ Colorea el ícono `ic_notification` en la barra de estado
- ✅ Usado internamente por Android para tintear el ícono
- ✅ Puede cambiar el color editando el valor hex

---

## 📋 Requisitos de Android para Notificaciones

### Ícono de Notificación (`default_notification_icon`)
```
❌ NO PERMITIDO                    ✅ PERMITIDO
- Colores (RGB)                    - Escalas de grises
- Transparencias complejas         - Transparencia simple
- Imágenes escaneadas              - Vectores
- Tamaños irregular               - Cuadrados/círculos
```

**Por qué:** Android usa el ícono como máscara. El color viene de `notification_color`, no del ícono mismo.

### Color de Notificación (`default_notification_color`)
- Debe ser un color hexadecimal válido en `colors.xml`
- Se aplica como tinte al ícono
- Típicamente usa el color primario de la app

### Canal de Notificación (`default_notification_channel_id`)
- Definido aquí como `high_importance_channel`
- Se crea/define completamente en PASO 9
- Usuarios pueden cambiar comportamiento del canal en Settings → Apps → Notificaciones

---

## 🔄 Flujo de Notificación en Android 13+

```
Backend (FCMService.php)
    ↓
Firebase Cloud Messaging
    ↓
Android Device (Push Token)
    ↓
Capacitor Firebase Plugin
    ↓
AndroidManifest.xml Metadata
    ├─ icon: @drawable/ic_notification
    ├─ color: @color/notification_color  
    └─ channel: high_importance_channel
    ↓
Android Notification System
    ↓
User's Notification Tray
```

---

## ✅ Validación de Cambios

### 1. **Archivo AndroidManifest.xml**
```bash
grep -A3 "com.google.firebase.messaging" \
  android/app/src/main/AndroidManifest.xml
```

**Salida esperada:**
```xml
<meta-data
    android:name="com.google.firebase.messaging.default_notification_icon"
    android:resource="@drawable/ic_notification" />
```

### 2. **Archivo ic_notification.xml existe**
```bash
ls -lh android/app/src/main/res/drawable/ic_notification.xml
```

**Salida esperada:**
```
-rw-r--r-- ... ic_notification.xml (1.2K)
```

### 3. **Archivo colors.xml existe y tiene color**
```bash
grep "notification_color" \
  android/app/src/main/res/values/colors.xml
```

**Salida esperada:**
```xml
<color name="notification_color">#1F77D2</color>
```

---

## 🎨 Personalización

### Cambiar Color de Notificación
```xml
<!-- android/app/src/main/res/values/colors.xml -->
<color name="notification_color">#FF6B35</color>  <!-- Naranja -->
<!-- o -->
<color name="notification_color">#FF0000</color>  <!-- Rojo -->
```

### Cambiar Ícono de Notificación
Edita `android/app/src/main/res/drawable/ic_notification.xml` con tu propio SVG/vector drawable.

**Requisitos:**
- Tamaño: 24x24 dp (viewportWidth=24, viewportHeight=24)
- Solo escala de grises (#000000 para color)
- Diseño simple y reconocible

**Ejemplo alternativo - Círculo simple:**
```xml
<vector xmlns:android="http://schemas.android.com/apk/res/android"
    android:width="24dp"
    android:height="24dp"
    android:viewportWidth="24"
    android:viewportHeight="24">
    <path
        android:fillColor="#000000"
        android:pathData="M12,2C6.48,2 2,6.48 2,12C2,17.52 6.48,22 12,22C17.52,22 22,17.52 22,12C22,6.48 17.52,2 12,2Z" />
</vector>
```

---

## 📱 Prueba en Dispositivo

### 1. **Build APK**
```bash
cd android
./gradlew build
```

### 2. **Instalar en dispositivo/emulador**
```bash
# Usar Android Studio o
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

### 3. **Probar notificación**
```bash
# Abrir DevTools en el emulador y ejecutar:
window.initializePushNotifications();
```

### 4. **Enviar notificación desde backend**
```bash
# POST /api/admin/test-notification (si existe)
curl -X POST http://backend.local/api/admin/test-notification \
  -H "Authorization: Bearer TOKEN"
```

### 5. **Verificar en barra de estado**
- Notificación aparecerá con el ícono `ic_notification`
- Coloreada con `notification_color`
- En el canal `high_importance_channel`

---

## 🔍 Troubleshooting

### "Ícono no aparece en notificación"
```
❌ Problema: ic_notification.xml tiene colores
✅ Solución: Usar solo #000000 (negro) - será coloreado por Android
```

### "Color de notificación no se aplica"
```
❌ Problema: Color no existe en colors.xml
✅ Solución: Verificar que colors.xml tiene <color name="notification_color">
```

### "Notificación no usa el canal especificado"
```
❌ Problema: Canal no existe en NotificationManager
✅ Solución: PASO 9 crea el canal automáticamente en Java
```

### "BuildException - ícono no encontrado"
```
❌ Problema: Ruta @drawable/ic_notification no existe
✅ Solución: Verificar que ic_notification.xml está en drawable/
```

---

## 🚀 Próximo Paso: PASO 9

PASO 9 creará el archivo de configuración del canal de notificación:
- **Archivo:** `android/app/src/main/java/com/offsideclub/app/NotificationChannelManager.java`
- **Objetivo:** Crear el canal `high_importance_channel` con configuración completa
- **Tiempo:** ~10 minutos

El canal define:
- Nombre visible al usuario
- Importancia (high/max/default/low/min)
- Sonido por defecto
- Vibración
- Light (LED)

---

## 📦 Resumen de Cambios

| Archivo | Acción | Líneas |
|---------|--------|--------|
| `AndroidManifest.xml` | Modificado | +11 (metadatos Firebase) |
| `ic_notification.xml` | Creado | 19 (vector drawable) |
| `colors.xml` | Creado | 13 (colores) |

**Total:** 3 archivos, 43 líneas nuevas

---

## ✨ Conclusión

**PASO 8 ✅ COMPLETADO:**
- AndroidManifest.xml configurado con metadatos Firebase
- Ícono de notificación creado (vector bell icon)
- Color de notificación definido (#1F77D2 azul primario)

**Estado General:**
```
████████████████████░░░░░░░░░░░░  75% (6/8 PASOS)
├─ PASO 1-7: ✅ Completados
├─ PASO 8:   ✅ Completado (JUSTO AHORA)
├─ PASO 9:   ⏳ Pendiente (10 min)
└─ PASO 10:  ⏳ Pendiente (10 min)
```

Al terminar PASO 10, tendrás una **implementación completa y funcional** de Firebase Cloud Messaging para Android en Capacitor 6.
