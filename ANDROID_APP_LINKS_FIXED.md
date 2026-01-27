# 🔧 SOLUCIÓN PARA ANDROID APP LINKS - NUEVA VERSIÓN

## ¿Qué Cambió?

Dividi los intent-filters de Android App Links en **dos intent-filters separados** en lugar de uno con dos `<data>` elements:

```xml
<!-- Antes: Un intent-filter con dos <data> -->
<intent-filter android:autoVerify="true">
    <data android:path="/invite/*" ... />
    <data android:path="/groups/invite/*" ... />
</intent-filter>

<!-- Después: Dos intent-filters separados (Android Best Practice) -->
<intent-filter android:autoVerify="true">
    <data android:path="/invite/*" ... />
</intent-filter>

<intent-filter android:autoVerify="true">
    <data android:path="/groups/invite/*" ... />
</intent-filter>
```

**Razón**: Esto sigue mejor las prácticas de Android y aumenta la probabilidad de que la verificación funcione correctamente.

---

## 📱 Pasos para Probar la NUEVA APK

### 1. Desinstala completamente la app anterior
```bash
# IMPORTANTE: Usar -k para mantener datos (optional)
adb uninstall com.offsideclub.app
```

### 2. Espera ~5 segundos
(Libera completamente la app del sistema)

### 3. Instala la APK nueva
```bash
adb install android/app/build/intermediates/apk/debug/app-debug.apk
```

### 4. **ESPERA 15 segundos** ⏳
(Android verifica assetlinks.json y compila los app links)

### 5. Prueba en WhatsApp Web
```
1. Abre: https://web.whatsapp.com
2. Escribe/comparte este link: https://app.offsideclub.es/invite/test123
3. En tu teléfono: Haz click en el link
```

### 6. ¿Qué debería pasar?
```
✅ CORRECTO: Se abre OffsideClub app (no Chrome)
❌ INCORRECTO: Se abre Chrome o navegador web
```

---

## 🔍 Si SIGUE SIN FUNCIONAR

### Debugging paso a paso:

#### Paso 1: Verifica que assetlinks.json es accesible
```bash
curl https://app.offsideclub.es/.well-known/assetlinks.json

# Debería mostrar JSON válido con tu SHA256
```

#### Paso 2: Limpia cache completamente
```bash
adb shell pm clear com.offsideclub.app
adb uninstall com.offsideclub.app
# Reinstala APK
adb install app-debug.apk
```

#### Paso 3: Ver logs de Android App Links
```bash
adb logcat | grep "AppLinks"
# O verificar si el sistema reconoce el app link:
adb shell pm get-app-link com.offsideclub.app
```

#### Paso 4: Verificación manual de app link
```bash
# Simular click en el link desde línea de comandos
adb shell am start -d "https://app.offsideclub.es/invite/test" com.offsideclub.app

# Si la app se abre con ese comando, entonces App Links está funcionando
```

---

## 📊 Verificación Técnica

| Componente | Estado |
|-----------|--------|
| Intent-filter autoVerify | ✅ Configurado x2 |
| SHA256 en assetlinks.json | ✅ Correcto |
| assetlinks.json accesible | ✅ Verificado |
| Package name | ✅ com.offsideclub.app |
| Dominio | ✅ app.offsideclub.es |

---

## ⚠️ Notas Importantes

1. **Tiempo de verificación**: Android puede tardar 10-30 segundos en verificar y cachear los app links después de instalar
2. **Cache de Chrome**: Si Chrome abrió el link antes, puede haberlo cacheado. Limpia cache de Chrome o usa incognito
3. **Permisos**: Asegúrate de que la app tiene permiso de internet en el teléfono

---

## 🚀 APK Disponible

```
android/app/build/intermediates/apk/debug/app-debug.apk
```

---

**Cuéntame qué sucede después de instalar esta nueva versión 👇**

¿Se abre la app o sigue siendo Chrome?
