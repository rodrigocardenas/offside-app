# 🔑 PASO A PASO: Usar Keystore de Producción Existente

## ¡BUENA NOTICIA!

Ya tienes el keystore de producción que usaste para Play Store:
```
C:\Users\rodri\offside.jks
```

**No necesitas generar uno nuevo.** Solo necesitas:
1. Obtener el SHA256 de este keystore
2. Actualizar assetlinks.json
3. Configurar build.gradle
4. Recompilar la APK

---

## Paso 1: Obtener el SHA256

**Opción A: Script automático (Windows)**

Ejecuta este archivo en Windows:
```
get-sha256.bat
```

Se abrirá una ventana CMD que te mostrará toda la información del keystore. Busca la línea:
```
SHA256: XX:XX:XX:XX:XX:XX:XX:XX:...
```

**Opción B: Comando manual**

Abre CMD en Windows y ejecuta:
```cmd
keytool -list -v -keystore "C:\Users\rodri\offside.jks" -alias offside
```

Cuando pida contraseña, ingresa la que usaste para Play Store.

### Busca esta línea en el output:

```
SHA256: XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX:XX
```

**Copia ese SHA256 completo** (40 caracteres separados por dos puntos).

Ejemplo:
```
SHA256: AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12
```

---

## Paso 3: Actualizar assetlinks.json

Abre este archivo:
```
public/.well-known/assetlinks.json
```

Reemplaza el SHA256 anterior con el que acabas de copiar:

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

## Paso 4: Configurar build.gradle

Abre:
```
android/app/build.gradle
```

Busca o crea la sección `signingConfigs`. Reemplaza con esto (usa tu contraseña de Play Store):

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

⚠️ **Reemplaza `TU_CONTRASEÑA_DE_PLAY_STORE` con tu contraseña real**

---

## Paso 5: Compilar Release APK

```bash
cd /c/laragon/www/offsideclub/android
./gradlew assembleRelease
```

La APK se crea en:
```
android/app/release/app-release.apk
```

---

## Paso 6: Instalar y Probar

```bash
# Desinstalar la vieja
adb uninstall com.offsideclub.app

# Instalar la nueva
adb install android/app/release/app-release.apk

# Esperar a que Android verifique (5-30 minutos)
sleep 60

# Ver estado
adb shell pm get-app-links com.offsideclub.app
```

### Resultado Esperado:

```
com.offsideclub.app:
  ID: app.offsideclub.es
  Status: always          ← ✅ ESTO ES LO QUE QUEREMOS
  User set: false
```

Si ves `Status: always` ✅ **¡Funciona!**

---

## Paso 7: Probar que Funciona

```bash
# Probar un deep link
adb shell am start -a android.intent.action.VIEW -d "https://app.offsideclub.es/invite/TEST"

# Debería abrir OffsideClub automáticamente (sin pregunta)
```

---

## Verificar con Google

```bash
# Esperar 2 minutos para que Google cachee
sleep 120

# Verificar
curl -s "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls" | jq .
```

Debería retornar:
```json
{
  "linked": true
}
```

Si ves `"linked": true` ✅ **¡Google lo verificó!**

---

## Checklist

- [ ] Obtener SHA256 del keystore (Paso 1)
- [ ] Actualizar assetlinks.json (Paso 2)
- [ ] Configurar build.gradle (Paso 3)
- [ ] Compilar release APK (Paso 4)
- [ ] Instalar en dispositivo (Paso 5)
- [ ] Probar deep link (Paso 6)
- [ ] Verificar con Google (Verificación)
- [ ] Ver "Status: always" ✅
- [ ] Ver "linked: true" ✅

---

## Si Algo Sale Mal

### Error: "keytool not found"
En Windows, keytool debería estar disponible. Si no, asegúrate de tener Java en PATH.

### Error: "Invalid keystore format"
Verifica que la ruta sea correcta: `C:\Users\rodri\offside.jks`

### Error: "Invalid keystore alias"
El alias podría ser diferente. Ejecuta sin -alias para ver la lista:
```cmd
keytool -list -keystore "C:\Users\rodri\offside.jks"
```

### La APK no compila
Verifica que build.gradle tenga la sintaxis correcta (sin typos).

### Status sigue siendo "ask"
Puede que Google tarde más. Espera 30 minutos y verifica de nuevo:
```bash
adb shell pm verify-app-links com.offsideclub.app
```

---

## Resumen Ultra Rápido

1. **Obtener SHA256:** 
   ```cmd
   keytool -list -v -keystore "C:\Users\rodri\offside.jks" -alias offside
   ```

2. **Actualizar assetlinks.json** con el SHA256

3. **Editar build.gradle** con la configuración de signing (usa tu contraseña)

4. **Compilar:** `./gradlew assembleRelease`

5. **Instalar:** `adb install -r android/app/release/app-release.apk`

6. **Verificar:** `adb shell pm get-app-links com.offsideclub.app`

7. **Status should be: `always` ✅**

---

**¡Eso es todo! Sigue estos pasos y App Links automático funcionará.**

**Fecha:** 29 de Enero, 2026  
**Status:** 🟢 USANDO KEYSTORE EXISTENTE DE PLAY STORE
