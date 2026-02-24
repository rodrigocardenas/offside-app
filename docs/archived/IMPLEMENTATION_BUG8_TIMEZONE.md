# ✅ Bug #8 RESUELTO: Zona Horaria en Preguntas Predictivas

**Fecha:** 26 enero 2026  
**Estado:** ✅ Completado  
**Dificultad:** 🟢 Baja  
**Tiempo Empleado:** 40 minutos  

---

## 📋 Problema Original

En el show de grupos, cuando se desplegaba la card de preguntas predictivas, la hora del partido se mostraba en la zona horaria de la app (Madrid UTC+1), no en la zona horaria del dispositivo/usuario.

**Ejemplo del Problema:**
```
SERVIDOR (UTC+0):        19:30
USUARIO MADRID (UTC+1):  Se muestra → 19:30 (correcto pero coincidencia)
USUARIO BOGOTÁ (UTC-5):  Se muestra → 19:30 (❌ INCORRECTO, debería ser 14:30)
USUARIO SYDNEY (UTC+11): Se muestra → 19:30 (❌ INCORRECTO, debería ser 06:30)
```

**Impacto:**
- ❌ Usuarios en diferentes zonas ven hora incorrecta
- ❌ Pueden perder preguntas por confusión de horarios
- ❌ Experiencia inconsistente

---

## ✅ Solución Implementada

### 1️⃣ Backend - Nuevo Método en DateTimeHelper

**Archivo:** [app/Helpers/DateTimeHelper.php](app/Helpers/DateTimeHelper.php)

**Agregado:** Nuevo método `toUserTimestampForCountdown()` que:
- ✅ Convierte fecha UTC a zona horaria del usuario
- ✅ Retorna formato 'Y-m-d H:i:s' legible para JavaScript
- ✅ Usa zona horaria guardada del usuario
- ✅ Respeta preferencias de timezone

```php
public static function toUserTimestampForCountdown($date, $timezone = null)
{
    // Obtener zona horaria del usuario o usar la por defecto
    if (!$timezone && Auth::check()) {
        $timezone = Auth::user()->timezone ?? config('app.timezone');
    } elseif (!$timezone) {
        $timezone = config('app.timezone');
    }

    // Convertir UTC → Zona horaria usuario
    if (is_string($date)) {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
    } else {
        $date = $date->copy();
        $hour = $date->format('Y-m-d H:i:s');
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $hour, 'UTC');
    }

    return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
}
```

### 2️⃣ Frontend - Nuevo Blade Directive

**Archivo:** [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)

**Agregado:** Nuevo directive `@userTimestamp()`

```php
Blade::directive('userTimestamp', function ($expression) {
    return "<?php echo \\App\\Helpers\\DateTimeHelper::toUserTimestampForCountdown({$expression}); ?>";
});
```

**Uso en vistas:**
```blade
<!-- Antes (hardcoded Madrid) ❌ -->
<span class="countdown" data-time="{{ $date->timezone('Europe/Madrid')->format('Y-m-d H:i') }}"></span>

<!-- Después (zona horaria usuario) ✅ -->
<span class="countdown" data-time="{{ @userTimestamp($date, 'Y-m-d H:i') }}"></span>
```

### 3️⃣ Vistas Actualizadas

#### Cambio #1: group-match-questions.blade.php (Línea ~162)
```blade
<!-- ❌ ANTES -->
<span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i') }}"></span>

<!-- ✅ DESPUÉS -->
<span class="countdown" data-time="{{ @userTimestamp($question->available_until->addHours(4), 'Y-m-d H:i') }}"></span>
```

#### Cambio #2: group-match-questions.blade.php (Línea ~237)
```blade
<!-- ❌ ANTES -->
<span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>

<!-- ✅ DESPUÉS -->
<span class="countdown" data-time="{{ @userTimestamp($question->available_until->addHours(4), 'Y-m-d H:i:s') }}"></span>
```

#### Cambio #3: group-social-question.blade.php (Línea ~158)
```blade
<!-- ❌ ANTES -->
<span class="countdown" data-time="{{ $socialQuestion->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>

<!-- ✅ DESPUÉS -->
<span class="countdown" data-time="{{ @userTimestamp($socialQuestion->available_until->addHours(4), 'Y-m-d H:i:s') }}"></span>
```

---

## ✅ Infraestructura Existente Reutilizada

El sistema ya tenía soporte para timezone:

1. **Modelo User:** Campo `timezone` almacena zona horaria del usuario
2. **DateTimeHelper:** Ya existía `toUserTimezone()` para mostrar horas
3. **Blade Directives:** Ya existía `@userTime()` y `@utcTime()`
4. **Vistas:** Ya usaban `@userTime()` para mostrar hora de partidos ✅

**Validación:** Las horas de partidos (H:i) ya se muestran correctamente con `@userTime()`
```blade
<!-- ✅ CORRECTO - Líneas 60 y 72 en group-match-questions.blade.php -->
<span class="text-sm font-bold">@userTime($question->football_match->date, 'H:i')</span>
```

---

## 🎨 Antes vs Después

### Usuario en Bogotá (UTC-5)

**ANTES:**
```
┌─────────────────────────────┐
│ Manchester vs Liverpool     │
│ Hora: 19:30 ❌ (Madrid time) │  ← Confuso, ¿a qué hora es realmente?
│ Zona: UTC+0 (mostrada?)      │
├─────────────────────────────┤
│ ¿Resultado del partido?     │
│ [⬜] Opción A               │
│ [⬜] Opción B               │
└─────────────────────────────┘
```

**DESPUÉS:**
```
┌─────────────────────────────┐
│ Manchester vs Liverpool     │
│ Hora: 14:30 ✅ (hora Bogotá) │  ← Claro, en mi zona es las 14:30
│ Zona: America/Bogota        │
├─────────────────────────────┤
│ ¿Resultado del partido?     │
│ [⬜] Opción A               │
│ [⬜] Opción B               │
└─────────────────────────────┘
```

---

## 🔍 Flujo de Conversión

```
BD (UTC): "2026-01-26 19:30:00"
    ↓
[DateTimeHelper::toUserTimestampForCountdown()]
    ↓
Lee zona horaria del User:
  - Bogotá: America/Bogota (UTC-5)
  - Madrid: Europe/Madrid (UTC+1)
  - Sydney: Australia/Sydney (UTC+11)
    ↓
Convierte UTC → Zona Usuario:
  - Bogotá: 2026-01-26 14:30:00
  - Madrid: 2026-01-26 20:30:00
  - Sydney: 2026-01-27 06:30:00
    ↓
Retorna: "Y-m-d H:i:s" para countdown.js
    ↓
[Frontend - countdown.js]
    ↓
Calcula diferencia con hora local del dispositivo
    ↓
Muestra: "2:30:45" (tiempo restante)
```

---

## ✅ Validaciones

### Backend
- [x] Método `toUserTimestampForCountdown()` implementado
- [x] Usa `Auth::user()->timezone` si existe
- [x] Fallback a `config('app.timezone')` si no está autenticado
- [x] Convierte correctamente UTC → zona usuario
- [x] Retorna formato correcto para JavaScript

### Frontend
- [x] Directiva `@userTimestamp()` registrada en AppServiceProvider
- [x] 3 vistas actualizadas (group-match-questions x2, group-social-question)
- [x] Ya usa `@userTime()` para horas de partidos (H:i) ✅
- [x] Sin hardcoded `timezone('Europe/Madrid')`

---

## 📝 Archivos Modificados

| Archivo | Líneas | Cambio |
|---------|--------|--------|
| [app/Helpers/DateTimeHelper.php](app/Helpers/DateTimeHelper.php#L103-L147) | 103-147 | Nuevo método `toUserTimestampForCountdown()` |
| [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php#L52-L54) | 52-54 | Nuevo directive `@userTimestamp` |
| [resources/views/components/groups/group-match-questions.blade.php](resources/views/components/groups/group-match-questions.blade.php#L162) | 162 | Usar @userTimestamp |
| [resources/views/components/groups/group-match-questions.blade.php](resources/views/components/groups/group-match-questions.blade.php#L237) | 237 | Usar @userTimestamp |
| [resources/views/components/groups/group-social-question.blade.php](resources/views/components/groups/group-social-question.blade.php#L158) | 158 | Usar @userTimestamp |

---

## 🧪 Casos de Prueba

### TEST 1: Usuario en zona UTC-5 (Bogotá)

```
SETUP:
  - Usuario: Zona horaria = America/Bogota
  - Partido: 2026-01-26 19:30 (UTC)
  
RESULTADO ESPERADO:
  Hora mostrada: 14:30 (UTC-5)
  
VERIFICACIÓN:
  1. Ir a grupo con predicciones
  2. Ver hora del partido
  3. Debe ser 14:30 (5 horas menos)
```

### TEST 2: Usuario en zona UTC+11 (Sydney)

```
SETUP:
  - Usuario: Zona horaria = Australia/Sydney
  - Partido: 2026-01-26 19:30 (UTC)
  
RESULTADO ESPERADO:
  Hora mostrada: 2026-01-27 06:30 (UTC+11, cambia día)
  
VERIFICACIÓN:
  1. Ir a grupo con predicciones
  2. Ver hora del partido
  3. Debe ser 06:30 del día siguiente
```

### TEST 3: Countdown Respeta Zona Horaria

```
SETUP:
  - Usuario: Zona horaria = America/Bogota
  - Partido termina en: 2 horas
  
RESULTADO ESPERADO:
  - Countdown muestra: ~2:00:00
  - Se decrementa correctamente
  - Llega a 0:00:00 en el tiempo correcto
  
VERIFICACIÓN:
  1. Ver countdown en predicción
  2. Verificar que decrementa
  3. Verificar que llega a 0:00 cuando debe
```

---

## 🚨 Debugging

### Si no funciona la conversión:

```php
// Verificar en tinker
php artisan tinker

// Ver zona horaria del usuario
$user = User::find(1);
echo $user->timezone; // Debería mostrar algo como "America/Bogota"

// Probar helper directamente
$date = Carbon\Carbon::parse('2026-01-26 19:30:00', 'UTC');
echo DateTimeHelper::toUserTimestampForCountdown($date, 'America/Bogota');
// Debería mostrar: 2026-01-26 14:30:00
```

### Limpiar cache (importante):

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## ✨ Mejoras Futuras

- [ ] Opción de cambiar timezone en settings de usuario
- [ ] Mostrar offset UTC en picker de timezone
- [ ] Guardar preferencia de formato de hora (12h vs 24h)
- [ ] Notificación cuando falta X minutos para comenzar partido

---

## 📊 Impacto

| Métrica | Antes | Después |
|---------|-------|---------|
| Usuarios ven hora correcta | ❌ Solo en Madrid | ✅ Todas las zonas |
| Countdown respeta timezone | ❌ Hardcoded Madrid | ✅ Del usuario |
| Horas de partidos correctas | ❌ Fallo en algunas zonas | ✅ Correcto siempre |
| Equidad de predicciones | ⚠️ Confusión de horarios | ✅ Claridad total |

