# 📱 Guía: Compilación y Testing Mobile - Offside Club

**Fecha:** 4 feb 2026  
**Objetivo:** Compilar APK y probar Bugs 1, 2, 3, 4 en Android

---

## 🔴 REQUISITO CRÍTICO: google-services.json

Para compilar, **NECESITAS** el archivo `google-services.json` descargado de Firebase Console.

### ⏬ Descargar google-services.json

1. **Ve a Firebase Console:**
   - URL: https://console.firebase.google.com
   - Proyecto: **offside-dd226**

2. **Sección Settings:**
   - Izquierda: ⚙️ Configuración del Proyecto
   - Tab: "Integraciones"
   - O: "Apps" si existe

3. **Selecciona Android App:**
   - Nombre: `com.offsideclub.app`
   - Version: 1.079

4. **Descarga google-services.json:**
   - Botón azul: "Descargar google-services.json"
   - Guarda como: `google-services.json`

5. **Coloca el archivo:**
   ```
   android/app/google-services.json
   ```
   ✅ Ruta correcta: `c:\laragon\www\offsideclub\android\app\google-services.json`

---

## 🚀 Pasos de Compilación

### 1️⃣ Preparar entorno

```bash
cd c:\laragon\www\offsideclub

# Instalar dependencias npm (si no las tienes)
npm install

# Sincronizar Capacitor con Android
npx cap sync android
```

**Salida esperada:**
```
Capacitor Android synchronized successfully
```

### 2️⃣ Compilar APK Debug

```bash
cd android
./gradlew assembleDebug
```

**Tiempo esperado:** 5-10 minutos  
**Ubicación del APK:** `android/app/build/outputs/apk/debug/app-debug.apk`

**Salida esperada:**
```
BUILD SUCCESSFUL in X seconds
```

### 3️⃣ Instalar en dispositivo/emulador

#### Opción A: Con emulador Android Studio (RECOMENDADO)

```bash
# Primero: abre Android Studio
# Emulator → Create Virtual Device → Pixel 5 (Android 14)

# Verifica que el emulador está corriendo
adb devices
# Debería mostrar: emulator-5554 device

# Instala el APK
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

#### Opción B: Con dispositivo físico

```bash
# Conecta tu Android por USB
# Activa "Depuración USB" en Configuración → Opciones de desarrollador

# Verifica conexión
adb devices
# Debería mostrar: <device-id> device

# Instala
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

**Salida esperada:**
```
Success
```

---

## 🧪 Testing en Mobile

Después de instalar, abre "Offside Club" en tu dispositivo.

### Bug 1️⃣: Android Back Button

**Que debería funcionar:**
- ✅ Navega entre pantallas
- ✅ Back → pantalla anterior (NO inicio)
- ✅ En home → muestra diálogo "¿Salir?"

**Cómo probar:**

```
1. Abre app → Home
2. Navega a: Grupos → Selecciona grupo → Preguntas
3. Presiona ATRÁS (botón físico Android)
4. Debería volver a "Grupos" (no a Home)
5. Presiona ATRÁS de nuevo
6. Debería volver a "Home"
7. Presiona ATRÁS en Home
8. Debería aparecer diálogo: "¿Deseas salir de Offside Club?"
```

**Logs esperados:**
```
adb logcat | grep -E "AndroidBackButton|history"

Output:
[AndroidBackButton] Capacitor detectado
[AndroidBackButton] Manejador inicializado correctamente
[AndroidBackButton] Back button presionado. History length: 3
[AndroidBackButton] Navegando atrás
```

**❌ Si no funciona:**
- Abre DevTools (F12 en web):
  - Console → filtra por `[AndroidBackButton]`
  - Si ves error → APK vieja o plugin App no cargó
  - Recompila: `npx cap sync android && ./gradlew clean assembleDebug`

---

### Bug 2️⃣: Deep Links

**Que debería funcionar:**
- ✅ Link `offsideclub://group/1` abre app (no web)
- ✅ Navega directamente al grupo
- ✅ Link HTTPS `app.offsideclub.es` también funciona

**Cómo probar:**

#### Método 1: Via adb

```bash
# Abre un deep link (grupo 1)
adb shell am start -a android.intent.action.VIEW -d "offsideclub://group/1"

# Debería:
# 1. Abrir la app (no el navegador)
# 2. Navegar a "Grupo 1"
# 3. No pedir permisos (ya configurado)
```

#### Método 2: Enviar por mensaje

```
1. En tu computadora, abre navegador
2. Envía un link de invitación desde web
3. Cópialo: app.offsideclub.es/invite/abc123
4. Envía por WhatsApp/Telegram a tu móvil
5. Haz click en el link
6. Debería abrir la app (no el navegador)
```

**Logs esperados:**
```
adb logcat | grep -E "DeepLinks|appUrlOpen"

Output:
[DeepLinks] Handler inicializado correctamente
[DeepLinks] Deep link recibido: offsideclub://group/1
[DeepLinks] Host: group, Path: 1
[DeepLinks] Navegando a: /groups/1
```

**❌ Si no funciona:**
- Abre DevTools: Console → filtra `[DeepLinks]`
- Si no ves logs → APK vieja (Intent-filter no actualizado)
- Recompila: `./gradlew clean assembleDebug`

---

### Bug 3️⃣: Firebase Notifications

**Que debería funcionar:**
- ✅ Notificaciones llegan a mobile (foreground y background)
- ✅ Sonido, badge, vibración
- ✅ Clics en notificación navegan a preguntas

**Cómo probar:**

```
1. Abre web: https://app.offsideclub.es
2. Crea un grupo (si no tienes)
3. Crea una pregunta predictiva
4. En el móvil:
   - Debería sonar/vibrar
   - Banner notificación aparece
   - Haz click → abre la pregunta
```

**Alternativa: Enviar notificación manual (desde web/admin)**

```
Si existe endpoint admin:
  POST /api/admin/test-notification
  Body: { user_id: 1 }
  
Entonces mobile debería recibir notificación
```

**Logs esperados:**
```
adb logcat | grep -E "FirebaseMessaging|firebase|messaging"

Output:
FirebaseMessaging: Token registered: abc...xyz
FirebaseMessaging: Message received: {"title":"Nueva pregunta","body":"..."}
```

**❌ Si no funciona:**
- Verifica que push token se registró:
  - Web DevTools → Network → POST /api/push/token
  - Response debe mostrar `success: true`
- Si no hay token:
  - Abre DevTools → Console → busca errores Firebase
  - Puede que google-services.json esté mal
- Si token existe pero no llega notificación:
  - Backend puede estar usando web-only Firebase API
  - Necesita usar `app/Traits/HandlesPushNotifications.php`

---

### Bug 4️⃣: Cache Issues

**Que debería funcionar:**
- ✅ Cambios en web se ven automáticamente en mobile
- ✅ No requiere `artisan cache:clear` manual
- ✅ Pull-to-refresh actualiza datos

**Cómo probar:**

```
Caso 1: Actualizar datos existentes
1. En web: edita el nombre de un grupo
2. En mobile (sin hacer nada):
   - Debería actualizar automáticamente en pocos segundos
   - Si no, usa pull-to-refresh (swipe desde arriba)

Caso 2: Crear datos nuevos  
1. En web: crea una nueva pregunta predictiva
2. En mobile:
   - Debería aparecer automáticamente
   - Si no, usa pull-to-refresh

Caso 3: Usar Pull-to-Refresh
1. En mobile: swipe desde la parte superior de la pantalla
2. Espera a que aparezca indicador
3. Mantén presionado hasta 80px
4. Suelta
5. Debería recargar datos y mostrar checkmark verde
```

**Logs esperados:**
```
adb logcat | grep -E "cache|pull-to-refresh"

Output:
[PullToRefresh] Threshold reached
[PullToRefresh] Clearing user cache
[PullToRefresh] Request to /api/cache/clear-user success
```

**❌ Si no funciona:**
- Verifica pull-to-refresh:
  - DevTools Console → `new OffsidePullToRefresh()` inicializa?
  - Si no: recompila
- Verifica que endpoint `/api/cache/clear-user` existe:
  - `curl -X POST http://localhost/api/cache/clear-user -H "Authorization: Bearer TOKEN"`
  - Debe retornar `{"success": true}`

---

## 🔧 Troubleshooting Compilación

### ❌ Error: "google-services.json not found"

**Solución:**
```bash
# 1. Descarga desde Firebase Console (ver arriba)
# 2. Verifica ruta exacta:
ls -la android/app/google-services.json

# 3. Si no existe, cópialo manualmente:
cp ~/Downloads/google-services.json android/app/

# 4. Recompila:
./gradlew clean assembleDebug
```

### ❌ Error: "Gradle sync failed"

**Solución:**
```bash
cd android
./gradlew clean
./gradlew sync
./gradlew assembleDebug
```

### ❌ Error: "Plugin App not found" (en runtime)

**Solución:**
```bash
# Reconstruir todo
npx cap sync android
./gradlew clean
./gradlew assembleDebug
```

### ❌ APK instalado pero app no arranca

**Pasos:**
```bash
# 1. Ver logs
adb logcat | head -50

# 2. Si ves errores JavaScript:
#    - Abre DevTools en web (F12)
#    - Console debería mostrar los mismos errores
#    - Corrige en código

# 3. Recompila y reinstala
./gradlew clean assembleDebug
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

---

## 📊 Resultados Esperados

Después del testing, deberías ver:

| Bug | Status | Indicador |
|-----|--------|-----------|
| 1 | ✅ OK | Back → anterior, Home→diálogo |
| 2 | ✅ OK | Deep link abre app, no web |
| 3 | ✅ OK | Notificación llega, sonido/vibración |
| 4 | ✅ OK | Datos sync automático, pull-to-refresh funciona |

---

## 📝 Próximos Pasos

1. **Si TODO funciona:**
   - ✅ Preparar para producción
   - ✅ Generar signed APK
   - ✅ Deploy a Google Play

2. **Si hay bugs:**
   - Reportar específicamente cuál Bug#
   - Incluir logs de `adb logcat`
   - Incluir paso exacto que falló

---

## 🎯 Resumen Rápido

```bash
# Terminal 1: Sincronizar y compilar
cd c:\laragon\www\offsideclub
npm install
npx cap sync android
cd android
./gradlew assembleDebug

# Terminal 2: Instalar (después de compilar)
cd c:\laragon\www\offsideclub
adb install -r android/app/build/outputs/apk/debug/app-debug.apk

# Terminal 3: Ver logs en tiempo real
adb logcat | grep -E "DeepLinks|AndroidBackButton|Firebase|offsideclub"
```

---

**Nota:** Este documento asume que tienes:
- ✅ Android SDK instalado
- ✅ Emulador o dispositivo con Android 9+
- ✅ adb disponible en PATH
- ✅ gradle configurado

Si tienes dudas, revisa los logs exactos con `adb logcat`.

