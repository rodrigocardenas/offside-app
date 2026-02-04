# 🔧 Firebase Gradle Configuration - Actualizado

**Fecha:** 5 feb 2026  
**Commit:** e16c61e  
**Status:** ✅ LISTO PARA COMPILAR

---

## ✅ Lo que se hizo

### Root `build.gradle` (android/build.gradle)
```groovy
plugins {
    id 'com.google.gms.google-services' version '4.4.0' apply false
}
```
✅ Plugin agregado en seccion `plugins` (moderna)

### App `build.gradle` (android/app/build.gradle)
```groovy
apply plugin: 'com.android.application'
apply plugin: 'com.google.gms.google-services'  // ← AGREGADO
```

### Dependencias Firebase
```groovy
dependencies {
    // Firebase BoM (Bill of Materials)
    implementation platform('com.google.firebase:firebase-bom:34.8.0')
    
    // Firebase Messaging (push notifications)
    implementation 'com.google.firebase:firebase-messaging'
    
    // Firebase Analytics
    implementation 'com.google.firebase:firebase-analytics'
}
```

---

## 🚀 Próximo paso: Compilar

```bash
cd c:\laragon\www\offsideclub

# Limpiar cache anterior (si compilación anterior falló)
cd android
./gradlew clean
cd ..

# Sincronizar Capacitor
npx cap sync android

# Compilar
cd android
./gradlew assembleDebug

# Espera 5-10 minutos...
# ✅ BUILD SUCCESSFUL = listo
```

---

## ✅ Señales de Éxito

Cuando veas esto:
```
BUILD SUCCESSFUL in Xm Ys
```

El APK estará en:
```
android/app/build/outputs/apk/debug/app-debug.apk
```

---

## 🚨 Si sigue fallando

### Error 1: "google-services plugin not found"
```
❌ Plugin with id 'com.google.gms.google-services' not found
```

**Solución:**
```bash
cd android
./gradlew clean
cd ..
npx cap sync android
cd android
./gradlew assembleDebug
```

### Error 2: "google-services.json not found"
```
❌ Task failed: No google-services.json file found
```

**Solución:**
- Descarga desde Firebase Console (offside-dd226)
- Cópialo en: `android/app/google-services.json`
- Reintenta compilación

### Error 3: "Firebase dependency conflict"
```
❌ Dependency conflict: com.google.firebase:firebase-bom vs other version
```

**Solución:**
```bash
# Actualizar todo
npm update
cd android
./gradlew dependencies --refresh-dependencies
./gradlew clean assembleDebug
```

---

## 📚 Referencias

- Commit: e16c61e (Firebase Gradle config)
- [Firebase Android Setup](https://firebase.google.com/docs/android/setup)
- [Google Services Gradle Plugin](https://developers.google.com/android/guides/google-services-plugin)

---

## 🎯 Checklist Pre-Compilación

```
☐ google-services.json descargado
☐ google-services.json en: android/app/google-services.json
☐ Root build.gradle tiene: plugins { id 'com.google.gms.google-services' }
☐ App build.gradle tiene: apply plugin: 'com.google.gms.google-services'
☐ Dependencies tiene: Firebase BoM + messaging + analytics
☐ npm install ejecutado
☐ npx cap sync android ejecutado
```

Si todo está ☑️, ejecuta:
```bash
cd android
./gradlew assembleDebug
```

