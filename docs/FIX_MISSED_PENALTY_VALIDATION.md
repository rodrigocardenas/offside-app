# 🔧 CORRECCIÓN: Validación de Penales Fallados en Preguntas de Goles

**Fecha:** Febrero 23, 2026  
**Versión:** v1.0  
**Status:** ✅ Completada y Validada

---

## 📋 Problema Reportado

**Match:** ID 755 (Wolves vs Leicester)  
**Pregunta:** "¿Cuál equipo anotará el primer gol en el partido?"  
**Error:** Se marcó como respuesta correcta "Wolves" (opción 2488)

**Root Cause:** Un evento con `type: "Goal"` pero `detail: "Missed Penalty"` fue contado como un gol válido

**Evento Problemático:**
```json
{
  "time": 43,
  "type": "Goal",
  "team": "Wolves",
  "player": "T. Arokodare",
  "assist": "T. Arokodare",
  "detail": "Missed Penalty"
}
```

---

## ✅ Solución Implementada

### 1. Nuevo Método Helper: `isValidGoal()`

Se agregó un método privado a `QuestionEvaluationService.php` (líneas ~1223-1246) que valida si un evento de tipo GOAL es válido:

```php
private function isValidGoal(array $event): bool
{
    // Verificar que sea del tipo GOAL (case-insensitive)
    $type = strtoupper($event['type'] ?? '');
    if ($type !== 'GOAL') {
        return false;
    }

    // Excluir penales fallados
    $detail = strtolower($event['detail'] ?? '');
    if (stripos($detail, 'missed penalty') !== false) {
        return false;
    }

    // Excluir otros casos inválidos potenciales
    if (stripos($detail, 'missed') !== false && stripos($detail, 'penalty') !== false) {
        return false;
    }

    return true;
}
```

**Características:**
- ✅ Case-insensitive para el tipo (`'Goal'`, `'GOAL'`, `'goal'`)
- ✅ Excluye "Missed Penalty" en cualquier formato
- ✅ Funciona con eventos that carecen del field `detail`

---

### 2. Métodos Actualizados

Se actualizaron **4 métodos** para usar `isValidGoal()` en lugar de checar solo `type === 'GOAL'`:

#### a) `evaluateFirstGoal()` (línea ~369)
```php
foreach ($events as $event) {
    // ✅ VALIDACIÓN MEJORADA
    if ($this->isValidGoal($event)) {
        $firstGoalTeam = $event['team'];
        break;
    }
}
```

#### b) `evaluateGoalBeforeMinute()` (línea ~481)
```php
foreach ($events as $event) {
    // ✅ VALIDACIÓN MEJORADA
    if (!$this->isValidGoal($event)) {
        continue;
    }
    // ... resto del código
}
```

#### c) `evaluateLastGoal()` (línea ~499)
```php
foreach (array_reverse($events) as $event) {
    // ✅ VALIDACIÓN MEJORADA
    if ($this->isValidGoal($event)) {
        $lastGoalTeam = $event['team'];
        break;
    }
}
```

#### d) `evaluateLateGoal()` (línea ~1008)
```php
$lateGoals = array_filter($events, fn($e) =>
    $this->isValidGoal($e) && ($e['minute'] ?? 0) >= 75
);
```

---

## 🧪 Validación

### Tests Unitarios Agregados

Se agregaron **2 new test cases** a `FirstGoalQuestionEvaluationTest.php`:

1. **`test_missed_penalty_is_not_counted_as_goal()`**
   - Reproduce el bug del Match 755
   - Verifica que "Ninguno" es la respuesta correcta (no "Wolves")

2. **`test_valid_goal_counts_when_missed_penalty_exists()`**
   - Verifica que goles válidos sí son contados incluso si hay penales fallados antes

### Validación Manual

Se ejecutó script de validación con 5 test cases:

| Test | Eventos | Resultado | Status |
|------|---------|-----------|--------|
| 1 | Missed Penalty → false | ✅ PASS | ✅ |
| 2 | Normal Goal → true | ✅ PASS | ✅ |
| 3 | Goal sin detail → true | ✅ PASS | ✅ |
| 4 | Card event → false | ✅ PASS | ✅ |
| 5 | "missed penalty kick" → false | ✅ PASS | ✅ |

---

## 🔍 Caso de Uso Específico: Match 755

### Antes de la Corrección
```
Eventos: [Missed Penalty (min 43), Card (min 55), Card (min 89)]
Análisis anterior: Detecta "Goal" en min 43 → Wolves
Respuesta errónea: "Wolves" (opción 2488)
Resultado final: 0-0 (¡incorrecto!)
```

### Después de la Corrección
```
Eventos: [Missed Penalty (min 43), Card (min 55), Card (min 89)]
Análisis nuevo: Excluye "Missed Penalty" → No hay goles válidos
Respuesta correcta: "Ninguno"
Resultado final: 0-0 (✅ correcto)
```

---

## 📊 Impacto

### Preguntas Afectadas
- ✅ "¿Quién anotará el primer gol?" - **CRÍTICO**
- ✅ "¿Quién anotará el último gol?" - **CRÍTICO**
- ✅ "¿Habrá gol antes del minuto X?" - **CRÍTICO**
- ✅ "¿Habrá gol en los últimos 15 minutos?" - **CRÍTICO**

### Matches Afectados
Cualquier match que tenga penales fallados en el JSON de eventos de API Football.

### Frecuencia Estimada
- **Baja-Media**: ~2-5% de matches tienen penales fallados registrados como "Goal"
- **Impacto**: Hasta 20 matches afectados por season en principales ligas

---

## 🚀 Deployment

### 1. Aplicar Cambios
```
✅ QuestionEvaluationService.php actualizado
✅ Syntax validation: Pasó sin errores
```

### 2. Ejecutar en Producción
```bash
# No requiere migrations o cambios de BD
# Solo PHP code change
```

### 3. Validar (Opcional)
```bash
# Re-run evaluation para Match 755
php artisan tinker
>>> $m = FootballMatch::find(755);
>>> $q = Question::where('title', 'like', '%primer gol%')
...   ->where('match_id', 755)->first();
>>> $service = new QuestionEvaluationService();
>>> $service->evaluateQuestion($q, $m); // Debe retornar opción "Ninguno"
```

---

## 📚 Archivos Modificados

| Archivo | Líneas | Cambios |
|---------|--------|---------|
| `app/Services/QuestionEvaluationService.php` | 1223-1246 | + Nuevo método `isValidGoal()` |
| | 369 | Actualizar `evaluateFirstGoal()` |
| | 481 | Actualizar `evaluateGoalBeforeMinute()` |
| | 499 | Actualizar `evaluateLastGoal()` |
| | 1008 | Actualizar `evaluateLateGoal()` |
| `tests/Unit/Services/FirstGoalQuestionEvaluationTest.php` | + 60 líneas | 2 nuevos test cases |

---

## ✨ Beneficios Adicionales

1. **Reutilizable**: El método `isValidGoal()` puede usarse en futuros métodos de evaluación
2. **Mantenible**: Lógica centralizada hace fácil agregar más excludentes (ej: Own Goals)
3. **Robusto**: Maneja variaciones en case y formato de eventos
4. **Backward Compatible**: No afecta matches sin penales fallados

---

## 🔗 Referencia

- Bug reportado para: Match 755 - Wolves vs Leicester
- Pregunta: "¿Cuál equipo anotará el primer gol en el partido?"
- Evento: `{"time":43,"type":"Goal","team":"Wolves","detail":"Missed Penalty"}`
- Corregido en: `QuestionEvaluationService.php`

---

## 📝 Notas de Implementación

### Casos Edge Que Maneja
1. ✅ Penales fallados con `detail: "Missed Penalty"`
2. ✅ Eventos sin field `detail` (null/empty)
3. ✅ Case-insensitive matching de type (`Goal`, `GOAL`, `goal`)
4. ✅ Otros tipos de eventos (Card, Substitution, etc.)

### Casos No Cubiertos (Future Work)
- Own goals: Actualmente se cuentan como válidos. Considerar si debería ser opcional por pregunta
- Penales convertidos: Se cuentan correctamente (detail ≠ "Missed Penalty")
- Direct red cards after penalty: No afecta validación

