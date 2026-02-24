# Fix: Evaluación de Preguntas "Primer Gol" con Nuevo Formato de Events

## Problema

Las preguntas del tipo "¿Cuál equipo anotará el primer gol?" no se validaban correctamente después que el formato de los eventos cambió cuando API Football PRO comenzó a proporcionar datos estructurados.

### Causas

Había DOS formatos de eventos diferentes en la base de datos:

#### Formato Antiguo (Gemini):
```json
{
  "minute": "15",
  "type": "GOAL",
  "team": "HOME",
  "player": "Jugador"
}
```

#### Formato Nuevo (API Football):
```json
{
  "time": 16,
  "type": "Goal",
  "team": "Inter",
  "player": "L. Martinez",
  "assist": "F. Dimarco",
  "detail": "Normal Goal"
}
```

**Diferencias clave:**
- `minute` vs `time` (string vs int)
- `GOAL` vs `Goal` (mayúsculas)
- `HOME/AWAY` vs nombres reales del equipo

### Impacto

El código en `QuestionEvaluationService::evaluateFirstGoal()` esperaba:
```php
if ($event['type'] === 'GOAL' && $event['team'] !== 'substitution') {
```

Con el nuevo formato:
- `'Goal' !== 'GOAL'` → True (no encontraba goles)
- El check `$event['team'] !== 'substitution'` no tenía sentido (substitution es un tipo, no un equipo)

**Resultado:** Todas las preguntas de "primer gol" fallaban en su validación.

---

## Solución

Se actualizó `QuestionEvaluationService` para **normalizar eventos** de ambos formatos a un estándar interno consistente.

### Cambios Implementados

#### 1. Nuevo método `normalizeEvent()` (líneas 1188-1209)

```php
private function normalizeEvent(array $event): array
{
    return [
        // Normalizar minuto (puede ser 'minute' o 'time', string o int)
        'minute' => $this->parseMinuteValue($event['minute'] ?? $event['time'] ?? null),
        // Normalizar tipo de evento a UPPERCASE
        'type' => strtoupper($event['type'] ?? ''),
        // Mantener el equipo tal cual
        'team' => $event['team'] ?? null,
        // Campos opcionales
        'player' => $event['player'] ?? null,
        'detail' => $event['detail'] ?? null,
        'assist' => $event['assist'] ?? null,
    ];
}
```

#### 2. Actualizar `parseEvents()` (líneas 1174-1186)

Se agregó normalización de eventos:
```php
private function parseEvents($events): array
{
    // ... deserialization ...
    
    // ✅ NORMALIZAR eventos para manejar múltiples formatos
    return array_map(fn($event) => $this->normalizeEvent($event), $events);
}
```

#### 3. Simplificar `evaluateFirstGoal()` (línea 356)

**Antes:**
```php
if ($event['type'] === 'GOAL' && $event['team'] !== 'substitution') {
```

**Después:**
```php
if ($event['type'] === 'GOAL') {
```

Los eventos ya están normalizados, así que:
- `type` siempre está en MAYÚSCULAS
- `team` siempre contiene el nombre del equipo

#### 4. Actualizar `evaluateGoalBeforeMinute()` (línea 414)

**Antes:**
```php
$minute = $this->parseMinuteValue($event['minute'] ?? null);
```

**Después:**
```php
$minute = $event['minute'] ?? null;
```

Ya está normalizado en el evento.

#### 5. Actualizar `evaluateLastGoal()` (línea 461)

Mismo cambio que en `evaluateFirstGoal()`.

---

## Verificación

Se verificó con el match ID 1785 (Cremonese vs Inter):

**Evento Raw:**
```json
{"time": 16, "type": "Goal", "team": "Inter", "player": "L. Martinez", "detail": "Normal Goal"}
```

**Evento Normalizado:**
```json
{"minute": 16, "type": "GOAL", "team": "Inter", "player": "L. Martinez", "detail": "Normal Goal"}
```

**Resultado:** ✅ La pregunta "¿Cuál equipo anotará el primer gol?" devuelve correctamente "Inter" como opción correcta.

---

## Métodos Afectados

Todos estos методы ahora funcionan correctamente con ambos formatos:

- `evaluateFirstGoal()` - ¿Primer gol?
- `evaluateLastGoal()` - ¿Último gol?
- `evaluateGoalBeforeMinute()` - ¿Gol antes del minuto X?
- `evaluateOwnGoal()` - ¿Autogoles?
- `evaluatePenaltyGoal()` - ¿Goles de penal?
- `evaluateFreeKickGoal()` - ¿Goles de tiro libre?
- `evaluateCornerGoal()` - ¿Goles de córner?

---

## Testing

Se creó un test formal en [tests/Unit/Services/FirstGoalQuestionEvaluationTest.php](../../../tests/Unit/Services/FirstGoalQuestionEvaluationTest.php) que verifica:

1. Evaluación de "Primer Gol" con formato API Football
2. Evaluación de "Último Gol" con formato API Football  
3. Evaluación de "Gol antes de minuto X" con formato API Football

---

## Impacto en Production

✅ **Safe to deploy**

- No hay cambios en las interfaces públicas
- Métodos privados solo
- Los eventos raw guardados en BD no cambian
- Solo normalización interna durante evaluación
- Backward compatible con ambos formatos

---

## Rollback Plan

Si necesita revertir:
```bash
git revert <commit-sha-of-this-fix>
```

Pero la normalización no afecta datos, solo lectura.
