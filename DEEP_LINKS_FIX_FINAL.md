# Solución Final: Deep Links en Android

## El Problema

El manifest está correctamente compilado en la APK con los intent-filters. **El problema es que Android tiene Chrome como handler prioritario** para URLs HTTPS, y Chrome está capturando todos los links antes de darle oportunidad a nuestra app.

## La Solución

### Paso 1: Verificar que DeepLinks están listos
Los siguientes intent-filters están compilados en la APK:

```xml
<!-- Custom scheme -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="offsideclub" />
</intent-filter>

<!-- HTTPS URLs -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data
        android:host="app.offsideclub.es"
        android:scheme="https" />
</intent-filter>

<!-- BroadcastReceiver interceptor (máxima prioridad) -->
<receiver android:name="com.offsideclub.app.DeepLinkReceiver" android:exported="true">
    <intent-filter android:priority="999">
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data android:host="app.offsideclub.es" android:scheme="https" />
    </intent-filter>
</receiver>
```

### Paso 2: Configurar OffsideClub como handler preferido (CRÍTICO)

En tu dispositivo Android:

1. **Abre Settings (Configuración)**
2. **Ve a: Apps > Default apps** (o "Aplicaciones predeterminadas")
3. **Busca "Opening links"** (o "Abrir enlaces")
4. **Busca "app.offsideclub.es"**
5. **Cambia de Chrome a OffsideClub**

**O usa la terminal:**

```bash
adb shell cmd package set-default-browser com.offsideclub.app
```

Esto le dice a Android que OffsideClub es la app preferida para abrir links de app.offsideclub.es.

### Paso 3: Probar

1. Desinstala completamente la app anterior
2. Instala la nueva APK
3. **Configura OffsideClub como handler preferido** (Paso 2)
4. Abre WhatsApp
5. Haz clic en un link de invitación

**Resultado esperado:**
- Se abre OffsideClub (NO Chrome)
- Se abre la página de invitación

### Alternativa si no funciona

Si sigue sin funcionar después de configurar el handler preferido:

```bash
# Ver handlers disponibles
adb shell cmd package query-activities --brief com.offsideclub.app

# Limpiar preferencias de links
adb shell pm set-default-app com.offsideclub.app https app.offsideclub.es

# Ver status actual
adb shell pm get-app-link com.offsideclub.app
```

## ¿Por qué Chrome tiene prioridad?

Android tiene un sistema de "preferred apps" donde los usuarios pueden elegir qué aplicación abre qué tipo de links. Por defecto, Chrome es el preferido para la mayoría de URLs HTTPS.

Nuestra app **tiene los intent-filters correctamente compilados** - el problema es que Android simplemente elige Chrome porque el usuario (o el sistema) lo tiene configurado como preferido.

## Confirmación Técnica

El manifest compilado en la APK contiene:
- ✅ Custom scheme `offsideclub://`  
- ✅ HTTPS URLs para `app.offsideclub.es`
- ✅ BroadcastReceiver interceptor con prioridad 999
- ✅ MainActivity con `launchMode="singleTop"`
- ✅ Manejo de `onNewIntent()` para deep links

Todo está técnicamente correcto. El último paso es configurar Android para usar nuestra app.

