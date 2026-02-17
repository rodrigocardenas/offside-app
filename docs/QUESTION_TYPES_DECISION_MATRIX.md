# 🎯 Matriz de Decisión: Qué Implementar Primero

> **Documento de síntesis:** Ayuda a priorizar nuevos tipos de preguntas basado en ROI, esfuerzo e impacto.

---

## 📊 Matriz de Priorización

### Nivel de Esfuerzo vs Impacto para Usuarios

```
    ALTO IMPACTO
         ⬆
         │
      S5 │ S1, S2  S11* 
    S10* │ S3, S6  S12*
         │ S4, S9       S7*, S8*, S10
         │
    BAJO │ S13     
    IMPACTO
         │
         └─────────────────────────────►
         BAJO     ESFUERZO     ALTO
         
         * Requiere PM/Designer review
```

### Tabla Detallada de Scoring

| ID | Nombre | Esfuerzo | Impacto | ROI | Prioridad | Semana | Owner |
|----|--------|----------|---------|-----|-----------|--------|-------|
| S1 | Late Goal (últimos 15 min) | 1 | 4.0 | 4.0 | 🔴 NOW | W1 | Dev |
| S5 | Goal B4 Halftime | 1 | 4.0 | 4.0 | 🔴 NOW | W1 | Dev |
| S2 | Shots on Target | 1 | 3.5 | 3.5 | 🔴 NOW | W1 | Dev |
| S3 | Total Shots | 1 | 3.5 | 3.5 | 🟡 Soon | W2 | Dev |
| S4 | Corners Count | 1 | 3.5 | 3.5 | 🟡 Soon | W2 | Dev |
| S6 | Goals after 60min | 1 | 3.0 | 3.0 | 🟡 Soon | W2 | Dev |
| S9 | Total Cards | 2 | 3.5 | 1.75 | 🟡 Soon | W2 | Dev |
| S7 | Offsides Count | 1 | 2.0 | 2.0 | 🟠 Later | W3 | Dev |
| S8 | Pass Accuracy | 1 | 2.0 | 2.0 | 🟠 Later | W3 | Dev |
| S10 | Goal Winner ⭐ | 3 | 4.5 | 1.5 | 🟠 Later | W4 | PM+Dev |
| S11 | First Scorer ⭐⭐ | 4 | 5.0 | 1.25 | 🔵 Backlog | TBD | PM+Design+Dev |
| S12 | Assists | 2 | 2.0 | 1.0 | 🔵 Backlog | TBD | Dev |

---

## 🚀 RECOMENDACIÓN INMEDIATA

### Sprint 1 (1-2 días)
**Objetivo:** 3 preguntas nuevas de altísimo ROI (~100 líneas de código)

```
✅ S1: Gol en últimos 15 minutos
   ├─ Esfuerzo: 15 min de code
   ├─ Impacto: 4/5 (dramaticidad/suspenso)
   ├─ Patrón: filter events by minute >= 75
   └─ UX: "¿Habrá gol tensionante?" 🎬

✅ S5: Gol antes de descanso (min 45)
   ├─ Esfuerzo: 5 min (reutilizar GoalBeforeMinute)
   ├─ Impacto: 4/5 (ritmo del partido)
   ├─ Patrón: evaluateGoalBeforeMinute(..., 45)
   └─ UX: "¿Habrá gol en el primer tiempo?"

✅ S2: Tiros al arco
   ├─ Esfuerzo: 20 min de code
   ├─ Impacto: 3.5/5 (análisis táctico)
   ├─ Patrón: compare stats['shots_on_target']
   └─ UX: "¿Quién tuvo más tiros al arco?"
```

**Implementación:** 1 hora total

---

## 📋 Checklist de Implementación

### Para cada nueva pregunta tipo:

- [ ] **Código:** Implementar método `evaluate*` en QuestionEvaluationService
- [ ] **Lógica:** Agregar case en `evaluateQuestion()` 
- [ ] **Test:** Agregar test case en FirstGoalQuestionEvaluationTest
- [ ] **Doc:** Actualizar esta referencia con nuevo tipo
- [ ] **Data validation:** Verificar datos disponibles en 80%+ de partidos
- [ ] **QA:** Probar con 3-5 partidos reales
- [ ] **Deploy:** Merge a main + deploy a producción

---

## 🧮 Fórmula de ROI

```
ROI = (Valor para Usuarios × Disponibilidad de Datos) / Esfuerzo en Horas

Donde:
  Valor = 1-5 (engagement/interés)
  Disponibilidad = 0.6-1.0 (qué % de partidos tienen datos)
  Esfuerzo = horas de desarrollo

Ejemplos:
  S1 = (4.0 × 0.95) / 0.25 = 15.2 ✅ EXCELENTE
  S5 = (4.0 × 0.95) / 0.08 = 47.5 ✅ PERFECTO
  S2 = (3.5 × 0.90) / 0.33 = 9.5  ✅ EXCELENTE
  S11= (5.0 × 0.85) / 4.0 = 1.06  ⚠️ DIFICIL
```

---

## 🎓 Datos Críticos para Validar Antes

Antes de implementar nuevos tipos, asegurar que los datos estén disponibles:

```bash
# Query para verificar disponibilidad de tiros al arco
SELECT 
  COUNT(*) as total_matches,
  SUM(CASE WHEN JSON_EXTRACT(statistics, '$.home.shots_on_target') IS NOT NULL THEN 1 ELSE 0 END) as has_shots_on_target,
  ROUND(SUM(CASE WHEN JSON_EXTRACT(statistics, '$.home.shots_on_target') IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as availability_percent
FROM football_matches
WHERE status IN ('FINISHED', 'Match Finished')
AND created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH);

# Target: > 80% disponibilidad
```

---

## 🔄 Ciclo de Vida de Implementación

```
Plan Pregunta (30min)
    ↓
Implementar evaluate*() (30-60min)
    ↓
Agregar a evaluateQuestion() (10min)
    ↓
Escri test cases (20min)
    ↓
Testing con datos reales (15min)
    ↓
Code review (20min)
    ↓
Deploy (5min)
    ↓
Monitor en producción (first 24h)
    
TOTAL: 2-3 horas por pregunta
```

---

## 💡 Tips de Implementación

### Reutilización de Código

```php
// En lugar de copiar, reutilizar:

// ❌ DON'T: Copiar logica de comparación
private function evaluateTotalShots(...) {
    $home = count(array_filter($events, ...));
    $away = count(array_filter($events, ...));
    foreach ($q->options as $option) {
        if ($home > $away && strpos($option->text, 'Home')) ...
    }
}

// ✅ DO: Crear helper general
private function compareTeamStats(array $opts, int $home, int $away, 
    string $teamOneName, string $teamTwoName): array {
    return $this->findMatchingOptions($opts, /* resultado */);
}

// Usar en múltiples métodos
$result = $this->compareTeamStats(
    $q->options, 
    $homeTiros, 
    $awayTiros,
    $match->home_team,
    $match->away_team
);
```

### Logging para Debug

```php
Log::debug('Evaluating [question type]', [
    'question_id' => $q->id,
    'match_id' => $m->id,
    'data_available' => [
        'events_count' => count($events),
        'has_statistics' => !empty($m->statistics)
    ],
    'result' => $result
]);
```

---

## 📈 Métricas de Éxito

Una vez implementado S1-S6, monitorear:

```
✅ Adoption Rate: % de preguntas de estos tipos vs total
   TARGET: > 15% en 2 semanas

✅ Verification Rate: % de preguntas verificadas con éxito
   TARGET: > 95%

✅ User Engagement: Respuestas por pregunta tipo
   TARGET: Similar o mayor que tipos existentes

✅ Accuracy: Validación manual de resultados
   TARGET: 100% (determinístico)
```

---

## 🔗 Enlaces de Referencia

1. **Detalles de cada tipo:** [QUESTION_TYPES_REFERENCE.md](QUESTION_TYPES_REFERENCE.md)
2. **Resumen visual rápido:** [QUESTION_TYPES_QUICK_REFERENCE.md](QUESTION_TYPES_QUICK_REFERENCE.md)
3. **Código actual:** `app/Services/QuestionEvaluationService.php`
4. **Histórico:** `docs/archived/PHASE_2_COMPLETED.md`

---

## 📝 Notas Finales

- **Constraint:** No usar Gemini (mantener determinístico)
- **Datos:** Solo usar lo que está en `events` y `statistics`
- **Test:** Mínimo 3-5 partidos reales por tipo
- **Documentación:** Updatear QUESTION_TYPES_REFERENCE.md
- **Backwards-compatible:** No romper tipos existentes

