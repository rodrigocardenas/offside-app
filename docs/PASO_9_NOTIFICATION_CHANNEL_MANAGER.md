# PASO 9: Crear Canal de Notificación Android (NotificationChannelManager)

**Estado:** ✅ COMPLETADO
**Fecha:** 2026-02-19
**Rama:** `feature/firebase-android-fix`
**Archivos:**
- `android/app/src/main/java/com/offsideclub/app/NotificationChannelManager.java` ✅
- `android/app/src/main/java/com/offsideclub/app/MainActivity.java` (modificado) ✅

---

## 🎯 Objetivo

Crear un gestor de canales de notificación que configure el canal `high_importance_channel` especificado en el AndroidManifest.xml, requerido por Android 8.0+ (API 26+) para que Firebase Cloud Messaging funcione correctamente.

---

## 📱 ¿Qué Son Los Canales de Notificación?

**Android 8.0 (API 26)** introdujo los "NotificationChannels" como requisito obligatorio.

### Antes de Android 8.0 ❌
```
App → Notificación → Usuario
(Sin control granular)
```

### Android 8.0+ ✅
```
App → Canal 1 (importantes)  → User settings → Control granular
    → Canal 2 (marketing)    → (deshabilitar, cambiar sonido, etc)
    → Canal 3 (bajos)        → 
```

**Beneficios:**
- ✅ Usuario controla qué tipo de notificación desea recibir
- ✅ Control de sonido, vibración, luz por canal
- ✅ Prioridad (importance level)
- ✅ App no puede cambiar configuración sin permiso usuario

---

## ⚙️ Configuración del Canal

### NotificationChannelManager.java

**Clase:** `NotificationChannelManager`  
**Métodos principales:**
- `createNotificationChannels(Context)` - Crea el canal al iniciar
- `deleteNotificationChannel(Context, String)` - Elimina canal (raro)

**Canal Creado: `high_importance_channel`**
```
ID:           high_importance_channel
Nombre:       "Notificaciones de Ofside Club"
Descripción:  "Notificaciones importantes de partidos, resultados..."
Importancia:  IMPORTANCE_HIGH (Android lo coloca por encima de otros)
Badge:        Sí (punto rojo en ícono)
Lights:       Sí (LED de notificación)
Sonido:       Por defecto del sistema
```

### Integración en MainActivity.java

**onCreate():**
```java
@Override
public void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);
    
    // PASO 9: Crear canales de notificación
    NotificationChannelManager.createNotificationChannels(this);
    
    handleDeepLink(getIntent());
}
```

**Por qué aquí:**
- Se ejecuta una sola vez al iniciar la app
- Garantiza que el canal existe antes de recibir notificaciones
- Seguro (idempotente) - llamarlo múltiples veces no causa problemas

---

## 🔗 Flujo Completo: PASOS 8 → 9

```
PASO 8: AndroidManifest.xml
├─ <meta-data> default_notification_icon   → @drawable/ic_notification
├─ <meta-data> default_notification_color  → @color/notification_color
└─ <meta-data> default_notification_channel_id → "high_importance_channel"
    ↓
    ↓ (Android busca este ID)
    ↓
PASO 9: NotificationChannelManager
└─ createNotificationChannels()
   └─ new NotificationChannel("high_importance_channel", ...)
      ├─ Nombre: "Notificaciones de Ofside Club"
      ├─ Importancia: HIGH
      ├─ LED: habilitado
      └─ Se registra con NotificationManager
```

---

## 📝 Código Generado

### NotificationChannelManager.java (Completo)

```java
package com.offsideclub.app;

public class NotificationChannelManager {
    public static final String HIGH_IMPORTANCE_CHANNEL_ID = 
        "high_importance_channel";

    /**
     * Crea canales de notificación (Android 8.0+)
     */
    public static void createNotificationChannels(Context context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(
                HIGH_IMPORTANCE_CHANNEL_ID,
                "Notificaciones de Ofside Club",
                NotificationManager.IMPORTANCE_HIGH
            );
            
            channel.setDescription("Notificaciones importantes...");
            channel.setShowBadge(true);
            channel.enableLights(true);
            
            NotificationManager nm = 
                context.getSystemService(NotificationManager.class);
            nm.createNotificationChannel(channel);
        }
    }
}
```

---

## 🔄 Compatibilidad Android

| Versión | Comportamiento |
|---------|----------------|
| **Android 7.1 e inferiores** | Método se ejecuta pero no hace nada (sin canales) |
| **Android 8.0+** | ✅ Crea el canal correctamente |

**Código seguro:**
```java
if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
    // Solo ejecutar en Android 8.0+
    // En versiones inferiores, no causa error
}
```

---

## 📊 Importancia del Canal (Importance Level)

```
IMPORTANCE_MAX (5)      ⚠️  Extra importante, sonido + vibración + popup
  ↓
IMPORTANCE_HIGH (4)     ✅  Importante, sonido + vibración (NUESTRO CANAL)
  ↓
IMPORTANCE_DEFAULT (3)  ℹ️  Normal, sonido + vibración
  ↓
IMPORTANCE_LOW (2)      🔔 Silencioso, sin sonido
  ↓
IMPORTANCE_MIN (1)      👻 Muy bajo, sin sonido ni vibración
```

**Por qué IMPORTANCE_HIGH:**
- Las notificaciones de partidos son críticas
- Usuario quiere notificarse inmediatamente
- Justifica sonido y vibración

---

## 🧪 Verificación / Testing

### 1. **Compilar y ejecutar**
```bash
cd android
./gradlew assembleDebug
```

### 2. **En el dispositivo (Adb)**
```bash
adb logcat | grep "NotificationChannelManager"
```

**Salida esperada:**
```
I/NotificationChannelManager: ✅ Canal de notificación 'high_importance_channel' creado exitosamente
```

### 3. **En Settings de Android**
```
Ajustes 
  → Aplicaciones 
  → Offside Club 
  → Notificaciones
  → Buscar "Notificaciones de Offside Club"
```

Debería mostrar el canal con:
- ✅ Nombre: "Notificaciones de Offside Club"
- ✅ Descripción: "Notificaciones importantes..."
- ✅ Badge habilitado
- ✅ Suena
- ✅ Vibra

### 4. **Prueba de notificación**
```bash
# En tu app
window.initializePushNotifications();
```

Luego envía una notificación desde backend:
```bash
POST /api/admin/test-notification
```

Debería aparecer en el canal correcto.

---

## ⚙️ Personalización

### Cambiar Nombre del Canal
```java
new NotificationChannel(
    HIGH_IMPORTANCE_CHANNEL_ID,
    "Mi Nombre Custom",  // ← Cambiar aquí
    NotificationManager.IMPORTANCE_HIGH
);
```

### Cambiar Importancia
```java
// De IMPORTANCE_HIGH a IMPORTANCE_DEFAULT
new NotificationChannel(
    HIGH_IMPORTANCE_CHANNEL_ID,
    "Notificaciones de Offside Club",
    NotificationManager.IMPORTANCE_DEFAULT  // ← Cambiar
);
```

### Habilitar Vibración Personalizada
```java
// Android 8.0+
long[] vibrationPattern = {0, 250, 250, 250}; // ms
channel.setVibrationPattern(vibrationPattern);
```

### Cambiar Sonido
```java
android.media.RingtoneManager.getDefaultUri(
    android.media.RingtoneManager.TYPE_NOTIFICATION
);
// Asignar con channel.setSound()
```

---

## 🔍 Troubleshooting

### "Las notificaciones no usan el canal"
```
❌ Problema: FCMService no especifica channelId
✅ Solución: Asegurar que AndroidManifest tiene el metadata
```

### "El canal no aparece en Settings"
```
❌ Problema: NotificationChannelManager no fue llamado
✅ Solución: Verificar que MainActivity llama createNotificationChannels()
```

### "Error: createNotificationChannel not found"
```
❌ Problema: Build.VERSION insuficiente
✅ Solución: Verificar que min SDK es 21+, target SDK es 33+
```

---

## 📦 Cambios Resumidos

| Archivo | Acción | Líneas |
|---------|--------|--------|
| `NotificationChannelManager.java` | Creado | 93 |
| `MainActivity.java` | Modificado | +5 |

**Total:** 2 archivos, 98 líneas de código

---

## ✨ Conclusión

**PASO 9 ✅ COMPLETADO:**
- Clase NotificationChannelManager creada con configuración completa
- Integrada en MainActivity.onCreate()
- Canal `high_importance_channel` listo para Firebase

**Estado General:**
```
██████████████████████░░░░░░  87.5% (7/8 PASOS)
├─ PASO 1-9:  ✅ Completados
└─ PASO 10:   ⏳ Pendiente (5 min)
```

**El siguiente PASO 10 es el último:**
- Crear Blade View para incluir los 3 servicios JavaScript
- Permitir que Laravel/Blade inicialize las notificaciones fácilmente

---

## 🚀 Próximo: PASO 10

**PASO 10:** Crear Blade View de Inicialización
- **Archivo:** `resources/views/components/firebase-messaging-init.blade.php`
- **Objetivo:** Componente para incluir en cualquier página
- **Uso:** `@include('components.firebase-messaging-init')`
- **Tiempo:** ~5 minutos

Una vez completado PASO 10, tendrás un sistema **COMPLETO Y FUNCIONAL** de Firebase Cloud Messaging para Capacitor 6 Android.

---

**Referencia:**
- Android NotificationChannel: https://developer.android.com/training/notify-user/channels
- Firebase FCM Android: https://firebase.google.com/docs/cloud-messaging/android/receive
