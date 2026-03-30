# 📱 Guía de Testing - Bugs Móviles #1, #2, #5

## Objetivo
Verificar que los 3 bugs están completamente solucionados en el dispositivo Android.

---

## Preparación

### Requerimientos
- Dispositivo Android (mínimo Android 8.0)
- USB habilitado en desarrollo/depuración
- ADB instalado (opcional, para logs)
- APK: `/c/laragon/www/offsideclub/android/app/build/outputs/apk/debug/app-debug.apk`

### Instalación

#### Opción 1: ADB (Recomendado - para logs)
```bash
# Conectar dispositivo USB y habilitar depuración
adb devices

# Instalar APK
adb install -r android/app/build/outputs/apk/debug/app-debug.apk

# Ver logs en tiempo real
adb logcat | grep -E "DeepLinks|AndroidBackButton|PullToRefresh"
```

#### Opción 2: Instalación Manual
1. Transferir `app-debug.apk` a dispositivo (USB, email, cloud)
2. Abrir Files → Navegar a archivo
3. Click en APK → "Install"
4. Permitir instalación desde fuentes desconocidas si es necesario

---

## Testing - Bug #1: Android Back Button ✅

### Caso de Uso
Usuario presiona botón atrás de Android. Esperado: Navega a página anterior en historial.

### Pasos
1. **Abrir App**
   - Launch "OffsideClub" desde home
   - Esperar a que cargue completamente

2. **Crear Historial de Navegación**
   - Ir a "Matches" → Click en un partido
   - Ir a "Groups" → Click en un grupo
   - Ir a "Profile"
   - Ahora tienes historial: Home → Matches → Match Detail → Groups → Group Detail → Profile

3. **Test Back Button**
   - Presiona botón atrás de Android (esquina abajo izquierda)
   - **Esperado**: Navega a página anterior (Profile → Group Detail)
   - Presiona nuevamente
   - **Esperado**: Navega a Group Detail → Match Detail
   - Continuar hasta llegar a Home
   - Presiona en Home
   - **Esperado**: Muestra diálogo "¿Seguro que deseas salir?"

4. **Verificación de Logs** (si tienes ADB)
   ```
   [AndroidBackButton] Manejador inicializado correctamente
   [AndroidBackButton] Back button presionado
   [AndroidBackButton] Navegando: page anterior
   ```

### Criterios de Éxito
- ✅ Back button navega a página anterior
- ✅ En Home muestra diálogo de salida
- ✅ Logs muestran `[AndroidBackButton]`
- ✅ NO cierra la app abruptamente

### Bugs Conocidos
- Ninguno hasta ahora

---

## Testing - Bug #5: Pull-to-Refresh 🟡

### Caso de Uso
Usuario hace pull/swipe desde arriba de la pantalla. Esperado: Recarga datos frescos.

### Pasos
1. **Abrir App**
   - Launch "OffsideClub"
   - Ir a "Matches" o página con lista de elementos

2. **Ejecutar Pull-to-Refresh**
   - Scroll hasta TOP de página (si aplica)
   - Posiciona dedo en borde superior
   - DRAG/SWIPE hacia abajo ~80-100px
   - **Esperado**: Aparece indicador/loader visual
   - Espera ~2-3 segundos
   - **Esperado**: Página recarga, loader desaparece

3. **Verificación Visual**
   - ✅ Barra gradiente/loader visible mientras pulls
   - ✅ Página se recarga
   - ✅ Datos frescos (timestamps nuevos)

4. **Verificación de API** (opcional)
   - Verificar Network Tab en DevTools si está disponible
   - Debería hacer GET a `/api/cache/clear-user`
   - Luego reload de página

5. **Logs** (si tienes ADB)
   ```
   [PullToRefresh] Gestor inicializado correctamente
   [PullToRefresh] Refresh triggered
   [PullToRefresh] Refreshing page...
   ```

### Criterios de Éxito
- ✅ Pull desde arriba activa loader
- ✅ Página se recarga después del pull
- ✅ Datos están frescos (no caché viejo)
- ✅ Funciona múltiples veces
- ✅ NO activa si pulls en mitad de página (solo arriba)

### Bugs Potenciales
- Si no recarga después del pull → Verificar `/api/cache/clear-user` es callable
- Si loader no aparece → Revisar CSS de pull-to-refresh.js

---

## Testing - Bug #2: Deep Links 🟡

### Caso de Uso
Usuario click en link `offsideclub://group/123`. Esperado: App abre directamente en ese grupo.

### Pasos - Opción 1: Link Manual

1. **Generar Links de Testing**
   ```
   # Grupo
   offsideclub://group/1
   
   # Partido
   offsideclub://match/1
   
   # Perfil
   offsideclub://profile/1
   
   # Invitación (si tienes token real)
   offsideclub://invite/abc123
   ```

2. **Compartir Link**
   - Abrir Notes, Chat, o cualquier app de texto
   - Escribir o pegar: `offsideclub://group/1`
   - Seleccionar todo
   - Copiar

3. **Test del Link**
   - Click en el link
   - Si app está cerrada → Abre app EN SEGUIDA a página específica
   - Si app está abierta → Navega a página específica
   - **Esperado**: Ve detalles del grupo/partido/perfil #1

4. **Variantes de Testing**
   - Cerrar app completamente → Click link → Abre directamente a recurso
   - App abierta → Click link → Navega al recurso
   - Link inválido (ej: `offsideclub://group/99999`) → ¿Muestra error o 404?

### Pasos - Opción 2: Deep Links Reales (Invitaciones)

1. **Crear Invitación a Grupo**
   - Ir a Grupo
   - Click "Invitar" o similar
   - Generar/copiar link de invitación
   - Típicamente: `https://app.offsideclub.es/invite/{token}` 
   - Convertir a: `offsideclub://invite/{token}`

2. **Compartir Invitación**
   - Copiar link convertido
   - Compartir por WhatsApp, SMS, Email, Chat
   - Enviar a otro dispositivo o amigo

3. **Test Invitación**
   - En dispositivo receptor: Click link
   - App abre → Página de invitación
   - Click "Aceptar" → Se agrega usuario a grupo
   - ✅ Confirmado: Deep link funcionó

### Verificación de Logs
```
[DeepLinks] Handler inicializado correctamente
[DeepLinks] Deep link detectado: offsideclub://group/1
[DeepLinks] URL parseada: grupo = 1
[DeepLinks] Navegando a /groups/1
```

### Criterios de Éxito
- ✅ Link `offsideclub://` abre app en lugar de navegador
- ✅ App navega a recurso específico (grupo/partido/perfil)
- ✅ Funciona con app cerrada y abierta
- ✅ Funciona con invitaciones reales
- ✅ Logs muestran navegación correcta

### Bugs Potenciales
- Link abre navegador → Intent-filter no está configurado o APK vieja
- Link abre app pero no navega → Deep links handler no se ejecuta
- Navega a página equivocada → URL parsing es incorrecto
- 404 después de navegar → ID de recurso inválido (OK, expected behavior)

---

## Matriz de Testing

| Bug | Caso | Acción | Esperado | Estado |
|-----|------|--------|----------|--------|
| #1 | Back btn | Presionar botón atrás | Navega atrás en historial | ⏳ Testing |
| #1 | Back btn Home | Presionar en Home | Muestra diálogo salida | ⏳ Testing |
| #5 | Pull-to-Refresh | Drag desde arriba | Loader aparece + Recarga | ⏳ Testing |
| #5 | Pull-to-Refresh múltiple | Pull 3+ veces | Funciona cada vez | ⏳ Testing |
| #2 | Deep link grupo | Click `offsideclub://group/1` | Abre grupo #1 | ⏳ Testing |
| #2 | Deep link cerrada | Link con app cerrada | Abre app + Navega | ⏳ Testing |
| #2 | Deep link invitación | Click link invitación | Abre página invitación | ⏳ Testing |
| #2 | Deep link inválido | Click `offsideclub://group/999` | 404 o error amable | ⏳ Testing |

---

## Troubleshooting

### Bug #1: Back button no funciona
**Síntoma**: Back button cierra app completamente
**Causas**:
- APK vieja (no tiene nuevo código)
- Plugin @capacitor/app no instalado
- Capacitor no detectado

**Solución**:
```bash
# Reinstalar APK
adb uninstall com.offsideclub.app
adb install -r app-debug.apk

# Ver logs
adb logcat | grep Android

# Si logs muestran "No estamos en Capacitor" → APK vieja
# Si logs vacíos → Plugin no instalado
```

### Bug #5: Pull-to-refresh no funciona
**Síntoma**: Pull desde arriba no hace nada
**Causas**:
- APK vieja
- Touch events no se disparan
- `/api/cache/clear-user` falla

**Solución**:
```bash
# Ver logs
adb logcat | grep PullToRefresh

# Si NO ves "[PullToRefresh] Gestor inicializado" → APK vieja

# Verificar red
adb logcat | grep "cache/clear"
# Si no aparece → API no se llamó → Bug en handler

# En dispositivo: Abrir DevTools si está disponible
# Verificar Network → Ver si GET /api/cache/clear-user se hace
```

### Bug #2: Deep links no abren app
**Síntoma**: Click en `offsideclub://` abre navegador en lugar de app
**Causas**:
- APK vieja (sin intent-filter)
- Intent-filter mal configurado
- Capacitor no compiló cambios

**Solución**:
```bash
# Desinstalar e instalar APK nueva
adb uninstall com.offsideclub.app
adb install -r app-debug.apk

# Ver logs
adb logcat | grep DeepLinks

# Si NO ves "[DeepLinks] Handler inicializado" → APK vieja

# Verificar intent-filter en APK (requiere apktool)
# O simplemente reinstalar APK más nueva

# Probar desde otra app (Chrome, Notes, etc)
# para confirmar que intent-filter funciona
```

### General: "Cambios no aparecen"
**Síntoma**: Instalo APK pero cambios no se ven
**Causas**:
- APK vieja en caché
- Diferente build/variante

**Solución**:
```bash
# Opción 1: Uninstall + Clean Install
adb uninstall com.offsideclub.app
adb install -r android/app/build/outputs/apk/debug/app-debug.apk

# Opción 2: Limpiar caché de app
adb shell pm clear com.offsideclub.app
# Luego reinstalar APK

# Opción 3: Verificar APK correcto
adb shell pm dump com.offsideclub.app | grep -i version
# Comparar versionCode con APK generada
```

---

## Logging en Tiempo Real

### Setup ADB Logs
```bash
# Terminal 1: Ver todos los logs
adb logcat

# Terminal 2: Ver solo nuestros logs
adb logcat | grep -E "DeepLinks|AndroidBackButton|PullToRefresh"

# O guardarpd en archivo
adb logcat > logs.txt &
# ... hacer testing ...
# Ctrl-C para parar
```

### Interpretar Logs

**Buenos signos**:
```
[AndroidBackButton] Capacitor detectado
[AndroidBackButton] Manejador inicializado correctamente
[DeepLinks] Handler inicializado correctamente
[PullToRefresh] Gestor inicializado correctamente
```

**Malos signos**:
```
[AndroidBackButton] No estamos en Capacitor, skipping
  → APK vieja o plugin no instalado

error: invalid source release: 21
  → Problema compilación, no APK

[error] HandleBackButton error
  → Bug en handler code

no logs
  → Code no se ejecuta, APK vieja
```

---

## Reporte de Resultados

### Template de Resultado
```markdown
## Testing Results - Bug #X

### Dispositivo
- Modelo: Samsung Galaxy S21
- Android: 13
- APK versión: app-debug.apk (fecha)

### Bug #1: Android Back Button
- [x] Back button navega atrás
- [x] Home muestra diálogo
- [ ] (marcar si falla)

### Bug #5: Pull-to-Refresh
- [x] Pull activa loader
- [x] Página recarga
- [ ] (marcar si falla)

### Bug #2: Deep Links
- [x] Link abre app (app cerrada)
- [x] Link navega correcto
- [x] Invitaciones funcionan
- [ ] (marcar si falla)

### Logs
```
[copiar logs relevantes]
```

### Notas
[Cualquier observación, comportamiento extraño, etc]
```

---

## Próximos Pasos Tras Testing

1. **Si TODO funciona** ✅
   - Actualizar versión en `build.gradle`
   - Build APK release (no debug)
   - Deploy a Play Store
   - Update version en stores

2. **Si ALGO falla** 🔧
   - Documentar bug con logs
   - Revisar handler code
   - Recompilador si necesario
   - Re-test

3. **Si MUCHAS cosas fallan** 🚨
   - Verificar que APK es correcta
   - Verificar que sea APK completa (no vieja)
   - Re-compilar desde 0: `npm run build && npx cap sync && ./gradlew clean assembleDebug`

---

## Conclusión

Sigue estos pasos para confirmar que todos los bugs están solucionados. Si todo funciona, la app está lista para production. Si algo falla, documenta con logs y revisamos el código.

**¡Buen testing!** 🚀
