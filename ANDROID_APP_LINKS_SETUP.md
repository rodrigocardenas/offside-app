# ✅ Android App Links - Links Abren la App en Lugar del Navegador

## El Problema
```
Link: https://app.offsideclub.es/invite/gYjxGZ
Resultado: Se abre en Chrome navegador ❌
Esperado: Abre en app OffsideClub ✅
```

## La Solución: Android App Links

Android App Links permite que URLs HTTPS abran automáticamente una app instalada en lugar del navegador.

---

## ¿Cómo Funciona?

### 1. Intent Filter en AndroidManifest.xml
```xml
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data
        android:scheme="https"
        android:host="app.offsideclub.es"
        android:path="/invite/*" />
</intent-filter>
```

**Qué hace**:
- `android:autoVerify="true"` → Android verifica automáticamente el dominio
- `android:scheme="https"` → Solo URLs HTTPS
- `android:host="app.offsideclub.es"` → Solo este dominio
- `android:path="/invite/*"` → Solo esta ruta

### 2. Verificación del Dominio: assetlinks.json
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

**Ubicación**: `https://app.offsideclub.es/.well-known/assetlinks.json` ✅ (Ya está)

**Qué contiene**:
- `package_name`: `com.offsideclub.app` (Package de la app)
- `sha256_cert_fingerprints`: SHA256 del certificado que firma la app

---

## Flujo Completo Ahora

```
Usuario A comparte grupo por WhatsApp
    ↓
Envía: https://app.offsideclub.es/invite/gYjxGZ
    ↓
Usuario B recibe en WhatsApp
    ↓
¿Tiene app instalada?
    │
    ├─ SÍ → Android verifica assetlinks.json
    │       ✅ Abre en OffsideClub app
    │       ✅ Muestra pantalla de invitación
    │       ✅ Usuario hace click "Unirme"
    │
    └─ NO → Android no encuentra app
            ✅ Se abre en Chrome navegador
            ✅ Muestra pantalla de invitación web
            ✅ Usuario hace click "Unirme"
```

---

## Cambios Implementados

### 1. AndroidManifest.xml
```xml
<!-- Agregado intent-filter para Android App Links -->
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="https" android:host="app.offsideclub.es" android:path="/invite/*" />
    <data android:scheme="https" android:host="app.offsideclub.es" android:path="/groups/invite/*" />
</intent-filter>
```

### 2. assetlinks.json
```
Ubicación: public/.well-known/assetlinks.json
Contenido: Certificado SHA256 + package name
URL: https://app.offsideclub.es/.well-known/assetlinks.json
```

**Ya existe en el servidor** ✅

### 3. APK Compilado
```
android/app/build/outputs/apk/debug/app-debug.apk
✅ Incluye nueva configuración
```

---

## Cómo Testear

### Test 1: Verificar que assetlinks.json existe
```bash
curl https://app.offsideclub.es/.well-known/assetlinks.json
# Debería mostrar JSON con el SHA256
```

### Test 2: En Dispositivo Android
```
1. Instala APK: adb install -r app-debug.apk
2. Abre WhatsApp en desktop/web
3. Comparte: https://app.offsideclub.es/invite/gYjxGZ
4. En móvil: Click en link
5. ✅ Debería abrir OffsideClub app (no Chrome)
```

### Test 3: Si no abre la app
```
Verificar que:
1. APK está compilada con el nuevo AndroidManifest
2. assetlinks.json está en: /.well-known/assetlinks.json
3. assetlinks.json contiene el SHA256 correcto
4. Teléfono tiene conexión a internet (verifica dominio)
5. App está instalada en el dispositivo

Si sigue sin funcionar:
- Desinstala app: adb uninstall com.offsideclub.app
- Reinstala APK nueva: adb install app-debug.apk
- Espera ~5 segundos (Android verifica)
- Prueba de nuevo
```

---

## Verificación de Configuración

### SHA256 del Certificado Debug
```
75:2E:20:AE:6E:13:E4:16:C4:DD:CC:A8:51:0B:92:DD:12:5F:AE:44:0E:93:A6:21:55:18:73:0D:23:01:D5:84
```

**Ubicación en**: `assetlinks.json` ✅

### Rutas Interceptadas
- ✅ `/invite/{code}`
- ✅ `/groups/invite/{code}`

### Dominio
- ✅ `app.offsideclub.es`

---

## Diferencia: Con vs Sin Android App Links

### ANTES (Sin Android App Links)
```
WhatsApp link: https://app.offsideclub.es/invite/gYjxGZ
Click en link → Se abre Chrome (navegador web)
Resultado: 😞 Mala UX, parece que no tiene app
```

### AHORA (Con Android App Links)
```
WhatsApp link: https://app.offsideclub.es/invite/gYjxGZ
Click en link → Se abre OffsideClub app
Resultado: 😊 Buena UX, experiencia nativa
```

---

## Para Producción (Play Store)

Cuando publiques en Play Store, necesitarás:

1. **Nuevo certificado de producción** (no debug)
2. **Obtener SHA256** del certificado de producción:
   ```
   Play Console → Settings → App signing → SHA-256 certificate fingerprint
   ```
3. **Actualizar assetlinks.json** con el SHA256 de producción
4. **Volver a compilar y subir** APK con el nuevo SHA256

---

## Archivos Modificados

- ✅ `android/app/src/main/AndroidManifest.xml` - Intent filter para app links
- ✅ `public/.well-known/assetlinks.json` - Certificado de verificación

---

## Estado

🟢 **COMPLETADO**

- ✅ AndroidManifest.xml configurado con autoVerify="true"
- ✅ assetlinks.json creado con SHA256 correcto
- ✅ APK compilado
- ✅ Servidor sirve assetlinks.json en ruta correcta
- ✅ Listo para testear

---

## Próximos Pasos

1. **Instala APK en dispositivo**
2. **Espera ~5 segundos** (Android verifica dominio)
3. **Abre WhatsApp en desktop/web**
4. **Comparte link**: `https://app.offsideclub.es/invite/{code}`
5. **En móvil: Click en link**
6. **✅ Debería abrir app** (no navegador)

---

**¡Android App Links ahora configurado!** 🚀
