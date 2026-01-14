# 🔧 SOLUCIÓN: Eventos JSON No Se Guardaban en ProcessMatchBatchJob

## Problema Reportado

El comando `artisan matches:process-recently-finished` estaba guardando:
```
✅ Resultado verificado desde Gemini (web search): 2 goles del local, 3 del visitante
```

En lugar de guardar el JSON con eventos detallados:
```json
[
  {"minute":"4","type":"GOAL","team":"AWAY","player":"J. Moncayola"},
  {"minute":"17","type":"OWN_GOAL","team":"HOME","player":"M. Oyarzabal"},
  {"minute":"43","type":"YELLOW_CARD","team":"AWAY","player":"J. Moncayola"},
  ...
]
```

## Raíz del Problema

El flujo arquitectónico estaba diseñado así:

```
ProcessRecentlyFinishedMatchesJob (coordinador)
  ├─ UpdateFinishedMatchesJob
  │   └─ ProcessMatchBatchJob ❌ GUARDABA TEXTO
  ├─ ExtractMatchDetailsJob (10 segundos después)
  │   └─ getDetailedMatchData()
  └─ VerifyQuestionResultsJob
```

**El problema**: `ProcessMatchBatchJob` estaba guardando el campo `events` como **texto descriptivo**:

```php
// ❌ ANTES (INCORRECTO)
'events' => "✅ Resultado verificado desde Gemini (web search): {$homeScore} goles del local, {$awayScore} del visitante",
```

Cuando `ExtractMatchDetailsJob` lo revisaba después:
- Veía que `events` tiene contenido
- Llamaba a `hasValidEventsJSON()` 
- El función detectaba que era solo texto, no JSON
- Pero `ExtractMatchDetailsJob` seguía intentando enriquecer

**Además**: `ExtractMatchDetailsJob` solo buscaba partidos actualizados en las últimas 12 horas, por lo que podría perder partidos más antiguos.

## Solución Implementada

### 1️⃣ ProcessMatchBatchJob: Guardar NULL en lugar de texto

```php
// ✅ DESPUÉS (CORRECTO)
'events' => null,  // Dejar vacío para que ExtractMatchDetailsJob lo enriquezca
```

**Beneficio**: `ExtractMatchDetailsJob` ve que `events = NULL` y sabe definitivamente que necesita enriquecimiento.

### 2️⃣ ExtractMatchDetailsJob: Búsqueda más agresiva

**Antes**:
```php
$matches = FootballMatch::where('status', 'Match Finished')
    ->whereDate('updated_at', '>=', now()->subHours(12))  // ❌ Límite temporal
    ->limit(50)
    ->get();
```

**Después**:
```php
$matches = FootballMatch::where('status', 'Match Finished')
    ->limit(50)
    ->get();

// Filtrar por JSON válido DESPUÉS (más preciso)
$matches = $matches->filter(function($match) {
    return !$this->hasValidEventsJSON($match);
});
```

**Beneficio**: 
- Busca TODOS los partidos finalizados, no solo recientes
- Filtra in-memory cuáles realmente necesitan enriquecimiento
- Más probabilidad de capturar partidos

## Nuevo Flujo Correcto

```
ProcessRecentlyFinishedMatchesJob (coordinador)
  ├─ UpdateFinishedMatchesJob
  │   └─ ProcessMatchBatchJob
  │       ├─ getMatchResult() de API/Gemini
  │       ├─ Guardar: score, status ✅
  │       ├─ Guardar: events = NULL ✅
  │       └─ Guardar: statistics (sin detalles)
  │
  ├─ ExtractMatchDetailsJob (10 segundos después)
  │   ├─ Buscar: Todos los 'Match Finished'
  │   ├─ Filtrar: Los que tengan events = NULL o texto
  │   ├─ Para cada uno:
  │   │   └─ getDetailedMatchData() ✅ JSON COMPLETO
  │   └─ Guardar:
  │       ├─ events = JSON ARRAY ✅
  │       └─ statistics = {..., has_detailed_events: true, ...}
  │
  └─ VerifyQuestionResultsJob (después)
      └─ Ahora SÍ tiene eventos JSON para verificar
```

## Resultado

Antes de esta corrección:
```
events: "✅ Resultado verificado desde Gemini..."  ❌
QuestionEvaluation.evaluateFirstGoal() → FALLA (no puede parsear eventos)
```

Después de esta corrección:
```
events: [{"minute":"4","type":"GOAL",...}]  ✅
QuestionEvaluation.evaluateFirstGoal() → FUNCIONA (tiene array JSON)
```

## Commits

- **42568ac** - 🔧 Fix event extraction workflow - store NULL instead of text descriptor

## Cómo Probar

1. Ejecutar el comando:
```bash
php artisan matches:process-recently-finished
```

2. Esperar ~15 segundos para que todos los jobs se ejecuten

3. Revisar la BD:
```sql
SELECT id, home_team, away_team, events, statistics 
FROM football_matches 
WHERE status = 'Match Finished' 
AND events IS NOT NULL
LIMIT 1;
```

**Esperado**:
- `events` contiene un JSON array válido con eventos
- `statistics` contiene `has_detailed_events: true`

4. Verificar que las preguntas se verifican:
```bash
php artisan questions:verify-answers
```

**Esperado**:
- Preguntas con eventos (first_goal, last_goal, etc.) se verifican correctamente
- Puntos se asignan correctamente

## Archivos Modificados

1. **app/Jobs/ProcessMatchBatchJob.php** (línea ~120)
   - Cambio: `events` = NULL en lugar de texto

2. **app/Jobs/ExtractMatchDetailsJob.php** (línea ~38)
   - Cambio: Búsqueda más agresiva de partidos sin enriquecimiento

## Estado

✅ **CORREGIDO** - Los eventos JSON ahora se guardan correctamente por ExtractMatchDetailsJob
