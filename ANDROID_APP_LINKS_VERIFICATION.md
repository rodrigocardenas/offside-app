# Verificación y Validación: Android App Links

## Checklist de Validación

### 1. Archivo assetlinks.json

- [ ] Existe en `public/.well-known/assetlinks.json`
- [ ] Es accesible en `https://app.offsideclub.es/.well-known/assetlinks.json`
- [ ] Contiene JSON válido (no errores de sintaxis)
- [ ] El `package_name` es `com.offsideclub.app`
- [ ] El `sha256_cert_fingerprints` está presente

### 2. SHA256 Fingerprint

- [ ] Coincide con el certificado de la app
- [ ] No tiene espacios extra o caracteres inválidos
- [ ] Es de 40 caracteres hexadecimales: `XX:XX:XX:XX:...`

### 3. AndroidManifest.xml

- [ ] Intent-filter HTTPS tiene `android:autoVerify="true"`
- [ ] Host es exactamente `app.offsideclub.es`
- [ ] Scheme es `https`
- [ ] Incluye `android.intent.category.BROWSABLE`

### 4. Compilación

- [ ] APK compilada con el certificado correcto
- [ ] SHA256 del certificado coincide con assetlinks.json
- [ ] AndroidManifest.xml está compilado dentro de APK

---

## Validación Manual

### 1. Verificar accesibilidad de assetlinks.json

```bash
# Desde terminal
curl -v https://app.offsideclub.es/.well-known/assetlinks.json
```

**Esperado:**
```
< HTTP/1.1 200 OK
< Content-Type: application/json

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

### 2. Verificar formato JSON

```bash
# Validar JSON
curl https://app.offsideclub.es/.well-known/assetlinks.json | jq .
```

**Esperado:** Sin errores de sintaxis

### 3. Verificar SHA256 de certificado

**Para debug keystore:**
```bash
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
```

**Para release keystore:**
```bash
keytool -list -v -keystore /path/to/keystore.jks -alias your_alias -storepass your_password
```

**Buscar en output:**
```
SHA256: 75:2E:20:AE:6E:13:E4:16:C4:DD:CC:A8:51:0B:92:DD:12:5F:AE:44:0E:93:A6:21:55:18:73:0D:23:01:D5:84
```

Debe coincidir exactamente con lo en assetlinks.json

### 4. Verificar con Android Studio

**En AndroidManifest.xml:**
```xml
<intent-filter android:autoVerify="true">  <!-- ← Este atributo debe estar -->
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data
        android:scheme="https"
        android:host="app.offsideclub.es" />
</intent-filter>
```

### 5. Verificar con Google Digital Asset Links API

```bash
# Reemplaza con tus valores
curl "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls"
```

**Esperado:**
```json
{
  "linked": true
}
```

Si retorna `"linked": false`, hay un problema. Ver sección de troubleshooting.

---

## Verificación en Dispositivo

### 1. Instalar APK

```bash
adb install android/app/build/outputs/apk/release/app-release.apk
```

### 2. Esperar a que Android verifique

Android verifica en background, puede tardar **5-30 minutos**. Es normal.

### 3. Verificar Estado

```bash
# Ver estado de verificación
adb shell pm get-app-links com.offsideclub.app
```

**Esperado - Verificado:**
```
com.offsideclub.app:
  ID: app.offsideclub.es
  Status: always
  User set: false
```

**Status meanings:**
- `always` = ✅ Verificado y automático
- `ask` = ⚠️ Pregunta al usuario
- `never` = ❌ No configurado

### 4. Verificar manualmente (si quieres)

```bash
# Forzar re-verificación
adb shell pm verify-app-links com.offsideclub.app

# Ver logs
adb logcat | grep AppLinks
```

---

## Troubleshooting

### Problema 1: "linked": false en Google API

**Causa:** assetlinks.json no está accesible o tiene error

**Solución:**
```bash
# 1. Verificar acceso
curl https://app.offsideclub.es/.well-known/assetlinks.json

# 2. Verificar JSON
curl https://app.offsideclub.es/.well-known/assetlinks.json | jq .

# 3. Verificar headers
curl -v https://app.offsideclub.es/.well-known/assetlinks.json | grep -i "content-type"
```

El Content-Type debe ser `application/json` (o `text/plain`, también funciona)

### Problema 2: Status "ask" en dispositivo

**Causa:** SHA256 no coincide o autoVerify no está en manifest

**Solución:**
```bash
# 1. Verificar manifest
grep -r "autoVerify" android/app/src/main/AndroidManifest.xml

# 2. Verificar SHA256
keytool -list -v -keystore path/to/keystore.jks | grep SHA256

# 3. Comparar con assetlinks.json
cat public/.well-known/assetlinks.json | grep sha256

# 4. Si no coinciden, actualizar assetlinks.json con el SHA256 correcto
```

### Problema 3: APK no abre automático

**Causa:** Verificación aún en progreso o no coincide certificado

**Solución:**
```bash
# 1. Esperar 5-30 minutos y re-verificar
adb shell pm verify-app-links com.offsideclub.app

# 2. Ver logs
adb logcat | grep "AppLinks\|pkg="

# 3. Resetear y re-verificar
adb shell pm set-app-links com.offsideclub.app all ask
adb shell pm verify-app-links com.offsideclub.app
adb shell pm get-app-links com.offsideclub.app
```

### Problema 4: Content-Type incorrecto en servidor

**Causa:** Servidor configura Content-Type incorrecto para `.json`

**Solución (Laravel/public):**

En `public/.well-known/.htaccess`:
```apache
<Files "assetlinks.json">
    AddType application/json .json
</Files>
```

O en nginx config:
```nginx
location /.well-known/assetlinks.json {
    add_header Content-Type application/json;
}
```

---

## Estado Actual: OffsideClub

### ✅ Verificaciones Completadas

```
✅ Archivo assetlinks.json existe
   Ubicación: public/.well-known/assetlinks.json
   
✅ Formato JSON válido
   "relation": ["delegate_permission/common.handle_all_urls"]
   "package_name": "com.offsideclub.app"
   
✅ SHA256 configurado
   Fingerprint: 75:2E:20:AE:6E:13:E4:16:C4:DD:CC:A8:51:0B:92:DD:12:5F:AE:44:0E:93:A6:21:55:18:73:0D:23:01:D5:84
   
✅ AndroidManifest.xml actualizado
   android:autoVerify="true" presente
   Host: app.offsideclub.es
   Scheme: https
   
✅ Intent-filter correcto
   <intent-filter android:autoVerify="true">
       <data android:scheme="https" android:host="app.offsideclub.es" />
   </intent-filter>
```

### 🔍 Próximo paso: Rebuild y Testing

```bash
# 1. Compilar
npm run build
npx cap sync android

# 2. En Android folder
cd android
./gradlew assembleRelease

# 3. Instalar
adb install -r android/app/build/outputs/apk/release/app-release.apk

# 4. Esperar verificación
sleep 30

# 5. Verificar
adb shell pm get-app-links com.offsideclub.app

# 6. Debería mostrar Status: always ✅
```

---

## Validación en Play Store

Cuando subes a Play Store, Google automáticamente:

1. Descarga tu APK
2. Extrae el certificado
3. Descarga assetlinks.json desde tu servidor
4. Verifica que coinciden:
   ```
   APK SHA256 == assetlinks.json SHA256 ✅
   APK package == assetlinks.json package ✅
   Dominio está accesible ✅
   ```
5. Activa App Links si todo es correcto
6. Tu app sale con ✓ App Links

---

## Monitoreo Continuo

### Verificar regularmente

```bash
# Script de monitoreo
curl -s "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls" | jq .

# Si retorna true: ✅ Listo
# Si retorna false: ⚠️ Revisar assetlinks.json
```

### En caso de cambios

Si cambias:
- **Certificado de firma:** Actualizar SHA256 en assetlinks.json
- **Dominio:** Crear nuevo assetlinks.json en nuevo dominio
- **Package name:** Actualizar en assetlinks.json (aunque no lo recomiendo)

---

## Referencias

- [Official Android App Links docs](https://developer.android.com/training/app-links)
- [Digital Asset Links API](https://digitalassetlinks.googleapis.com/)
- [Google Play App Links documentation](https://support.google.com/googleplay/android-developer/answer/9325313)

---

**Versión:** v1.078+  
**Última actualización:** 29 de Enero, 2026  
**Status:** ✅ Completo
