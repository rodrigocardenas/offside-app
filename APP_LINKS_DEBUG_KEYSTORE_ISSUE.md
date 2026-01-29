# ❌ PROBLEMA: App Links No Funciona - Causa Identificada

## El Problema Real

El `autoVerify="true"` **NO funciona automáticamente** porque:

### ❌ Estás compilando con DEBUG KEYSTORE
- SHA256 actual en assetlinks.json: `75:2E:20:AE:6E:13:E4:16:C4:DD:CC:A8:51:0B:92:DD:12:5F:AE:44:0E:93:A6:21:55:18:73:0D:23:01:D5:84`
- Certificado usado: `~/.android/debug.keystore` (LOCAL)

### ✅ Necesitas compilar con RELEASE KEYSTORE
- Debe ser un certificado de **producción**
- Play Store usará este certificado automáticamente
- El SHA256 de este certificado debe estar en `assetlinks.json`

---

## Por Qué Funciona el Diálogo Manual

El diálogo manual funciona porque:
1. No depende de `autoVerify="true"`
2. No necesita que Google verifique nada
3. El usuario manualmente configura la app
4. Por eso funciona incluso con debug keystore

**Pero App Links automático requiere un certificado de producción.**

---

## La Solución: Generar Release Keystore

### Paso 1: Generar el Keystore de Producción

```bash
keytool -genkey -v -keystore ~/offsideclub-release.keystore \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias offsideclub_app
```

**Cuando pregunte:**
```
Ingresa contraseña (mín 6 caracteres): [tu-contraseña]
Confirma contraseña: [tu-contraseña]

¿Cuál es tu nombre y apellido? [Rodrigo Cardenas]
¿Cuál es el nombre de tu unidad organizacional? [Development]
¿Cuál es el nombre de tu organización? [OffsideClub]
¿Cuál es el nombre de tu ciudad o localidad? [Barcelona]
¿Cuál es el nombre de tu estado o provincia? [Catalonia]
¿Cuál es el código de dos letras de tu país? [ES]

¿Es correcto? [sí]
```

### Paso 2: Obtener el SHA256 del Nuevo Certificado

```bash
keytool -list -v -keystore ~/offsideclub-release.keystore \
  -alias offsideclub_app
```

Busca la línea que dice:
```
SHA256: XX:XX:XX:XX:...
```

Copia ese SHA256 completo (40 caracteres con dos puntos).

### Paso 3: Actualizar assetlinks.json

Abre `public/.well-known/assetlinks.json` y reemplaza:

```json
{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.offsideclub.app",
    "sha256_cert_fingerprints": [
      "NUEVO_SHA256_AQUI"  ← Reemplaza con el nuevo
    ]
  }
}
```

### Paso 4: Compilar Release APK con el Nuevo Keystore

En `android/app/build.gradle`, asegúrate de que esté configurado:

```gradle
signingConfigs {
    release {
        storeFile file("$System.env.HOME/offsideclub-release.keystore")
        storePassword System.getenv("KEYSTORE_PASSWORD") ?: "tu-contraseña"
        keyAlias "offsideclub_app"
        keyPassword System.getenv("KEY_PASSWORD") ?: "tu-contraseña"
    }
}

buildTypes {
    release {
        signingConfig signingConfigs.release
    }
}
```

### Paso 5: Compilar y Probar

```bash
# Compilar release APK
cd android
./gradlew assembleRelease

# Instalar
adb install -r android/app/release/app-release.apk

# Esperar a que Android verifique (5-30 minutos)
sleep 60

# Verificar
adb shell pm get-app-links com.offsideclub.app

# Debería mostrar: Status: always ✅
```

---

## Alternativa: Usando Environment Variables (RECOMENDADO)

### Paso 1: Crear archivo de configuración

Crea `android/keystore.properties`:

```properties
storeFile=~/offsideclub-release.keystore
storePassword=tu-contraseña
keyAlias=offsideclub_app
keyPassword=tu-contraseña
```

### Paso 2: Actualizar build.gradle

En `android/app/build.gradle`:

```gradle
def keystoreFile = file('keystore.properties')
def keystoreProperties = new Properties()

if (keystoreFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystoreFile))
}

android {
    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile file(keystoreProperties['storeFile'])
            storePassword keystoreProperties['storePassword']
        }
    }

    buildTypes {
        release {
            signingConfig signingConfigs.release
        }
    }
}
```

### Paso 3: No commitear el keystore

Agrega a `.gitignore`:

```
android/keystore.properties
keystore files/
*.keystore
*.jks
```

---

## Verificación: Google API Check

Una vez que tengas el nuevo SHA256 en `assetlinks.json`:

```bash
# Esperar 1-2 minutos para que Google cachee
sleep 120

# Verificar con Google
curl -s "https://digitalassetlinks.googleapis.com/v1/assetlinks:check?namespace=android_app&package_name=com.offsideclub.app&relation=delegate_permission/common.handle_all_urls" | jq .

# Debería retornar: "linked": true ✅
```

---

## Por Qué Esto Es Importante

| Escenario | Debug Keystore | Release Keystore |
|-----------|---|---|
| **Testing local** | ✅ Funciona | ✅ Funciona |
| **APK manual** | ✅ Funciona | ✅ Funciona |
| **Play Store** | ❌ No funciona | ✅ Funciona |
| **App Links automático** | ❌ No | ✅ Sí |
| **Diálogo manual** | ✅ Sí | ✅ Sí |

---

## Timeline

### Situación Actual
```
1. Compilaste con debug keystore
2. assetlinks.json tiene SHA256 del debug keystore
3. Google verifica: "Espera, ¿es la misma app?"
4. Resultado: linked: false ❌
5. App Links automático NO funciona
6. Pero diálogo manual SÍ funciona
```

### Solución
```
1. Generar release keystore
2. Obtener SHA256 del release keystore
3. Actualizar assetlinks.json con el nuevo SHA256
4. Compilar APK con release keystore
5. Instalar en dispositivo
6. Google verifica: "Sí, es la misma app"
7. Resultado: linked: true ✅
8. App Links automático FUNCIONA
```

---

## Próximos Pasos

1. **Generar release keystore:**
   ```bash
   keytool -genkey -v -keystore ~/offsideclub-release.keystore \
     -keyalg RSA -keysize 2048 -validity 10000 \
     -alias offsideclub_app
   ```

2. **Obtener SHA256:**
   ```bash
   keytool -list -v -keystore ~/offsideclub-release.keystore \
     -alias offsideclub_app
   ```

3. **Actualizar assetlinks.json** con el nuevo SHA256

4. **Compilar release:**
   ```bash
   cd android && ./gradlew assembleRelease
   ```

5. **Instalar y probar:**
   ```bash
   adb install -r android/app/release/app-release.apk
   ```

6. **Verificar:**
   ```bash
   adb shell pm get-app-links com.offsideclub.app
   ```

---

## ⚠️ Importante: Seguridad del Keystore

- **NO subas el keystore a git** (agrega a `.gitignore`)
- **NO compartas la contraseña** en mensajes
- **Guarda el keystore en lugar seguro** (preferiblemente en máquina de CI/CD)
- **La contraseña NO debe estar en código fuente**

---

## FAQ

### ¿Por qué no funcionó al principio?
Porque `autoVerify="true"` necesita que el certificado sea verificado por Google, y Google solo confía en certificados de producción/release.

### ¿Qué es un Debug Keystore?
Es el certificado local que Android crea automáticamente para testing. No es válido para producción.

### ¿Qué es un Release Keystore?
Es el certificado que generas para producción. Es único y debe guardarse de forma segura.

### ¿Puedo usar el mismo keystore para siempre?
Sí, genera UNO y úsalo para todos los builds de producción. Google lo reconocerá como la misma app.

### ¿Qué pasa si pierdo el keystore?
No puedas actualizar la app en Play Store. Es por eso que debe guardarse de forma segura.

---

**Ahora entiendes por qué App Links requiere un certificado de producción. Genera el keystore y actualiza assetlinks.json con el nuevo SHA256.**

**Versión:** v1.078+  
**Actualización:** 29 de Enero, 2026  
**Status:** 🔴 BLOQUEANTE - Necesita release keystore
