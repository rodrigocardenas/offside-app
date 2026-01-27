# Bugs Pendientes - Próximos Pasos

## Estado Actual

### ✅ Bug #1: Android Back Button - COMPLETADO
- **Problema**: El botón de atrás nativo de Android siempre vuelve a la pantalla de inicio
- **Solución**: Handler de Capacitor que usa `history.back()`
- **Status**: Implementado y listo para testing
- **Archivos**: 
  - `public/js/android-back-button.js`
  - `ANDROID_BACK_BUTTON_FIX.md`
  - `ANDROID_BACK_BUTTON_SUMMARY.md`
  - `test-android-back-button.sh`

### ⏳ Bug #2: Deep Links Not Working
- **Prioridad**: ALTA (necesario para marketing/invitaciones)
- **Descripción**: Links como `offsideclub://match/123` no abren la app correctamente
- **Afectados**: Compartir partidos, invitaciones, notificaciones push
- **Configuración pendiente**: 
  - `capacitor.config.ts` - schemes de deep linking
  - `android/app/src/main/AndroidManifest.xml` - intent filters
  - Router configuration en Vue/Alpine

### ⏳ Bug #3: Firebase Notifications
- **Prioridad**: ALTA (comunicación con usuarios)
- **Descripción**: Notificaciones push no llegan o no se abren correctamente
- **Afectados**: Actualizaciones de partidos, mensajes importantes
- **Configuración pendiente**:
  - Firebase Cloud Messaging setup
  - Capacitor Push Notifications plugin
  - Backend integration

### ⏳ Bug #4: Content Cache Issues
- **Prioridad**: MEDIA (performance/UX)
- **Descripción**: Contenido viejo cached incluso después de actualizar
- **Afectados**: Partidos, tablas de posiciones, contenido dinámico
- **Solución pendiente**:
  - Service Worker cache strategy
  - Cache busting headers
  - Cache invalidation logic

---

## Recomendación: Orden de Implementación

### Fase 1 (Inmediata): Completar Bug #1
1. ✅ Código implementado
2. 🔄 **SIGUIENTE**: Build y test en Android
3. 🔄 Verificar en múltiples dispositivos
4. 🔄 Deploy a producción

### Fase 2 (Después del test de Bug #1): Deep Links
1. Implementar scheme registration en `capacitor.config.ts`
2. Configurar intent filters en AndroidManifest.xml
3. Crear route handler en Vue para deep links
4. Test con comandos `adb` para abrir links

### Fase 3: Firebase Notifications
1. Obtener credenciales Firebase
2. Instalar y configurar plugin de Push
3. Backend para enviar notificaciones
4. Test con Firebase Console

### Fase 4: Cache Strategy
1. Análisis de qué cachear
2. Configurar Service Worker
3. Implementar invalidación
4. Test con dev tools

---

## Para empezar con Bug #1 testing:

```bash
# Compilar la app
./test-android-back-button.sh build

# Luego:
# 1. Abre Android Studio que se abrirá automáticamente
# 2. Selecciona un emulador o conecta un dispositivo
# 3. Presiona Run
# 4. Prueba navegando y presionando atrás
```

**Reporta los resultados del testing para proceder con Bug #2**

---

## Archivos Relacionados

- `BUGS_REPORTED_PRIORITIZED.md` - Documento original con todos los bugs
- `ANDROID_BACK_BUTTON_FIX.md` - Guía detallada del fix
- `ANDROID_BACK_BUTTON_SUMMARY.md` - Resumen ejecutivo
- `test-android-back-button.sh` - Script de testing automatizado
- `public/js/android-back-button.js` - Código del handler
