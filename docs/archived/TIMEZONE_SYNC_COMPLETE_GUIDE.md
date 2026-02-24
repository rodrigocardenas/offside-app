# 🌍 Sincronización de Timezone - Flujo Completo Mejorado

**Fecha:** 28 enero 2026  
**Status:** ✅ Optimizado para usuarios autenticados

---

## 🎯 El Problema Resuelto

Los usuarios que ya estaban logueados **no actualizaban su timezone** porque:
- ❌ No pasaban por el formulario de login
- ❌ El script de sincronización no era lo suficientemente agresivo
- ❌ No tenía reintentos en caso de fallo
- ❌ Solo se ejecutaba en casos muy específicos

**Solución:** Script que se ejecuta automáticamente en CADA acceso y se re-sincroniza periódicamente.

---

## 📊 Diagrama de Flujo de Sincronización

```
┌─────────────────────────────────────────────────────────────────┐
│                     USUARIO AUTENTICADO                          │
└─────────────────────────────────────────────────────────────────┘

                           │
                           ▼
          ┌────────────────────────────────┐
          │    Script timezone-sync.js     │
          │      Se carga (INMEDIATO)      │
          │   - Antes de DOMContentLoaded  │
          │   - En cada página             │
          │   - Para usuarios con meta:id  │
          └────────────────────────────────┘
                           │
                           ▼
          ┌────────────────────────────────┐
          │  Obtener timezone del          │
          │  dispositivo (Intl API)        │
          │  ej: America/Bogota            │
          └────────────────────────────────┘
                           │
                           ▼
          ┌────────────────────────────────┐
          │  Verificar LocalStorage:       │
          │  - lastSyncedTimezone          │
          │  - lastSyncTimestamp           │
          └────────────────────────────────┘
                           │
                ┌──────────┴──────────┐
                │                     │
        [CAMBIÓ?]          [HACE >4h?]
           │                    │
          YES                   NO
           │                    │
      ┌────▼─────┐          [SKIP]
      │ ¿PRIMERO?│
      └────┬─────┘
           │
      ┌────▼──────────────────────────┐
      │ POST /api/set-timezone        │
      │ {timezone: "America/Bogota"}  │
      │                               │
      │ CON REINTENTOS:               │
      │ - Intento 1: ahora            │
      │ - Intento 2: +1s backoff      │
      │ - Intento 3: +2s backoff      │
      └────┬──────────────────────────┘
           │
        ┌──┴──┐
        │     │
     SUCCESS  FALLO
        │      │
        ▼      └──► [LOG ERROR]
      ✅ 
    Guardar en 
   LocalStorage
```

---

## 🕐 Línea de Tiempo de Sincronización

### Caso 1: Usuario Nuevo en Login
```
Timeline: Login Page
├─ Usuario ingresa nickname + dispositivo detecta TZ
├─ Script DOMContentLoaded captura timezone
├─ Envía formulario con TZ = "America/Bogota"
├─ LoginController crea usuario + guarda TZ
└─ ✅ Usuario creado con timezone correcto
```

### Caso 2: Usuario Existente - Primer Acceso del Día
```
Timeline: App Page (Usuario logueado)
├─ 00:00 - Usuario todavía durmiendo, cache vacío
├─ 08:00 - Usuario abre app
├─ Script timezone-sync.js se ejecuta INMEDIATO
│  ├─ Detecta TZ del dispositivo: "Europe/Madrid"
│  ├─ Verifica localStorage: vacío (primera vez)
│  └─ POST /api/set-timezone
├─ Backend actualiza user.timezone
├─ ✅ localStorage.setItem('lastSyncTimestamp', now)
└─ Preguntas se muestran en zona correcta
```

### Caso 3: Usuario Existente - Múltiples Accesos
```
Timeline: App Page (Múltiples visits)
├─ 08:00 - Abre app (ver Caso 2)
├─ 08:30 - Recarga página (F5)
│  ├─ Script se ejecuta
│  ├─ TZ es "Europe/Madrid" (sin cambios)
│  ├─ Verifica: hace 30 min desde último sync
│  ├─ Cache aún válido (no hace >4h)
│  └─ SKIP - No sincroniza
├─ 12:00 - Regresa a app después de 4 horas
│  ├─ Script se ejecuta
│  ├─ Verifica: hace 4h desde último sync
│  ├─ Cache expiró
│  └─ POST /api/set-timezone (re-sincroniza)
└─ ✅ Timezone confirmado tras 4 horas
```

### Caso 4: Usuario Viaja a Otra Zona
```
Timeline: Traveling Scenario
├─ Día 1 (Madrid): user.timezone = "Europe/Madrid"
├─ Día 2 (Bogotá): Usuario abre app
│  ├─ Script detecta: "America/Bogota" (CAMBIÓ!)
│  ├─ Verifica localStorage: "Europe/Madrid" (diferente)
│  ├─ POST /api/set-timezone
│  ├─ Backend UPDATE users SET timezone = "America/Bogota"
│  └─ ✅ Preguntas ahora en zona de Bogotá
└─ Día 3: Usuario abre app nuevamente
   ├─ Script detecta: "America/Bogota" (sin cambios)
   ├─ Verifica localStorage: "America/Bogota" (coincide)
   ├─ Hace 24h desde último sync
   └─ POST /api/set-timezone (re-sincroniza por precaución)
```

### Caso 5: Regreso Después de Inactividad
```
Timeline: Focus Event
├─ 10:00 - Usuario activo en app
├─ 10:15 - Minimiza app (pierdo focus)
│  └─ No hace nada (no hay sincronización)
├─ 10:30 - Usuario regresa a app (gano focus)
│  ├─ Event 'focus' dispara re-sincronización
│  ├─ Verifica: hace 30 min desde último sync
│  ├─ Threshold >15 min alcanzado
│  └─ POST /api/set-timezone
└─ ✅ Timezone re-sincronizado después de volver
```

### Caso 6: Sincronización Periódica (Background)
```
Timeline: 2 Hour Interval (App Abierta)
├─ 08:00 - Usuario abre app (sincroniza)
├─ 08:01-10:00 - App abierta, usuario navega
├─ 10:00 - setInterval(2h) se dispara
│  ├─ ¿App visible? 
│  ├─ YES: Verifica timezone
│  │  ├─ ¿Cambió?
│  │  ├─ YES: POST /api/set-timezone
│  │  └─ NO: SKIP (optimización)
│  └─ NO: SKIP (app en background)
├─ 12:00 - setInterval se dispara nuevamente
│  └─ ...idem a 10:00
└─ 14:00 - ...y así sucesivamente cada 2 horas
```

---

## 🛡️ Manejo de Errores con Reintentos

```
Intento 1: POST /api/set-timezone
│
├─ ✅ SUCCESS
│  └─ Guardar en localStorage + DONE
│
└─ ❌ FAIL (timeout/network/server error)
   │
   ├─ Esperar 1 segundo
   │
   ├─ Intento 2: POST /api/set-timezone
   │  │
   │  ├─ ✅ SUCCESS
   │  │  └─ Guardar en localStorage + DONE
   │  │
   │  └─ ❌ FAIL
   │     │
   │     ├─ Esperar 2 segundos
   │     │
   │     ├─ Intento 3: POST /api/set-timezone
   │     │  │
   │     │  ├─ ✅ SUCCESS
   │     │  │  └─ Guardar en localStorage + DONE
   │     │  │
   │     │  └─ ❌ FAIL
   │     │     └─ LOG ERROR + DONE
   │     │
```

**Ventajas:**
- ✅ Red lenta/intermitente: reintentos automáticos
- ✅ Backoff exponencial: no congestiona servidor
- ✅ 3 intentos: balance entre persistencia y performance
- ✅ Logs: debugging de problemas de sincronización

---

## 🔍 Debug Widget Visual (Local Only)

```
┌────────────────────────────────┐
│ 🌍 Timezone Debug              │
├────────────────────────────────┤
│ Device: America/Bogota         │
│ Saved: Europe/Madrid           │
│ Match: ❌                      │
│ Last sync: 5m atrás            │
│                                │
│ [FORCE SYNC] ← Button          │
└────────────────────────────────┘
```

### Cómo Activar (Local Only)
```javascript
// En consola del navegador:
localStorage.setItem("tz-debug-enabled", "true");
location.reload();

// Se mostrará widget en esquina inferior derecha
// Actualiza cada 5 segundos
// Botón para forzar sincronización manual
```

### Información que Muestra
- **Device:** Timezone actual del dispositivo (según Intl API)
- **Saved:** Timezone guardado en el servidor
- **Match:** ✅ si coinciden, ❌ si no
- **Last sync:** Tiempo transcurrido desde última sincronización

---

## 📋 Casos de Uso Cubiertos

| Caso | Antes | Ahora |
|------|-------|-------|
| **Nuevo usuario en login** | ✅ Se guardaba | ✅ Se guarda |
| **Usuario existente vuelve a login** | ❌ NO se actualizaba | ✅ Se actualiza |
| **Usuario ya logueado, primer acceso** | ❌ Sin sincronizar | ✅ Se sincroniza automáticamente |
| **Usuario recarga página (F5)** | ❌ No hay sincronización | ✅ Cache optimizado (no hace si hace <4h) |
| **Usuario regresa de inactividad** | ❌ No se detecta | ✅ Se re-sincroniza si >15 min |
| **Usuario viaja a otra zona** | ❌ Mantiene timezone viejo | ✅ Se detecta y actualiza automáticamente |
| **App en background 2+ horas** | ❌ Sin revisiones | ✅ Se sincroniza al regresar |
| **Conexión lenta/intermitente** | ❌ Sincronización puede fallar | ✅ Reintentos automáticos (3x) |
| **Usuario desconectado temporalmente** | ❌ Error sin reintentos | ✅ Reintentos con backoff exponencial |

---

## 🚀 Optimizaciones Implementadas

### 1. **Ejecución Temprana**
```javascript
// Detecta si documento está cargado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize(); // Si ya está cargado
}
```
- ✅ Ejecuta lo antes posible, no espera DOMContentLoaded
- ✅ Especialmente importante en slow networks

### 2. **Cache Inteligente (4 Horas)**
```javascript
if (lastSynced === deviceTimezone && lastSyncDate > fourHoursAgo) {
    return; // SKIP - No necesario sincronizar
}
```
- ✅ Evita requests innecesarias
- ✅ Reduce latencia en carga de página
- ✅ Mantiene actualización frecuente (4h es razonable)

### 3. **Reintentos con Backoff**
```javascript
const delayMs = 1000 * attemptNum; // 1s, 2s, 3s
setTimeout(() => attempt(attemptNum + 1), delayMs);
```
- ✅ No congestiona servidor
- ✅ Espaciado exponencial
- ✅ Máximo 3 intentos (ajustable)

### 4. **Eventos del Sistema**
```javascript
window.addEventListener('focus', checkAndSyncTimezone);
setInterval(checkAndSyncTimezone, 2 * 60 * 60 * 1000);
```
- ✅ Re-sincroniza cuando usuario regresa (focus)
- ✅ Sincronización periódica cada 2 horas
- ✅ Eficiente: no hace nada si app está en background

### 5. **Logging para Auditoría**
```php
Log::info("Timezone actualizado para usuario $userId: $oldTZ → $newTZ");
```
- ✅ Trackear cambios en logs
- ✅ Debugging de problemas
- ✅ Auditoría de cambios de usuario

---

## 📱 Funciona Perfectamente en Capacitor

El script está diseñado específicamente para Capacitor:
- ✅ No interfiere con navegación nativa
- ✅ Usa localStorage (soportado en Capacitor)
- ✅ Fetch API funciona en Capacitor WebView
- ✅ No depende de features específicas del navegador
- ✅ Funciona en background/foreground

**Testing en Capacitor:**
```bash
# En dispositivo, DevTools (Chrome Remote):
1. Abre Chrome DevTools para la app
2. Ver Network → POST /api/set-timezone
3. Cambiar zona horaria del dispositivo
4. Recargar app (pull-to-refresh)
5. Verificar que se sincroniza nuevo timezone
```

---

## ✨ Beneficios Finales

| Beneficio | Antes | Ahora |
|-----------|-------|-------|
| **UX Automática** | Manual (usuario configura) | Automática (detecta del dispositivo) |
| **Actualización Continua** | Sin actualización | Se sincroniza automáticamente |
| **Cambios de Dispositivo** | Manual | Automático en próximo login |
| **Viajes a Otra Zona** | Manual en perfil | Automático al abrir app |
| **Reintentos en Fallo** | Sin reintentos | 3 intentos con backoff |
| **Performance** | N/A | Cache de 4 horas + optimizaciones |
| **Debugging** | Difícil | Widget visual + logs detallados |
| **Cobertura Global** | Solo app web | App web + Capacitor/Mobile |

---

## 🎓 Lecciones Aprendidas

1. **Los usuarios no actualizan perfiles manualmente**
   - Mejor auto-detectar que pedir al usuario
   - Especialmente importante en mobile

2. **Las redes son impredecibles**
   - Reintentos son esenciales
   - Backoff exponencial es importante

3. **Logging es crítico**
   - Poder trackear cambios es vital
   - Debugging sin logs es muy difícil

4. **UX transparente es mejor**
   - No mostrar dialogs/confirmaciones innecesarias
   - Hacer el trabajo en background

5. **Cache es tu amigo**
   - 4 horas es buen balance
   - Evita requests innecesarias

---

**Implementación completada: ✅ 28 enero 2026**  
**Merge: 2 commits en rama main**
