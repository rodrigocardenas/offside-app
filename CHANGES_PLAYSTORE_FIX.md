# ��� Cambios realizados para Play Store (11 Feb 2026)

## Problemas solucionados

### ❌ Error: API Level 34 → ✅ Solucionado con API 35
**Antes:** La app estaba compilada con `compileSdkVersion = 34`  
**Ahora:** Compilada con `compileSdkVersion = 35`  
**Archivos modificados:**
- `android/variables.gradle`: Actualizado compileSdk y targetSdk a 35

### ❌ Warning: Sin archivo de desofuscación → ✅ Solucionado con mapping.txt
**Antes:** `minifyEnabled = false` en release build  
**Ahora:** `minifyEnabled = true` con R8/Proguard  
**Archivos modificados:**
- `android/app/build.gradle`: Habilitada minificación
- `android/app/proguard-rules.pro`: Configuradas reglas de ofuscación
- Se genera automáticamente: `mapping.txt` (15 MB)

### ❌ Warning: Sin símbolos de depuración → ✅ Solucionado con NDK y ProGuard
**Antes:** No se preservaban líneas de depuración  
**Ahora:** Se preservan SourceFile y LineNumberTable  
**Archivos modificados:**
- `android/app/build.gradle`: Agregada versión del NDK (26.0.10340659)
- `android/app/proguard-rules.pro`: Agregadas reglas para preservar debug info

### ⚠️ Warning: Pérdida de compatibilidad con 691 dispositivos
**Causa:** Incremento de minSdkVersion de 21 a 23  
**Impacto:** Los dispositivos con Android < 6.0 (API < 23) no pueden instalar  
**Decisión:** Aceptable - Android 5.0 (API 21) ya está obsoleto

---

## ��� Archivos modificados

### 1. `android/variables.gradle`
```diff
- compileSdkVersion = 34
+ compileSdkVersion = 35
- targetSdkVersion = 34
+ targetSdkVersion = 35
```

### 2. `android/app/build.gradle`
```diff
  buildTypes {
    release {
-     minifyEnabled false
+     minifyEnabled true
+     shrinkResources true
-     proguardFiles getDefaultProguardFile('proguard-android.txt')
+     proguardFiles getDefaultProguardFile('proguard-android-optimize.txt')
    }
  }
+ ndkVersion "26.0.10340659"
```

### 3. `android/app/capacitor.build.gradle`
```diff
  android {
+   compileSdk 35
    compileOptions {
      sourceCompatibility JavaVersion.VERSION_17
      targetCompatibility JavaVersion.VERSION_17
    }
  }
```

### 4. `android/app/proguard-rules.pro`
Completamente reescrito con:
- Preservación de líneas de depuración
- Protección de clases Capacitor, Firebase, Plugins
- Reglas para WebView, AndroidX, Native methods

---

## ��� Archivos generados automáticamente

Después de `./gradlew bundleRelease`:

```
android/app/build/outputs/
├── bundle/release/
│   └── app-release.aab (4.5 MB) ← Para Play Store
├── apk/release/
│   └── app-release.apk (3.1 MB)
└── mapping/release/
    ├── mapping.txt (15 MB) ← Para desofuscación
    ├── configuration.txt
    ├── seeds.txt
    ├── usage.txt
    └── resources.txt
```

---

## ✅ Pasos ya realizados

- [x] Incrementar compileSdk a 35
- [x] Habilitar minificación (R8)
- [x] Generar mapping.txt
- [x] Preservar símbolos de depuración
- [x] Configurar NDK version
- [x] Actualizar ProGuard rules
- [x] Compilar App Bundle
- [x] Verificar archivos generados

---

## ��� Próximos pasos del usuario

1. Descarga el App Bundle:
   ```
   android/app/build/outputs/bundle/release/app-release.aab
   ```

2. En Play Console, sube:
   - El archivo `.aab`
   - El archivo `mapping.txt` para desofuscación

3. Revisa compatibilidad de dispositivos (nueva basada en API 35)

4. Publica cuando esté listo

---

## ��� Comparativa Antes vs Después

| Aspecto | Antes | Después | Estado |
|---------|-------|---------|--------|
| API Level compileSdk | 34 | 35 | ✅ Cumple Play Store |
| API Level targetSdk | 34 | 35 | ✅ Optimizado para Android 15 |
| Minificación | Deshabilitada | Habilitada | ✅ Reduce tamaño |
| Mapping.txt | No existe | Existe (15 MB) | ✅ Desofuscación |
| Debug symbols | No preservados | Preservados | ✅ Debugging en Play Console |
| NDK version | N/A | 26.0.10340659 | ✅ Símbolos nativos |
| Tamaño APK | N/A | 3.1 MB | ✅ Optimizado |
| Tamaño AAB | N/A | 4.5 MB | ✅ Listo para Play Store |

---

## ��� Verificación

Todo compiló exitosamente sin errores:
- App Bundle: ✅ BUILD SUCCESSFUL in 2m 27s
- Release APK: ✅ Minificado con R8
- Mapping.txt: ✅ Generado automáticamente

La app está lista para Play Store.
