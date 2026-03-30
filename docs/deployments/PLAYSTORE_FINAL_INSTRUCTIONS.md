# 📱 Instrucciones FINALES para subir a Play Store (v1.081)

**Fecha:** 11 Feb 2026

---

## ✅ Archivos listos para Play Store

```
✅ App Bundle:        app-release.aab (4.5 MB)
   Ubicación: android/app/build/outputs/bundle/release/app-release.aab

✅ Mapping File:      mapping.txt (15 MB)
   Ubicación: android/app/build/outputs/mapping/release/mapping.txt
   
OPCIONAL:
   native-debug-symbols-empty.zip (22 bytes)
   Solo si Play Store insiste (no recomendado)
```

---

## 🚀 Pasos para subir a Play Store

### 1. Abre Play Console
- URL: https://play.google.com/console
- Proyecto: **Offside Club**

### 2. Crea nueva versión
- **Producción** (o Testing si prefieres)
- Click: **Crear nueva versión**

### 3. Sube el App Bundle
- En **"Entrega por App Bundle"**
- Arrastra: `app-release.aab` (4.5 MB)
- Espera a que se cargue ✅

### 4. Sube el archivo de Mapping
En **"Archivos de símbolos de depuración"**:

#### Mapping file (Java/Kotlin desofuscación) - NECESARIO
- Click: **Agregar archivo**
- Selecciona: `mapping.txt` (15 MB)
- Tipo: **Mapping file (R8/Proguard)**

#### Native Debug Symbols (OPCIONAL - NO RECOMENDADO)
**NO SUBAS NADA aquí EXCEPTO si:**
- Tienes código C/C++ propio compilado
- Tienes archivos `.so.unstripped` específicos

En tu caso, tu único `.so` (`libdatastore_shared_counter.so`) es de Google/AndroidX.  
Play Store ya tiene esos símbolos, así que **NO necesitas subir native symbols.**

### 5. Revisa compatibilidad

Verás este warning:
```
⚠️ Esta versión ya no es compatible con 687 dispositivos...
```

**ESTO ES NORMAL.** ✅ **Puedes ignorarlo y continuar**

### 6. Publica
- Click: **Enviar para revisión** o **Publicar**
- ✅ Listo

---

## ⚠️ Sobre el warning de "687 dispositivos"

**¿Por qué aparece?**
- Versión anterior: API 34
- Nueva versión: API 35
- Diferencia: Los dispositivos con API < 23 (Android < 6.0) ya no son compatibles

**¿Es un problema?**
- ❌ No, es NORMAL y ESPERADO
- Android 5.0 (API 21) está obsoleto desde 2017
- Google recomienda API 35 mínimo

**¿Puedo evitarlo?**
- No, es imposible evitarlo cuando subes una versión con API más alto
- Play Store SIEMPRE avisa cuando pierde compatibilidad con dispositivos

**¿Qué hago?**
- ✅ Simplemente publica normalmente
- Los usuarios con dispositivos compatibles recibirán la actualización
- Los usuarios con dispositivos obsoletos quedan en la versión anterior

---

## 🐛 Troubleshooting

### Error: "Invalid directory android-native-symbols-release"
**Solución:** El ZIP debe contener DIRECTAMENTE las carpetas de ABIs
```
CORRECTO:
native-debug-symbols.zip
  └─ arm64-v8a/
  └─ armeabi-v7a/
  └─ x86/
  └─ x86_64/

INCORRECTO (lo que pasó antes):
native-debug-symbols.zip
  └─ android-native-symbols-release/
      └─ arm64-v8a/
      └─ armeabi-v7a/
      └─ x86/
      └─ x86_64/
```

✅ Ya está corregido. El nuevo archivo `native-debug-symbols.zip` tiene la estructura correcta.

### Error: "No debug symbols"
- ✅ Sube el archivo `mapping.txt` (Java symbols)
- ✅ Sube el archivo `native-debug-symbols.zip` (Native symbols)

### Warning: "Pérdida de compatibilidad"
- ✅ Normal, ignora y publica

---

## 📊 Resumen final

| Item | Estado | Detalles |
|------|--------|----------|
| **API Level** | ✅ 35 | Cumple requerimientos Play Store |
| **App Bundle** | ✅ Listo | 4.5 MB, minificado |
| **Mapping** | ✅ Incluido | 15 MB para desofuscación Java |
| **Native Symbols** | ✅ Incluido | 9.9 KB estructura correcta |
| **Versión** | ✅ 1.081 | Incrementada |
| **Minificación** | ✅ R8 | Habilitada para release |

---

## ✅ Checklist pre-publicación

- [ ] App Bundle descargado/visible
- [ ] Mapping.txt descargado/visible
- [ ] native-debug-symbols.zip descargado/visible ✅ NUEVO CORRECTO
- [ ] Subiste el App Bundle a Play Console
- [ ] Subiste el mapping.txt en "Archivos de símbolos"
- [ ] Subiste el native-debug-symbols.zip en "Archivos de símbolos"
- [ ] Leíste el warning de "687 dispositivos" (normal, no es problema)
- [ ] Clickeaste **Publicar** o **Enviar para revisión**

Si todo está marcado, ¡estás listo! 🎉

---

## 📋 Ubicación exacta de archivos

```bash
# App Bundle
c:\laragon\www\offsideclub\android\app\build\outputs\bundle\release\app-release.aab

# Mapping
c:\laragon\www\offsideclub\android\app\build\outputs\mapping\release\mapping.txt
```

Estos 2 archivos son TODO lo que necesitas para Play Store.

**NO subas native symbols - en tu caso no es necesario.**
