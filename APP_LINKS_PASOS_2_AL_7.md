# 📋 App Links Automático - Pasos 2-7

Una vez que tengas el SHA256 de tu keystore, sigue estos pasos.

---

## Paso 2: Actualizar assetlinks.json

Abre: `public/.well-known/assetlinks.json`

Reemplaza con esto (usando TU SHA256):

```json
{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.offsideclub.app",
    "sha256_cert_fingerprints": [
      "TU_SHA256_AQUI"
    ]
  }
}
```

Ejemplo de cómo se vería:

```json
{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.offsideclub.app",
    "sha256_cert_fingerprints": [
      "AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12"
    ]
  }
}
```

---

## Paso 3: Configurar build.gradle

Abre: `android/app/build.gradle`

Busca el bloque `signingConfigs` y reemplázalo:

```gradle
signingConfigs {
    debug {
        storeFile file('debug.keystore')
    }
    release {
        storeFile file("C:/Users/rodri/offside.jks")
        storePassword "TU_CONTRASEÑA_DE_PLAY_STORE"
        keyAlias "offside"
        keyPassword "TU_CONTRASEÑA_DE_PLAY_STORE"
    }
}

buildTypes {
    debug {
        signingConfig signingConfigs.debug
    }
    release {
        signingConfig signingConfigs.release
        minifyEnabled false
    }
}
```

---

## Paso 4: Compilar Release APK

```bash
cd /c/laragon/www/offsideclub/android
./gradlew assembleRelease
```

Verás:
```
BUILD SUCCESSFUL in 2m 45s
```

---

## Paso 5: Instalar APK

```bash
# Desinstalar versión anterior
adb uninstall com.offsideclub.app

# Instalar nueva versión
adb install android/app/release/app-release.apk
```

---

## Paso 6: Esperar Verificación

Android necesita 5-30 minutos para verificar con Google. Espera:

```bash
sleep 60
```

Luego verifica:

```bash
adb shell pm get-app-links com.offsideclub.app
```

Debería mostrar:

```
com.offsideclub.app:
  ID: app.offsideclub.es
  Status: always          ← ✅
  User set: false
```

---

## Paso 7: Probar Deep Link

```bash
adb shell am start -a android.intent.action.VIEW -d "https://app.offsideclub.es/invite/TEST"
```

Debería abrir OffsideClub automáticamente **sin mostrar diálogo**.

---

## Verificación Final

```bash
curl -s "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls" | jq .
```

Si ves:
```json
{
  "linked": true
}
```

✅ **¡TODO FUNCIONA!**

---

## Checklist

- [ ] Extraído SHA256 del keystore
- [ ] Actualizado assetlinks.json
- [ ] Configurado build.gradle
- [ ] Compilada APK release
- [ ] Instalada en dispositivo
- [ ] Esperado 5-30 minutos
- [ ] Verificado: `Status: always`
- [ ] Probado deep link
- [ ] Verificado con Google API

---

Status: Pendiente SHA256

