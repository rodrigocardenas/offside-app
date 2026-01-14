# 🔧 GUÍA DE TROUBLESHOOTING - Verificación de Preguntas

Soluciones rápidas cuando las preguntas no se verifican correctamente.

---

## ❌ Problema 1: Preguntas con `result_verified_at = NULL`

### Síntoma
```
Las preguntas no se marcan como verificadas incluso después de horas
```

### Diagnóstico
```bash
# Buscar preguntas sin verificar
SELECT id, title, football_match_id, result_verified_at 
FROM questions 
WHERE result_verified_at IS NULL 
AND created_at > NOW() - INTERVAL 1 DAY
LIMIT 10;

# Ver si el partido tiene scores
SELECT id, home_team, away_team, score, home_team_score, away_team_score 
FROM football_matches 
WHERE id IN (...las match_ids del query anterior)
LIMIT 10;

# Ver logs del job
grep "VerifyQuestionResultsJob" storage/logs/laravel.log | tail -20
```

### Solución Rápida
```bash
# Opción 1: Ejecutar verificación manual
php artisan questions:verify-answers

# Opción 2: Reprocesar con detalles para ver qué falla
php artisan questions:repair --only-unverified --show-details
```

---

## ❌ Problema 2: Opciones con `is_correct = NULL` o `is_correct = false` (cuando deberían ser true)

### Síntoma
```
Las respuestas correctas se marcan como incorrectas
```

### Diagnóstico
```bash
# Ver preguntas y sus opciones correctas
SELECT 
  q.id, q.title, q.type,
  COUNT(*) as total_options,
  SUM(CASE WHEN o.is_correct = 1 THEN 1 ELSE 0 END) as correct_count
FROM questions q
JOIN question_options o ON q.id = o.question_id
WHERE q.football_match_id = [match_id]
GROUP BY q.id;

# Ver datos del partido
SELECT events, statistics 
FROM football_matches 
WHERE id = [match_id];
```

### Análisis
**Si `correct_count = 0` para todas:**
- Problema en `QuestionEvaluationService::evaluateQuestion()`
- Probably: Datos del partido no verificados (without events JSON)

**Si `correct_count > 0` pero erróneo:**
- Problema en lógica de evaluación de ese tipo de pregunta
- Revisar `evaluateWinner()`, `evaluateFirstGoal()`, etc.

### Solución Rápida
```bash
# Reprocesar solo ese partido con detalles
php artisan questions:repair --match-id=[match_id] --reprocess-all --show-details

# O verificar en logs qué evalúa
grep "Match has unverified\|Opción actualizada" storage/logs/laravel.log | tail -30
```

---

## ❌ Problema 3: `points_earned = NULL` o `0` cuando deberían tener puntos

### Síntoma
```
Los usuarios ganan 0 puntos incluso si responden correctamente
```

### Diagnóstico
```bash
# Ver respuestas y puntos
SELECT 
  a.id, a.user_id, a.question_option_id,
  a.is_correct, a.points_earned,
  q.points as question_default_points
FROM answers a
JOIN question_options qo ON a.question_option_id = qo.id
JOIN questions q ON qo.question_id = q.id
WHERE q.football_match_id = [match_id]
ORDER BY a.updated_at DESC
LIMIT 20;

# Ver si las opciones están marcadas correctamente
SELECT id, text, is_correct, question_id 
FROM question_options 
WHERE question_id IN (
  SELECT id FROM questions WHERE football_match_id = [match_id]
)
ORDER BY is_correct DESC;
```

### Causas Comunes
| Causa | Check |
|-------|-------|
| Opción NO marcada como correcta | Ver `is_correct = 0` en options |
| `question.points` = NULL | `SELECT points FROM questions WHERE id = ?` |
| Respuesta NO actualizada | Ver `answers.updated_at` reciente |

### Solución Rápida
```bash
# Reprocesar y asignar puntos
php artisan questions:verify-answers --force

# O específicamente por partido
php artisan questions:repair --match-id=[match_id] --reprocess-all

# Ver cuántos puntos se asignaron
grep "Puntos totales asignados" storage/logs/laravel.log | tail -5
```

---

## ❌ Problema 4: Eventos JSON inválido en `match.events`

### Síntoma
```
"events" field tiene texto, no JSON: "Resultado verificado desde Gemini: 3-0..."
```

### Diagnóstico
```bash
# Ver qué hay en events
SELECT id, events, statistics
FROM football_matches
WHERE status = 'Match Finished'
LIMIT 5;

# Contar cuántos tienen JSON vs texto
SELECT 
  SUM(CASE WHEN events LIKE '[{%' THEN 1 ELSE 0 END) as json_events,
  SUM(CASE WHEN events NOT LIKE '[{%' THEN 1 ELSE 0 END) as text_events
FROM football_matches
WHERE status = 'Match Finished';

# Ver si ExtractMatchDetailsJob se ejecutó
grep "ExtractMatchDetailsJob\|Detalles extraídos" storage/logs/laravel.log | tail -20
```

### Causas
- ❌ ExtractMatchDetailsJob NO se ejecutó
- ❌ Gemini `getDetailedMatchData()` retorna NULL
- ❌ ParseDetailedMatchData() falla silenciosamente

### Solución Rápida
```bash
# Ejecutar ExtractMatchDetailsJob manualmente
php artisan queue:work database --once

# Con más detalle (debug)
grep "parseDetailedMatchData\|getDetailedMatchData" storage/logs/laravel.log | tail -50

# Si falla, ver respuesta de Gemini
grep "Respuesta recibida de Gemini\|JSON inválido" storage/logs/laravel.log
```

---

## ❌ Problema 5: Queue jobs stuck/failed

### Síntoma
```
Jobs en estado "failed" en la tabla "failed_jobs"
```

### Diagnóstico
```bash
# Ver jobs fallidos
SELECT id, queue, payload, failed_at, exception 
FROM failed_jobs 
ORDER BY failed_at DESC 
LIMIT 10;

# Ver si hay jobs stuck
SELECT id, queue, attempts, reserved_at 
FROM jobs 
WHERE reserved_at < NOW() - INTERVAL 10 MINUTE;

# Ver logs de error
grep "ERROR\|CRITICAL" storage/logs/laravel.log | grep -i "job\|queue" | tail -20
```

### Causas Comunes
| Job | Causa | Fix |
|-----|-------|-----|
| ProcessMatchBatchJob | API rate limited | Esperar, logs dirán time |
| ExtractMatchDetailsJob | Gemini timeout | Aumentar timeout a 300s |
| VerifyQuestionResultsJob | Muchas preguntas | Reducir chunk size |

### Solución Rápida
```bash
# Retry jobs fallidos
php artisan queue:retry all

# O limpiar jobs muy viejos
php artisan queue:flush

# Ejecutar los jobs manualmente
php artisan questions:verify-answers
php artisan questions:repair
```

---

## ✅ Verificación de Salud

Ejecuta esto para verificar que todo funciona:

```bash
#!/bin/bash

echo "=== VERIFICACIÓN DE SALUD ==="

echo -e "\n1. Partidos finalizados:"
php artisan tinker --execute="
  \$count = App\Models\FootballMatch::where('status', 'Match Finished')->count();
  echo \"Total: \$count\n\";
"

echo -e "\n2. Preguntas sin verificar:"
php artisan tinker --execute="
  \$count = App\Models\Question::whereNull('result_verified_at')->count();
  echo \"Total: \$count\n\";
"

echo -e "\n3. Partidos con eventos JSON:"
php artisan tinker --execute="
  \$count = App\Models\FootballMatch::where('events', 'LIKE', '[{%')->count();
  \$total = App\Models\FootballMatch::where('status', 'Match Finished')->count();
  echo \"Con JSON: \$count / \$total\n\";
"

echo -e "\n4. Jobs fallidos:"
php artisan tinker --execute="
  \$count = DB::table('failed_jobs')->count();
  echo \"Total: \$count\n\";
"

echo -e "\n5. Puntos asignados hoy:"
php artisan tinker --execute="
  \$points = App\Models\Answer::where('is_correct', true)
    ->whereDate('updated_at', today())
    ->sum('points_earned');
  echo \"Total: \$points\n\";
"

echo -e "\n=== FIN VERIFICACIÓN ==="
```

---

## 🚀 Plan de Acción Recomendado

### Cuando algo no funciona:

1. **Primero**: Ejecuta la verificación de salud arriba
2. **Si preguntas sin verificar**: `php artisan questions:verify-answers`
3. **Si pocos eventos JSON**: `php artisan questions:repair --show-details`
4. **Si jobs fallidos**: `php artisan queue:retry all`
5. **Si nada funciona**: Revisar logs: `tail -100 storage/logs/laravel.log`

### Para monitoreo continuo:

```bash
# En crontab, cada 5 minutos
*/5 * * * * cd /path/to/app && php artisan questions:verify-answers --limit=100 >> /tmp/verify.log 2>&1

# O cada minuto para ser más agresivo
* * * * * cd /path/to/app && php artisan questions:verify-answers --limit=50 >> /tmp/verify.log 2>&1
```

---

## 📊 Dashboard de Salud

Script para monitorear estado:

```php
<?php
// Ver estado de verificación

$finishedMatches = \App\Models\FootballMatch::where('status', 'Match Finished')->count();
$verifiedQuestions = \App\Models\Question::whereNotNull('result_verified_at')->count();
$unverifiedQuestions = \App\Models\Question::whereNull('result_verified_at')->count();
$matchesWithEvents = \App\Models\FootballMatch::where('events', 'LIKE', '[{%')->count();

$pointsAssigned = \App\Models\Answer::where('is_correct', true)->sum('points_earned');
$failedJobs = DB::table('failed_jobs')->count();

echo "SALUD DEL SISTEMA:\n";
echo "Partidos finalizados: $finishedMatches\n";
echo "Preguntas verificadas: $verifiedQuestions\n";
echo "Preguntas sin verificar: $unverifiedQuestions\n";
echo "Partidos con eventos JSON: $matchesWithEvents/$finishedMatches\n";
echo "Puntos totales asignados: $pointsAssigned\n";
echo "Jobs fallidos: $failedJobs\n";
?>
```

---

¡Con esta guía podrás diagnosticar y resolver cualquier problema de verificación! 🎯
