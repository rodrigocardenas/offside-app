# 📱 Guía: Subir App Bundle a Play Store

**Última actualización:** 11 Feb 2026  
**Versión de app:** 1.081  
**API Level:** 35 ✅

---

## ✅ Lo que está listo para Play Store

### App Bundle (AAB)
- **Ubicación:** `android/app/build/outputs/bundle/release/app-release.aab`
- **Tamaño:** 4.5 MB
- **API Level:** 35 (requerimiento cumplido ✅)
- **Minificación:** Habilitada con R8 ✅
- **Símbolos de depuración:** Incluidos ✅

### Archivo de desofuscación (Mapping)
- **Ubicación:** `android/app/build/outputs/mapping/release/mapping.txt`
- **Tamaño:** 15 MB
- **Propósito:** Desofuscar crashes y ANR en Play Console

### Símbolos nativos (Debug symbols)
- **Ubicación:** `android-native-symbols-release.tar.gz`
- **Tamaño:** 7.5 KB
- **Propósito:** Symbols para código nativo compilado
- **Contenido:** Archivos `.so` de arquitecturas: arm64-v8a, armeabi-v7a, x86, x86_64

---

## 🚀 Pasos para subir a Play Store

### 1️⃣ Abre Play Console
- URL: https://play.google.com/console
- Selecciona: **Offside Club**

### 2️⃣ Ve a "Pruebas" → "Versión interna" (o "Producción")
- Click en: **Crear nueva versión**

### 3️⃣ Sube el App Bundle

#### En "Entrega por App Bundle"
- Selecciona y arrastra: `app-release.aab`
- Espera a que se cargue ✅

#### Información de versión
- **Número de versión:** 1.081 (ya viene en el AAB)
- **Notas de la versión:** Describe los cambios

### 4️⃣ Sube los archivos de desofuscación y símbolos

#### Archivo de desofuscación (Mapping)
- Click en: **Agregar archivo de símbolo**
- Selecciona: `android/app/build/outputs/mapping/release/mapping.txt`
- **Tipo:** Mapping file de R8/Proguard

#### Archivos de símbolos nativos (IMPORTANTE - Play Store lo pide)
- Click en: **Agregar archivo de símbolo**
- Selecciona: `android-native-symbols-release.tar.gz`
- **Tipo:** Archive de símbolos nativos o TAR.GZ

### 5️⃣ Revisa los cambios de compatibilidad
- ⚠️ "Esta versión ya no es compatible con 687 dispositivos"
- **Motivo:** Incremento de API level 34 → 35
- **Impacto:** Dispositivos con Android < 6.0 (API < 23) no compatibles
- **Decisión:** ✅ Aceptable - Android 5.0 ya está obsoleto

### 6️⃣ Confirma y envía
- Review todo
- Click: **Enviar para revisión** o **Publicar**

---

## 🔍 Verificación pre-upload

### ¿Está todo correcto?

```bash
# Verifica que el AAB existe y tiene tamaño
ls -lh android/app/build/outputs/bundle/release/app-release.aab
# Output: app-release.aab (4.5 MB)

# Verifica que el mapping.txt existe
ls -lh android/app/build/outputs/mapping/release/mapping.txt
# Output: mapping.txt (15 MB)

# Verifica que los símbolos nativos existen
ls -lh android-native-symbols-release.tar.gz
# Output: android-native-symbols-release.tar.gz (7.5 KB)

# Verifica la versión en el build.gradle
grep versionName android/app/build.gradle
# Output: versionName "1.081"

# Verifica el API level
grep compileSdkVersion android/variables.gradle
# Output: compileSdkVersion = 35
```

---

## 📊 Resumen de cambios en esta versión

### Configuración de Gradle
```groovy
compileSdkVersion 35              // Requerimiento de Play Store ✅
minSdkVersion 23                  // Compatible con Android 6.0+
targetSdkVersion 35               // Optimizado para Android 15

// Release build
minifyEnabled true                // Minificación con R8 ✅
shrinkResources true              // Reducir recursos no usados
proguardFiles 'proguard-rules.pro' // Reglas de ofuscación
ndkVersion "26.0.10340659"        // Soporte para símbolos nativos ✅
```

### ProGuard Rules
- ✅ Preserva líneas de depuración (SourceFile, LineNumberTable)
- ✅ Protege clases de Capacitor, Firebase, Plugins
- ✅ Permite desofuscación de crashes en Play Console

### Símbolos nativos
- ✅ Incluidos en el build
- ✅ Archivados en TAR.GZ para upload
- ✅ Múltiples arquitecturas soportadas (arm64-v8a, armeabi-v7a, x86, x86_64)

---

## 📲 Después de publicar

### En Play Console, verifica:
1. **Build → Lanzamientos → Producción**
   - Estado: "Lanzado"
   - Usuarios: Número de instalaciones
   - Compatibilidad: Basada en API 35

2. **Estadísticas → Crashes y ANR**
   - Si hay crashes, aparecerán desofuscados automáticamente
   - Gracias al archivo mapping.txt
   - Símbolos nativos estarán disponibles para depuración

3. **Gestión de versiones**
   - La versión 1.081 debe estar disponible
   - Usuarios con versiones anteriores reciben update
   - ~687 dispositivos con API < 23 quedan sin soporte (esperado)

---

## 🐛 Si hay problemas

### Error: "API level demasiado bajo"
- ✅ Ya está solucionado (API 35)

### Warning: "No mapping file"
- ✅ Ya está incluido (mapping.txt)

### Warning: "No debug symbols"
- ✅ Ya están incluidos (mapping.txt + native symbols)
- ✅ Sube también el archivo `android-native-symbols-release.tar.gz`

### Warning: "Pérdida de compatibilidad con 687 dispositivos"
- ✅ Normal y esperado
- ✅ Debido a incremento de API 35
- ✅ Aceptable - Android < 6.0 ya no es soportado

### La app no se instala en algunos dispositivos
- Normal: compileSdk 35 requiere compatibilidad con Android 15
- Los dispositivos con Android < 6.0 no son compatibles
- Esto es esperado y aceptable

---

## 💾 Archivos importantes para archivar

Después de publicar, guarda estos archivos por si necesitas depuración:

```bash
# Mapping.txt (para desofuscación)
cp android/app/build/outputs/mapping/release/mapping.txt \
   backups/mapping-v1.081.txt

# Símbolos nativos
cp android-native-symbols-release.tar.gz \
   backups/native-symbols-v1.081.tar.gz

# APK (en caso de que Play Store lo requiera)
cp android/app/build/outputs/apk/release/app-release.apk \
   backups/app-release-v1.081.apk

# AAB (copia de seguridad)
cp android/app/build/outputs/bundle/release/app-release.aab \
   backups/app-release-v1.081.aab
```

---

## ✅ Checklist final

- [ ] App Bundle generado (app-release.aab) ✅
- [ ] Mapping.txt generado (15 MB) ✅
- [ ] Símbolos nativos generados (7.5 KB) ✅
- [ ] API Level = 35 ✅
- [ ] Minificación habilitada ✅
- [ ] versionCode incrementado ✅
- [ ] versionName actualizado (1.081) ✅
- [ ] Probado en emulador ✅
- [ ] Listo para Play Store ✅

Si todo está checked, estás listo para publicar en Play Store.

---

## 📋 Archivos a descargar/copiar

Antes de subir a Play Store, asegúrate de tener estos archivos accesibles:

```
✅ app-release.aab                          (4.5 MB)
✅ mapping.txt                              (15 MB)
✅ android-native-symbols-release.tar.gz    (7.5 KB)
```

Todos están listos en tu directorio de proyecto.
