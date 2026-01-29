# Android App Links Automáticos - Solución Definitiva

## Visión General

Esta es la **solución de oro actual en Android** para deep links. A diferencia del enfoque anterior (que requería que el usuario configure la app manualmente), los **Android App Links automáticos** permiten que Android verifique automáticamente que tu app es la propietaria del dominio y abre los links directamente **sin preguntar al usuario**.

## ¿Cómo Funciona?

### 1. **Flujo de Verificación**

```
1. Usuario instala app
2. Android busca assetlinks.json en tu servidor
   └─ https://app.offsideclub.es/.well-known/assetlinks.json
3. Android verifica que:
   ✅ El archivo existe
   ✅ El SHA256 en el archivo coincide con tu app
4. Android marca la app como "verified" para ese dominio
5. Todos los links HTTPS de ese dominio abren en tu app
   └─ Sin diálogos, sin preguntas, automático
```

### 2. **Partes Necesarias**

#### A. Archivo `assetlinks.json` en el servidor
📁 **Ubicación:** `public/.well-known/assetlinks.json`

```json
{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.offsideclub.app",
    "sha256_cert_fingerprints": [
      "75:2E:20:AE:6E:13:E4:16:C4:DD:CC:A8:51:0B:92:DD:12:5F:AE:44:0E:93:A6:21:55:18:73:0D:23:01:D5:84"
    ]
  }
}
```

**Qué significa cada parte:**
- `delegate_permission/common.handle_all_urls` = "Esta app maneja todos los links del dominio"
- `package_name` = El nombre del paquete de tu app (`com.offsideclub.app`)
- `sha256_cert_fingerprints` = El SHA256 del certificado de firma de la app

#### B. AndroidManifest.xml con `autoVerify="true"`
📁 **Ubicación:** `android/app/src/main/AndroidManifest.xml`

```xml
<!-- Con autoVerify, Android verifica automáticamente -->
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data
        android:scheme="https"
        android:host="app.offsideclub.es" />
</intent-filter>
```

**Qué hace:**
- `android:autoVerify="true"` = "Android, por favor verifica automáticamente este dominio"

## Estado Actual en OffsideClub

### ✅ Configuración Correcta

| Componente | Estado | Detalles |
|-----------|--------|----------|
| **assetlinks.json** | ✅ Presente | En `public/.well-known/assetlinks.json` |
| **Ubicación correcta** | ✅ Correcto | URL: `https://app.offsideclub.es/.well-known/assetlinks.json` |
| **Package name** | ✅ Correcto | `com.offsideclub.app` |
| **SHA256 fingerprint** | ✅ Presente | Configurado en el archivo |
| **AndroidManifest.xml** | ✅ Actualizado | Ahora incluye `android:autoVerify="true"` |
| **HTTPS intent-filter** | ✅ Correcto | Apunta a `app.offsideclub.es` |

### Verificación

Verifica que el archivo sea accesible:
```bash
curl -v https://app.offsideclub.es/.well-known/assetlinks.json
```

Debería retornar:
```json
{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.offsideclub.app",
    "sha256_cert_fingerprints": ["75:2E:20:AE:..."]
  }
}
```

## Flujo de Funcionamiento

### Antes (Manual - Lo Viejo)

```
1. Usuario instala app
2. Usuario abre link en WhatsApp
3. Android muestra: "¿Abrir con Chrome o OffsideClub?"
4. Usuario selecciona OffsideClub
5. (Opcional) Usuario configura en Settings como predeterminado
6. Link se abre en app
```

### Ahora (Automático - App Links)

```
1. Usuario instala app
2. Android descarga assetlinks.json desde nuestro servidor
3. Android verifica que el SHA256 coincide
4. Android marca app como "verified" para app.offsideclub.es
5. Usuario abre link en WhatsApp
6. Android AUTOMÁTICAMENTE abre en OffsideClub
   └─ Sin diálogos, sin preguntas
7. Link se abre en app
```

## Ventajas

| Aspecto | Manual | App Links Automático |
|---------|--------|----------------------|
| **Configuración Usuario** | ❌ Requiere settings | ✅ Automático |
| **Diálogos** | ❌ Pregunta qué app | ✅ Sin preguntas |
| **UX** | ❌ Confuso | ✅ Seamless |
| **Seguridad** | ⚠️ Confía en usuario | ✅ Verificado por Google |
| **Estándar Moderno** | ❌ Deprecated | ✅ Recomendado |
| **iOS/Web** | N/A | ✅ Similar con Universal Links |

## Implementación Paso a Paso

### 1. Verificar Certificado de Firma

Tu `assetlinks.json` contiene el SHA256 del certificado con el que firmas la APK. Debe coincidir exactamente.

**Para verificar el SHA256 actual:**
```bash
cd android
./gradlew signingReport
```

O si usas `keystore`:
```bash
keytool -list -v -keystore ~/.android/debug.keystore
```

### 2. Actualizar assetlinks.json (si cambió el certificado)

Si compilaste con un nuevo certificado, actualiza el SHA256 en `public/.well-known/assetlinks.json`.

### 3. Compilar APK

```bash
npm run build
npx cap sync android
cd android
./gradlew assembleRelease
```

### 4. Instalar y Probar

```bash
adb install android/app/build/outputs/apk/release/app-release.apk
```

**Para probar automáticamente:**
```bash
# Abrir un link de invitación
adb shell am start -a android.intent.action.VIEW -d "https://app.offsideclub.es/invite/ABC123"

# Debería abrir OffsideClub automáticamente sin pregunta
```

### 5. Verificar Verificación en Android

```bash
# Ver si Android verificó la app
adb shell pm get-app-links com.offsideclub.app

# Debería mostrar:
# com.offsideclub.app:
#   ID: app.offsideclub.es
#   Status: always (✅ Verificado!)
```

## Debugging

### Si no funciona automático:

```bash
# 1. Ver estado de verificación
adb shell pm get-app-links com.offsideclub.app

# 2. Ver logs de verificación
adb logcat | grep "AppLinks"

# 3. Reset de verificación (para testing)
adb shell pm set-app-links com.offsideclub.app all ask

# 4. Verificar de nuevo
adb shell pm verify-app-links com.offsideclub.app
```

### Verificaciones de la Web

Usa las herramientas de Google para verificar:
1. Entra a: https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls

2. Debería retornar:
```json
{
  "linked": true
}
```

## Seguridad

### ¿Qué protege esto?

1. **Verificación de dominio:** Solo la app compilada con el certificado correcto puede abrir links de `app.offsideclub.es`
2. **Prevención de spoofing:** Otra app no puede hacerse pasar por la tuya
3. **Google lo verifica:** El Digital Asset Links API verifica automáticamente

### Flujo de seguridad:

```
Malware intenta abrir link
├─ Intenta usar package_name "com.offsideclub.app"
├─ Pero tiene diferente SHA256
├─ Android dice "No, no eres tú"
└─ Link se abre en Chrome (fallback seguro)
```

## Cambios Realizados

### 1. **AndroidManifest.xml**
✅ Agregado `android:autoVerify="true"` al intent-filter HTTPS

```diff
- <intent-filter>
+ <intent-filter android:autoVerify="true">
```

### 2. **assetlinks.json**
✅ Ya estaba correctamente configurado en `public/.well-known/assetlinks.json`

## Next Steps

### 1. Rebuild necesario
```bash
npm run build
npx cap sync android
cd android
./gradlew assembleRelease
```

### 2. Testing
```bash
# Instalar APK nueva
adb install -r android/app/build/outputs/apk/release/app-release.apk

# Esperar a que Android descargue y verifique (puede tardar minutos)

# Probar link
adb shell am start -a android.intent.action.VIEW -d "https://app.offsideclub.es/invite/TEST"

# Debería abrir directamente en OffsideClub
```

### 3. Despliegue
- Compilar APK con certificado de producción
- Subir a Play Store
- Google automáticamente verifica contra assetlinks.json

## Comparación: Dialogo Manual vs App Links

### Con el diálogo manual (actual):
1. ✅ Funciona
2. ⚠️ Requiere que el usuario haga setup
3. ❌ No es automático

### Con App Links (nueva):
1. ✅ Funciona
2. ✅ Automático desde el principio
3. ✅ Estándar moderno de Google
4. ✅ Sin fricción para el usuario

## Conclusión

**Recomendación:** Proceder con esta implementación. Ya tienes:
- ✅ `assetlinks.json` correcto
- ✅ Certificado configurado
- ✅ Manifest actualizado

**Próximo paso:** Compilar APK y subir a Play Store. Google automáticamente verifica y activará App Links automático.

---

**Nota:** El diálogo manual que implementamos anteriormente sirve como **fallback graceful** si por alguna razón la verificación automática falla. Pero con esta configuración, la mayoría de usuarios verán la apertura automática.

**Versión:** v1.078+  
**Status:** Listo para producción  
**Fecha:** 29 de Enero, 2026
