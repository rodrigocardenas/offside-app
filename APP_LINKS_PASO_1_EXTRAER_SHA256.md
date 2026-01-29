# 🔑 App Links Automático - Paso 1: Extraer SHA256

## Tu Keystore Producción

✅ Ya tienes el keystore correcto en:
```
C:\Users\rodri\offside.jks
```

Este es el keystore que **usaste para subir la app a Play Store**. Este es el correcto.

---

## Paso 1: Extraer SHA256

### ⭐ Opción A: Script automático (RECOMENDADO)

He creado un script PowerShell que hace todo por ti:

1. **Descarga este archivo:**
   ```
   C:\Users\rodri\extract-sha256.ps1
   ```

2. **Abre PowerShell:**
   - Búscalo como "PowerShell" en Windows
   - O presiona `Windows + X` y elige "Windows PowerShell"

3. **Navega a la carpeta del script:**
   ```powershell
   cd C:\Users\rodri\
   ```

4. **Ejecuta el script:**
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force
   .\extract-sha256.ps1
   ```

5. **Ingresa tu contraseña de Play Store cuando lo pida**

6. **El SHA256 se copiará automáticamente** ✅

---

### Opción B: Comando manual (si el script no funciona)

1. Abre **PowerShell** (búscalo en Windows)
2. Copia y pega este comando exactamente:

```powershell
& "C:\Program Files\Java\jdk-17.0.1\bin\keytool.exe" -list -v -keystore "C:\Users\rodri\offside.jks" -alias offside -storepass TU_CONTRASEÑA
```

3. **Reemplaza `TU_CONTRASEÑA` con tu contraseña de Play Store**

4. Presiona Enter

### Opción C: Desde CMD (Símbolo del Sistema)

1. Abre **Símbolo del Sistema** (cmd.exe)
2. Ejecuta:

```cmd
"C:\Program Files\Java\jdk-17.0.1\bin\keytool.exe" -list -v -keystore "C:\Users\rodri\offside.jks" -alias offside -storepass TU_CONTRASEÑA
```

3. **Reemplaza `TU_CONTRASEÑA` con tu contraseña real**

---

## Paso 2: Copiar el SHA256

En la salida, busca esta línea:

```
SHA-256 fingerprint: AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12
```

**Copia exactamente eso: `AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12`**

(Tu SHA256 será diferente, este es solo un ejemplo)

---

## Paso 3: Actualizar assetlinks.json

1. Abre: `public/.well-known/assetlinks.json`

2. Reemplaza el SHA256 anterior con el tuyo:

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

3. Guarda el archivo

---

## Paso 4: Configurar build.gradle

1. Abre: `android/app/build.gradle`

2. Busca esta sección (o créala si no existe):

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
    release {
        signingConfig signingConfigs.release
        minifyEnabled false
    }
    debug {
        signingConfig signingConfigs.debug
    }
}
```

3. **⚠️ IMPORTANTE:** Reemplaza `TU_CONTRASEÑA_DE_PLAY_STORE` con tu contraseña real (la que usaste en Play Store)

4. Guarda el archivo

---

## Paso 5: Compilar Release APK

Desde la terminal (bash), ejecuta:

```bash
cd /c/laragon/www/offsideclub/android
./gradlew assembleRelease
```

Esto tardará 2-5 minutos. Al final, verás:

```
BUILD SUCCESSFUL in 2m 45s
```

La APK compilada estará en:
```
android/app/release/app-release.apk
```

---

## Paso 6: Instalar en Tu Dispositivo

```bash
# Desinstalar la versión anterior
adb uninstall com.offsideclub.app

# Instalar la nueva versión con keystore de producción
adb install android/app/release/app-release.apk

# Esperar a que Android verifique (5-30 minutos)
sleep 60

# Ver el estado
adb shell pm get-app-links com.offsideclub.app
```

### Resultado Esperado

Si funciona, verás:

```
com.offsideclub.app:
  ID: app.offsideclub.es
  Status: always          ← ✅ ESTO SIGNIFICA QUE FUNCIONA
  User set: false
```

---

## Paso 7: Verificar con Google

Espera 2 minutos y ejecuta:

```bash
curl -s "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls" | jq .
```

Debería retornar:

```json
{
  "linked": true
}
```

Si ves `"linked": true` ✅ **¡FUNCIONA!**

---

## Resumen Rápido

1. ✅ Ya tienes el keystore en `C:\Users\rodri\offside.jks`
2. → Extrae SHA256 desde PowerShell
3. → Actualiza assetlinks.json
4. → Configura build.gradle
5. → Compila: `./gradlew assembleRelease`
6. → Instala: `adb install android/app/release/app-release.apk`
7. → Verifica: `adb shell pm get-app-links com.offsideclub.app`
8. → Debe mostrar `Status: always` ✅

---

**Próximo paso:** Ejecuta keytool en PowerShell y copia el SHA256. Luego avísame el resultado.

Fecha: 29 de Enero, 2026
Status: ✅ USANDO KEYSTORE DE PLAY STORE
