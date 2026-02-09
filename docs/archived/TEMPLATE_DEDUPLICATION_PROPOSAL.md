# 🚀 DEDUPLICATION OPTIMIZATION - Template Question Grouping

**Propuesta:** Agrupar preguntas por `template_question_id` dentro del mismo partido
**Impacto:** Reducir llamadas a Gemini en 80-90% para preguntas duplicadas
**Complejidad:** Media
**Riesgo:** Muy bajo

---

## 📊 PROBLEMA ACTUAL

### Estructura de Datos

```
Match #296 (Barcelona vs Real Madrid)

┌──────────────────────────────────────────┐
│ Preguntas en BD                          │
├──────────────────────────────────────────┤
│ Question #29                             │
│ ├─ template_question_id: 5               │
│ ├─ group_id: 1 (Grupo A)                │
│ ├─ title: "¿Quién ganará?"              │
│ └─ match_id: 296                        │
│                                          │
│ Question #24                             │
│ ├─ template_question_id: 5 (MISMO!)     │
│ ├─ group_id: 2 (Grupo B)                │
│ ├─ title: "¿Quién ganará?"              │
│ └─ match_id: 296 (MISMO!)               │
│                                          │
│ Question #18                             │
│ ├─ template_question_id: 5 (MISMO!)     │
│ ├─ group_id: 3 (Grupo C)                │
│ ├─ title: "¿Quién ganará?"              │
│ └─ match_id: 296 (MISMO!)               │
└──────────────────────────────────────────┘
```

### Flujo ACTUAL (Ineficiente)

```
VerifyAllQuestionsJob
├─ Question #29
│  ├─ evaluateQuestion()
│  ├─ Llamar Gemini: "¿Quién ganará Barcelona vs Madrid?"
│  └─ Resultado: Barcelona
│
├─ Question #24
│  ├─ evaluateQuestion()
│  ├─ Llamar Gemini: "¿Quién ganará Barcelona vs Madrid?" (REPETIDO!)
│  └─ Resultado: Barcelona (MISMO!)
│
└─ Question #18
   ├─ evaluateQuestion()
   ├─ Llamar Gemini: "¿Quién ganará Barcelona vs Madrid?" (REPETIDO!)
   └─ Resultado: Barcelona (MISMO!)

TOTAL: 3 llamadas a Gemini para preguntas IDÉNTICAS
```

---

## ✅ SOLUCIÓN: DEDUPLICATION BY TEMPLATE

### Flujo OPTIMIZADO

```
VerifyAllQuestionsJob
├─ Agrupar por (match_id, template_question_id)
│  └─ Group: {match_id: 296, template_id: 5}
│     ├─ Question #29 (Grupo A)
│     ├─ Question #24 (Grupo B)
│     └─ Question #18 (Grupo C)
│
├─ Procesar una sola vez:
│  ├─ evaluateQuestion(Question #29) ← REPRESENTATIVE
│  ├─ Llamar Gemini UNA SOLA VEZ
│  ├─ Resultado: Barcelona ✅
│  │
│  └─ Aplicar MISMO resultado a:
│     ├─ Question #24 ← mismo template
│     └─ Question #18 ← mismo template

TOTAL: 1 llamada a Gemini (vs 3 antes)
MEJORA: 66% reducción en API calls
```

---

## 🔧 IMPLEMENTACIÓN

### Estructura de Datos

```php
// Nueva estructura en QuestionEvaluationService

// Array agrupado por (match_id, template_question_id)
private array $templateResultsCache = [
    // Clave: "match_id|template_question_id"
    // Valor: array de IDs de opciones correctas
    "296|5" => [1, 2],  // Barcelona wins (options 1 y 2)
    "296|7" => [3],     // First goal: Messi
];
```

### Lógica de Agrupación

```php
public function deduplicateQuestionsByTemplate(
    array $questions,
    FootballMatch $match
): array {
    // Agrupar por template_question_id
    $grouped = [];
    
    foreach ($questions as $question) {
        $key = "match_{$match->id}_template_{$question->template_question_id}";
        
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'representative' => $question,  // Una pregunta para verificar
                'duplicates' => [],              // Todas las demás del grupo
            ];
        } else {
            $grouped[$key]['duplicates'][] = $question;
        }
    }
    
    return $grouped;
}
```

---

## 📈 IMPACTO ESPERADO

### Scenario: Match con 100 preguntas (10 templates únicos)

**ANTES:**
- 100 llamadas a Gemini
- Rate limit casi seguro
- Tiempo: 500-800s

**DESPUÉS:**
- 10 llamadas a Gemini (deduplicadas)
- Sin rate limit
- Tiempo: 50-80s
- **Mejora: 80-90%** ⚡

### Scenario: Match típico (30 preguntas, 5 templates)

**ANTES:**
- 30 llamadas a Gemini
- Tiempo: 150-200s

**DESPUÉS:**
- 5 llamadas a Gemini
- Tiempo: 25-35s
- **Mejora: 83%** ⚡

---

## 🔄 IMPLEMENTACIÓN DETALLADA

### Paso 1: Agregar método de deduplicación

```php
// En QuestionEvaluationService.php

/**
 * Agrupa preguntas por template_question_id para evitar verificaciones duplicadas.
 * 
 * Ejemplo:
 * - Question #29 (template_id: 5, group: 1)
 * - Question #24 (template_id: 5, group: 2)  ← Duplicada
 * - Question #18 (template_id: 5, group: 3)  ← Duplicada
 * 
 * Solo verifica Question #29, luego aplica resultado a #24 y #18
 */
public function getDeduplicatedQuestionGroups(array $questions): array
```

### Paso 2: Modificar evaluateQuestion()

```php
// Agregar al inicio
$templateKey = "{$question->match_id}|{$question->template_question_id}";

if (isset($this->templateResultsCache[$templateKey])) {
    Log::info('Using cached template result (deduplication)', [
        'question_id' => $question->id,
        'template_id' => $question->template_question_id,
        'from_cache' => true,
    ]);
    
    return $this->templateResultsCache[$templateKey];
}

// ... normal verification logic ...

// Cachear resultado para templates duplicadas
$this->templateResultsCache[$templateKey] = $correctOptions;
```

### Paso 3: Usar en batch jobs

```php
// En VerifyAllQuestionsJob.php

foreach ($questions as $question) {
    $match = $question->football_match;
    
    // ✅ NEW: Verificar si ya hemos calculado este template
    $correctOptionIds = $evaluationService->evaluateQuestion($question, $match);
    
    // El cache interno maneja la deduplicación automáticamente
}
```

---

## 🎯 WHERE TO IMPLEMENT

### Files to Modify

1. **QuestionEvaluationService.php**
   - Add `getDeduplicatedQuestionGroups()` method
   - Add `templateResultsCache` property
   - Modify `evaluateQuestion()` to check cache

2. **RepairQuestionVerification.php**
   - Add option: `--no-dedup` to disable deduplication (for testing)
   - Add logging for dedup hits

3. **VerifyQuestionAnswers.php**
   - Add option: `--no-dedup` to disable deduplication
   - Add logging for dedup hits

4. **VerifyAllQuestionsJob.php**
   - Already works with dedup (handled automatically in service)

---

## 📊 METRICS TO TRACK

### Log Entries to Monitor

```bash
# Deduplication hits (should be HIGH)
grep "Using cached template result" storage/logs/laravel.log | wc -l

# Total questions verified
grep "question verified" storage/logs/laravel.log | wc -l

# Hit rate should be 80-90%
hit_rate = cache_hits / total_questions
```

### Expected Output

```
Total Questions: 100
Template Verification Hits: 82 (cached)
Template Verification Misses: 18 (verified)
Hit Rate: 82%

API Calls Saved: 82 calls
Time Saved: ~300-400 seconds
```

---

## ⚙️ CONFIGURATION

### Optional Disable

```bash
# For testing/debugging - disable deduplication
php artisan questions:repair --match-id=296 --no-dedup

# Should make 30 API calls instead of 5
```

---

## 🔒 SAFETY CHECKS

### Validation Rules

1. ✅ Same `template_question_id` within same match
2. ✅ Same question text (deterministic check)
3. ✅ Same options set (verify option IDs match)
4. ⚠️ LOG if any mismatch (potential data issue)

---

## 📝 EXPECTED IMPROVEMENTS

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| API Calls (100 q) | 100 | 10 | -90% |
| Verification Time | 500s | 50s | -90% |
| Rate Limit Events | Frequent | Rare | -95% |
| Cost (if API charges) | $$ | $ | -90% |

---

## 🚀 PHASED ROLLOUT

### Phase 1: Implement (1-2 hours)
- Add deduplication logic
- Add caching mechanism
- Add logging

### Phase 2: Test (1-2 hours)
- Test with 100-question matches
- Verify cache hits
- Measure improvement

### Phase 3: Deploy (30 min)
- Deploy to production
- Monitor metrics
- Verify no accuracy degradation

---

## ⚠️ POTENTIAL ISSUES & MITIGATIONS

| Issue | Mitigation |
|-------|-----------|
| Cache not cleared between runs | Session-scoped cache (auto-clears per job) |
| Different options for same template | Add validation + logging |
| Template question text differs | Add fuzzy matching check |
| Race conditions in concurrent jobs | Cache is session-scoped (no conflicts) |

---

## 🎓 KEY INSIGHT

**Why This Works:**
- Template questions are IDENTICAL for same match
- Only "group_id" differs (cosmetic)
- Deterministic evaluation → same result always
- Safe to cache and replicate

**The Magic:**
```
Same match + Same template + Same options 
= SAME verification result always 
= SAFE to cache and reuse
```

---

## 📞 NEXT STEPS

1. Review this proposal
2. Implement deduplication in QuestionEvaluationService
3. Test with matches having 50+ questions
4. Monitor cache hit rate
5. Compare metrics before/after
