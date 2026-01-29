# ✅ SHA256 Extraído - Próximo Paso: Compilar APK

## Resumen de Cambios Realizados

✅ **SHA256 obtenido:**
```
67:65:54:7D:C9:41:E6:02:5E:62:32:AB:CA:E8:67:12:41:A1:2E:D0:23:B1:47:85:1E:2F:A1:5B:B5:79:67:BD
```

✅ **Archivos actualizados:**
1. `public/.well-known/assetlinks.json` - SHA256 actualizado ✅
2. `android/app/build.gradle` - signingConfigs y keyAlias "key0" configurados ✅

---

## ⚠️ IMPORTANTE: Actualizar Contraseña en build.gradle

Abre: `android/app/build.gradle`

Busca esta línea (alrededor de la línea 15-20):

```gradle
storePassword "TU_CONTRASEÑA_DE_PLAY_STORE"
```

Reemplaza `TU_CONTRASEÑA_DE_PLAY_STORE` con tu **contraseña real de Play Store** (la que acabas de usar para extraer el SHA256).

**Hay 2 lugares donde aparece. Actualiza ambos:**

```gradle
signingConfigs {
    debug {
        storeFile file('debug.keystore')
    }
    release {
        storeFile file("C:/Users/rodri/offside.jks")
        storePassword "TU_CONTRASEÑA_AQUI"          ← Reemplaza aquí
        keyAlias "key0"
        keyPassword "TU_CONTRASEÑA_AQUI"            ← Y aquí también
    }
}
```

---

## 🚀 Paso 1: Compilar Release APK

Una vez que hayas actualizado la contraseña, ejecuta:

```bash
cd /c/laragon/www/offsideclub/android
./gradlew assembleRelease
```

Esto tardará 2-5 minutos. Al final verás:

```
BUILD SUCCESSFUL in 2m 45s
```

La APK compilada estará en:
```
android/app/release/app-release.apk
```

---

## 🔧 Paso 2: Instalar en Dispositivo

```bash
# Desinstalar versión anterior
adb uninstall com.offsideclub.app

# Instalar nueva versión con keystore de producción
adb install android/app/release/app-release.apk
```

---

## ⏳ Paso 3: Esperar Verificación

Android necesita 5-30 minutos para verificar automáticamente con Google. Espera:

```bash
sleep 300
```

(300 segundos = 5 minutos, pero espera más si es necesario)

---

## ✅ Paso 4: Verificar Estado

```bash
adb shell pm get-app-links com.offsideclub.app
```

Debería mostrar:

```
com.offsideclub.app:
  ID: app.offsideclub.es
  Status: always          ← ✅ ESTO SIGNIFICA QUE FUNCIONA
  User set: false
```

Si ves `Status: always` ✅ **¡App Links funciona automáticamente!**

---

## 🧪 Paso 5: Probar Deep Link

```bash
adb shell am start -a android.intent.action.VIEW -d "https://app.offsideclub.es/invite/TEST"
```

Debería:
- ✅ Abrir OffsideClub automáticamente
- ✅ **SIN mostrar diálogo de selección**
- ✅ SIN preguntar "¿Abrir con..."

---

## 🔍 Paso 6: Verificación Final con Google

Espera 2 minutos y ejecuta:

```bash
curl -s "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls" | jq .
```

Resultado esperado:

```json
{
  "linked": true
}
```

✅ **¡FUNCIONA!**

---

## 📋 Resumen Rápido

1. **Actualiza contraseña** en `android/app/build.gradle`
2. **Compila:** `./gradlew assembleRelease`
3. **Instala:** `adb install android/app/release/app-release.apk`
4. **Espera:** 5-30 minutos para verificación
5. **Verifica:** `adb shell pm get-app-links com.offsideclub.app`
6. **Debe mostrar:** `Status: always`
7. **Prueba:** `adb shell am start -a android.intent.action.VIEW -d "https://app.offsideclub.es/invite/TEST"`
8. **Verifica con Google:** `curl ...digitalassetlinks.googleapis.com...`

---

## ¿Listo?

1. Actualiza la contraseña en `build.gradle`
2. Ejecuta: `./gradlew assembleRelease`
3. Avísame cuando la APK esté compilada
4. Luego instalas y verificas

**¡Vamos a terminar esto!** 🚀

Fecha: 29 de Enero, 2026
Status: ✅ LISTO PARA COMPILAR
