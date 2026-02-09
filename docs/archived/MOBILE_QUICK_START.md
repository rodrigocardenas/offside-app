# ⚡ QUICK START - Compilar y Probar Mobile

## 🔴 ANTES DE EMPEZAR

```
❌ Paso 1: ¿Tienes google-services.json?
├─ SI  → Continúa ↓
└─ NO  → Ve a https://console.firebase.google.com
           Proyecto: offside-dd226
           Descarga: google-services.json
           Copia en: android/app/google-services.json

✅ Paso 2: Firebase Gradle configurado?
├─ SI (root/app build.gradle actualizados) → Continúa ↓
└─ NO → Ver: FIREBASE_GRADLE_CONFIG.md
        Actualiza build.gradle files
        Commit: e16c61e ya lo hizo por ti ✓
```

---

## ⚙️ COMPILAR

```bash
# Terminal 1
cd c:\laragon\www\offsideclub
npm install
npx cap sync android

# Limpiar cache anterior (importante!)
cd android
./gradlew clean

# Compilar APK
./gradlew assembleDebug

# Espera 5-10 minutos...
# ✅ BUILD SUCCESSFUL = listo
```

---

## 📱 INSTALAR

```bash
# Terminal 2 (mientras compila o después)
cd c:\laragon\www\offsideclub

# Conecta emulador o dispositivo
adb devices
# Deberías ver: emulator-5554 device  O  <device-id> device

# Instala APK
adb install -r android/app/build/outputs/apk/debug/app-debug.apk

# ✅ Success = listo
```

---

## 🧪 PROBAR

Abre app en tu móvil: "Offside Club"

### Bug 1️⃣ - Android Back Button ✅

```
Navega: Home → Grupo → Preguntas
Presiona ATRÁS 2 veces
❌ Falla: vuelve siempre a Home
✅ Correcto: vuelve a Grupo, después a Home
En Home + ATRÁS: muestra diálogo "¿Salir?"
```

**Logs:**
```bash
adb logcat | grep AndroidBackButton
```

---

### Bug 2️⃣ - Deep Links ✅

```
En Terminal 3:
adb shell am start -a android.intent.action.VIEW -d "offsideclub://group/1"

❌ Falla: abre navegador
✅ Correcto: abre app y navega a grupo 1
```

**Logs:**
```bash
adb logcat | grep DeepLinks
```

---

### Bug 3️⃣ - Firebase Notifications ✅

```
En web (https://app.offsideclub.es):
1. Crea una pregunta predictiva
2. En móvil: debería escucharse sonido/vibración
3. Debería aparecer banner con notificación
4. Click en notificación → abre pregunta
```

**Logs:**
```bash
adb logcat | grep -i firebase
```

---

### Bug 4️⃣ - Cache Issues ✅

```
En web: cambia el nombre de un grupo
En móvil: debería actualizar automáticamente

Si no:
  → Swipe desde arriba (pull-to-refresh)
  → Espera a que se cargue

✅ Correcto: datos actualizados
```

**Logs:**
```bash
adb logcat | grep -i cache
```

---

## 📊 Resultado

| Bug | Esperado | Tu Resultado |
|-----|----------|--------------|
| 1 - Back Button | ✅ | ☐ OK ☐ FALLA |
| 2 - Deep Links | ✅ | ☐ OK ☐ FALLA |
| 3 - Notificaciones | ✅ | ☐ OK ☐ FALLA |
| 4 - Cache | ✅ | ☐ OK ☐ FALLA |

---

## 🚨 Si algo falla

```bash
# Ver logs detallados
adb logcat

# Limpiar todo y recompilar
cd android
./gradlew clean
cd ..
npx cap sync android
cd android
./gradlew assembleDebug
adb install -r ../android/app/build/outputs/apk/debug/app-debug.apk
```

---

## 📚 Documentación Completa

Ver: [MOBILE_TESTING_GUIDE.md](MOBILE_TESTING_GUIDE.md)

---

**Estado:** Listo para compilar  
**Fecha:** 4 feb 2026  
**Bugs a probar:** 4/4
