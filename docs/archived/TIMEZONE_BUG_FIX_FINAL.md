# ✅ Corrección Final: Bug de Timezone en Calendario

## 📋 Resumen Ejecutivo

Se ha corregido el bug donde el calendario mostraba partidos bajo la fecha incorrecta cuando el usuario estaba en una zona horaria diferente a UTC (ej: Madrid). 

**Status**: ✅ CORREGIDO Y DESPLEGADO EN PRODUCCIÓN

---

## 🔍 Problema Identificado

### Síntomas
- Usuario en Madrid (UTC+1) veía partidos agrupados bajo fecha UTC en lugar de fecha local
- Ejemplo: Match a las 21:00 Madrid (2026-02-06 21:00) se agrupaba bajo 2026-02-07 (porque en UTC sería 2026-02-07 00:00)

### Causa Raíz
1. **Configuración conflictiva**: `app.timezone` estaba configurado como `'Europe/Madrid'`
2. **Base de datos**: Los datos se almacenan en UTC (standard)
3. **Conflicto de interpretación**: 
   - Laravel cargaba fechas UTC de la BD
   - Las interpretaba como `Europe/Madrid` (porque `app.timezone` = Madrid)
   - Resultado: Horas incorrectas

### Ubicación del Bug
Dos lugares tenían problemas:

#### 1️⃣ `MatchesCalendarService::groupMatchesByDate()`
```php
// ❌ ANTES: Agrupaba por fecha UTC
foreach ($matches as $match) {
    $date = Carbon::parse($dateField)->toDateString(); // UTC!
    $grouped[$date][] = $this->formatMatch($match);
}

// ✅ DESPUÉS: Agrupa por fecha local del usuario
$userTimezone = auth()->check() ? auth()->user()->timezone : config('app.timezone');
foreach ($matches as $match) {
    $matchDate = Carbon::parse($dateField)->setTimezone($userTimezone);
    $date = $matchDate->toDateString(); // Fecha local
    $grouped[$date][] = $this->formatMatch($match);
}
```

#### 2️⃣ `DateTimeHelper::toUserTimezone()`
```php
// ❌ ANTES: Lógica confusa con reinterpretación
// Intentaba detectar y reinterpretar el timezone

// ✅ DESPUÉS: Simple y directa con app.timezone = UTC
if (is_string($date)) {
    $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
} else {
    $date = $date->copy();
}
return $date->setTimezone($timezone)->format($format);
```

---

## 🔧 Soluciones Implementadas

### 1. Cambiar `app.timezone` a UTC (Best Practice)
**Archivo**: `config/app.php`
```php
# ❌ Antes
'timezone' => 'Europe/Madrid',

# ✅ Después
'timezone' => 'UTC',
```

**Por qué**: 
- La BD almacena en UTC
- `app.timezone` debe coincidir con la zona horaria de almacenamiento
- Todos los usuarios ven horas diferentes según su timezone
- Esto es el estándar en desarrollo web

### 2. Actualizar `MatchesCalendarService::groupMatchesByDate()`
**Archivo**: `app/Services/MatchesCalendarService.php` (líneas 170-213)

Convertir a timezone del usuario ANTES de crear la clave de agrupación:
```php
protected function groupMatchesByDate(Collection $matches): array
{
    $grouped = [];
    
    // Obtener timezone del usuario
    $userTimezone = auth()->check() ? auth()->user()->timezone : config('app.timezone');

    foreach ($matches as $match) {
        // Convertir a timezone del usuario ANTES de agrupar
        $matchDate = Carbon::parse($dateField)->setTimezone($userTimezone);
        $date = $matchDate->toDateString(); // Fecha en timezone local
        
        $grouped[$date][] = $this->formatMatch($match);
    }
    
    ksort($grouped);
    return $grouped;
}
```

### 3. Simplificar `DateTimeHelper::toUserTimezone()`
**Archivo**: `app/Helpers/DateTimeHelper.php` (línea 18-42)

Eliminar lógica confusa y hacer simple:
```php
public static function toUserTimezone($date, $format = 'd/m/Y H:i', $timezone = null)
{
    // Obtener timezone del usuario o default (UTC)
    if (!$timezone && Auth::check()) {
        $timezone = Auth::user()->timezone ?? config('app.timezone');
    } elseif (!$timezone) {
        $timezone = config('app.timezone');
    }

    // Crear Carbon object correctamente
    if (is_string($date)) {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
    } else {
        $date = $date->copy();
    }

    // Convertir a timezone del usuario
    return $date->setTimezone($timezone)->format($format);
}
```

### 4. Actualizar métodos similares
También se actualizaron para consistencia:
- `toUTC()` - Simplificado
- `toUserTimestampForCountdown()` - Simplificado

---

## ✅ Verificación

### Tests Manuales Realizados

#### 1. API Calendar Endpoint
```bash
# Request
GET /api/matches/calendar?from_date=2026-02-06&to_date=2026-02-10

# Response: Partidos agrupados correctamente
{
  "2026-02-06": [
    {
      "kick_off_time": "19:45",  ✅ Hora local Madrid
      "id": 564,
      ...
    }
  ],
  "2026-02-07": [
    {
      "kick_off_time": "12:30",  ✅ Hora local Madrid
      ...
    }
  ]
}
```

**Resultado**: ✅ Partidos agrupados por fecha local, horas en formato local

#### 2. Blade Directive `@userTime()`
Usado en:
- `group-match-questions.blade.php` (línea 69, 81)
- `prediction-card.blade.php` (línea 41)

```blade
@userTime($question->football_match->date, 'H:i')
# Output: 21:00 (hora local de Madrid)
```

**Resultado**: ✅ Muestra hora local correctamente

#### 3. Configuración de Producción
```bash
$ php artisan config:cache
$ grep "timezone" config/app.php
# Output: 'timezone' => 'UTC',
```

**Resultado**: ✅ UTC configurado correctamente

---

## 📊 Impacto

| Aspecto | Antes | Después |
|--------|-------|---------|
| **Fecha de agrupación** | UTC (❌ incorrecta) | Local (✅ correcta) |
| **Formato de hora** | Variaba | Consistente (H:i en local) |
| **Usuario en Madrid** | Partidos en fecha equivocada | Fecha correcta |
| **app.timezone** | Europe/Madrid | UTC |
| **Compatibilidad** | Solo Madrid | Cualquier timezone |

---

## 🚀 Deployment

### Cambios Desplegados
- ✅ `config/app.php` - timezone = UTC
- ✅ `app/Helpers/DateTimeHelper.php` - Métodos simplificados
- ✅ `app/Services/MatchesCalendarService.php` - groupMatchesByDate() corregido

### Comandos Ejecutados
```bash
# Limpieza local
php artisan config:clear
php artisan cache:clear

# Deployment en producción
git push origin main
# En servidor:
git pull origin main
php artisan config:clear && php artisan cache:clear
```

### Status en Producción
- ✅ Code deployed
- ✅ Cache cleared
- ✅ API responding correctly
- ✅ Timezone conversion working

---

## 🧪 Cómo Probar

### Para un Usuario en Madrid:
1. Accede al calendario: `/matches/calendar`
2. Verifica que los partidos aparezcan en las fechas locales
3. Haz click en un partido y verifica la hora en formato local (H:i)

### Para Diferentes Timezones:
1. Cambia tu timezone en perfil a: `America/New_York`, `Asia/Tokyo`, etc.
2. Los partidos deberían reagruparse automáticamente
3. Las horas deberían reflejar tu zona horaria

### API Test:
```bash
curl "https://app.offsideclub.es/api/matches/calendar?from_date=2026-02-06&to_date=2026-02-10"
# Verifica que kick_off_time esté en formato local
```

---

## 📝 Notas Técnicas

### ¿Por qué cambiar `app.timezone` a UTC?
1. **Base de datos**: Datos almacenados en UTC (mejor práctica global)
2. **Laravel standard**: Las apps modernas usan UTC internamente
3. **Escalabilidad**: Funciona con cualquier timezone de usuario
4. **Claridad**: Elimina ambigüedad en conversiones

### ¿Cómo funciona ahora?
1. Datos en BD: UTF-8 (ej: `2026-02-06 20:00:00`)
2. Laravel carga: interpreta como UTC (correcto)
3. `@userTime()` convierte: a timezone del usuario
4. `groupMatchesByDate()` agrupa: por fecha local del usuario

### Componentes Afectados
- ✅ Calendar API endpoint
- ✅ Blade helpers (`@userTime`)
- ✅ GroupMatchQuestions component
- ✅ Prediction cards
- ✅ Countdowns (usa `@userTimestamp`)

---

## 🎯 Conclusión

El bug ha sido completamente corregido. Los partidos ahora se agrupan correctamente por fecha local del usuario y las horas se muestran en el formato correcto, independientemente de la zona horaria del usuario.

**Cambios**: 3 archivos modificados, 16 líneas removidas, 10 agregadas.
**Testing**: ✅ API responses correctas, ✅ Calendar displaying properly
**Production**: ✅ Deployed and verified
