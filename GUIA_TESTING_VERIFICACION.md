# 🧪 Guía de Testing - Pipeline de Verificación

## Pruebas Rápidas

### Test 1: Verificar que los jobs están en el scheduler

```bash
php artisan schedule:list
```

**Debería mostrar:**
```
update-finished-matches          Every hour
verify-matches-hourly            Every hour at :05
```

---

### Test 2: Ejecutar UpdateFinishedMatchesJob manualmente

```bash
php artisan tinker
```

```php
// Dentro de tinker

>>> use App\Jobs\UpdateFinishedMatchesJob;
>>> UpdateFinishedMatchesJob::dispatch();
>>> // Luego ejecutar queue en otra terminal para procesar
```

**Verificar en logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "UpdateFinishedMatchesJob\|ProcessMatchBatchJob"
```

**Debería mostrar:**
```
[2026-01-22 14:00:15] local.INFO: UpdateFinishedMatchesJob iniciada
[2026-01-22 14:00:16] local.INFO: Partidos encontrados: 2
[2026-01-22 14:00:20] local.INFO: ProcessMatchBatchJob BATCH 1 ejecutando
[2026-01-22 14:00:25] local.INFO: ✅ Partido #123 actualizado desde API Football
[2026-01-22 14:00:30] local.INFO: ✅ Partido #456 actualizado desde Gemini
```

---

### Test 3: Ejecutar VerifyFinishedMatchesHourlyJob manualmente

```php
>>> use App\Jobs\VerifyFinishedMatchesHourlyJob;
>>> use App\Models\FootballMatch;

// Asegurar que hay al menos un partido con status "Match Finished"
>>> $match = FootballMatch::where('status', 'Match Finished')->first();
>>> if ($match) {
      VerifyFinishedMatchesHourlyJob::dispatch();
      echo "Job despachado para Match #" . $match->id;
    } else {
      echo "No hay partidos con status 'Match Finished'";
    }
```

**Verificar en logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "VerifyFinishedMatchesHourlyJob\|VerifyAllQuestionsJob"
```

**Debería mostrar:**
```
[2026-01-22 14:05:10] local.INFO: VerifyFinishedMatchesHourlyJob - matches selected for verification
[2026-01-22 14:05:11] local.INFO: VerifyFinishedMatchesHourlyJob - dispatching verification batch
[2026-01-22 14:05:15] local.INFO: BatchGetScoresJob - all matches resolved
[2026-01-22 14:05:20] local.INFO: BatchExtractEventsJob - all matches contain detailed events
[2026-01-22 14:05:25] local.INFO: VerifyAllQuestionsJob - processing chunk [chunk_size: 50]
[2026-01-22 14:05:30] local.INFO: VerifyAllQuestionsJob completed [processed_questions: 8]
```

---

### Test 4: Verificar que las preguntas se actualizaron

```php
>>> use App\Models\Question;

// Ver preguntas verificadas
>>> $verified = Question::whereNotNull('result_verified_at')->count();
>>> echo "Preguntas verificadas: " . $verified;

// Ver preguntas sin verificar
>>> $unverified = Question::whereNull('result_verified_at')->count();
>>> echo "Preguntas pendientes: " . $unverified;

// Ver una pregunta verificada con detalles
>>> $q = Question::whereNotNull('result_verified_at')->with(['options', 'answers'])->first();
>>> if ($q) {
      echo "Pregunta #" . $q->id . "\n";
      foreach ($q->options as $opt) {
        echo "  Option #{$opt->id}: is_correct = " . ($opt->is_correct ? 'YES' : 'NO') . "\n";
      }
      echo "  Respuestas:\n";
      foreach ($q->answers as $ans) {
        echo "    User: is_correct = " . ($ans->is_correct ? 'YES' : 'NO') . 
             ", points = " . $ans->points_earned . "\n";
      }
    }
```

---

### Test 5: Verificar cooldown de reintentos

```php
>>> use App\Models\FootballMatch;

// Ver partidos con last_verification_attempt_at
>>> $attempts = FootballMatch::whereNotNull('last_verification_attempt_at')
    ->select(['id', 'home_team', 'away_team', 'last_verification_attempt_at'])
    ->orderByDesc('last_verification_attempt_at')
    ->limit(5)
    ->get();

>>> foreach ($attempts as $m) {
      $mins = $m->last_verification_attempt_at->diffInMinutes(now());
      echo "Match #{$m->id}: {$m->home_team} vs {$m->away_team} - " . 
           "Hace {$mins} minutos\n";
    }
```

**Debería mostrar (si cooldown es 5 min):**
```
Match #123: Barcelona vs Real - Hace 2 minutos
Match #456: Atletico vs Sevilla - Hace 8 minutos (LISTO PARA REINTENTAR)
Match #789: Valencia vs Real Sociedad - Hace 3 minutos
```

---

## Testing Manual Completo

### Scenario: Simular un partido que se termina

```bash
# 1. Verificar estado inicial
php artisan tinker
```

```php
>>> use App\Models\FootballMatch;

// Encontrar un partido "Not Started"
>>> $match = FootballMatch::where('status', 'Not Started')
    ->where('date', '<=', now()->subHours(2))
    ->first();

>>> if ($match) {
      echo "Match #{$match->id}: {$match->home_team} vs {$match->away_team}\n";
      echo "Status: {$match->status}\n";
      echo "Date: {$match->date}\n";
      exit;
    }
```

```bash
# 2. Ejecutar UpdateFinishedMatchesJob
php artisan tinker
```

```php
>>> use App\Jobs\UpdateFinishedMatchesJob;
>>> UpdateFinishedMatchesJob::dispatch();
```

```bash
# Terminal 2: Ejecutar queue
php artisan queue:work --max-jobs=10
```

```bash
# 3. Verificar cambios
# Esperar 10 segundos, luego en terminal 1:
php artisan tinker
```

```php
>>> use App\Models\FootballMatch;
>>> $match = FootballMatch::find(123); // El ID del paso anterior
>>> echo "Status: {$match->status}\n";
>>> echo "Score: {$match->score}\n";
>>> echo "Home Score: {$match->home_team_score}\n";
>>> echo "Away Score: {$match->away_team_score}\n";
```

**Debería mostrar:**
```
Status: Match Finished
Score: 2 - 1
Home Score: 2
Away Score: 1
```

```bash
# 4. Ejecutar VerifyFinishedMatchesHourlyJob
php artisan tinker
```

```php
>>> use App\Jobs\VerifyFinishedMatchesHourlyJob;
>>> VerifyFinishedMatchesHourlyJob::dispatch();
```

```bash
# Terminal 2: queue:work seguirá procesando
# Esperar a que terminen todos los jobs

# 5. Verificar preguntas verificadas
php artisan tinker
```

```php
>>> use App\Models\Question;
>>> $questions = Question::where('football_match_id', 123)
    ->with(['options', 'answers'])
    ->get();

>>> echo "Total preguntas para ese partido: {$questions->count()}\n";
>>> foreach ($questions as $q) {
      $verified = $q->result_verified_at ? 'YES' : 'NO';
      $correct = $q->options->where('is_correct', true)->count();
      echo "  Q#{$q->id}: Verificada=$verified, Opciones correctas=$correct\n";
    }
```

---

## Debugging

### Problema: UpdateFinishedMatchesJob no encuentra partidos

```php
>>> use App\Models\FootballMatch;

// Ver todos los status
>>> $statuses = FootballMatch::select('status')
    ->distinct()
    ->pluck('status');
>>> echo "Status encontrados: " . $statuses->implode(', ');

// Ver partidos por fecha
>>> $recentNotStarted = FootballMatch::where('status', 'Not Started')
    ->where('date', '<=', now()->subHours(2))
    ->orderByDesc('date')
    ->limit(10)
    ->select(['id', 'home_team', 'away_team', 'date', 'status'])
    ->get();

>>> foreach ($recentNotStarted as $m) {
      echo "{$m->home_team} vs {$m->away_team} - {$m->date}\n";
    }
```

### Problema: Preguntas no se verifican

```php
>>> use App\Models\Question;

// Ver preguntas de un partido verificado
>>> $match = FootballMatch::where('status', 'Match Finished')->first();
>>> if ($match) {
      $questions = $match->questions()
        ->with(['options', 'answers', 'football_match'])
        ->limit(5)
        ->get();
      
      foreach ($questions as $q) {
        echo "Q#{$q->id}: Verified=" . ($q->result_verified_at ? 'YES' : 'NO') . "\n";
        echo "  Home Team: {$q->football_match->home_team}\n";
        echo "  Options: " . $q->options->count() . "\n";
        echo "  Answers: " . $q->answers->count() . "\n";
      }
    } else {
      echo "No hay partidos con status 'Match Finished'\n";
    }
```

### Problema: Jobs con errores

```bash
# Ver failed jobs
php artisan queue:failed

# Ver detalles de un failed job
php artisan queue:failed-show {id}

# Reintentar failed jobs
php artisan queue:retry all
```

---

## Monitoreo Continuo

```bash
# Terminal 1: Ver schedule siendo ejecutado
php artisan schedule:work --verbose

# Terminal 2: Procesar jobs en la cola
php artisan queue:work --verbose

# Terminal 3: Monitorear logs en tiempo real
tail -f storage/logs/laravel.log | grep -i "job\|verify\|batch\|question"

# Terminal 4: Hacer tinker queries
php artisan tinker
```

---

## Performance Checks

```php
>>> use App\Models\Question;
>>> use App\Models\FootballMatch;

// Tiempo promedio de verificación por pregunta
>>> $start = Question::whereNotNull('result_verified_at')
    ->orderBy('result_verified_at')
    ->first();

>>> $end = Question::whereNotNull('result_verified_at')
    ->orderByDesc('result_verified_at')
    ->first();

>>> if ($start && $end) {
      $count = Question::whereNotNull('result_verified_at')->count();
      $diff = $end->result_verified_at->diffInSeconds($start->result_verified_at);
      $avg = $count > 0 ? ($diff / $count) : 0;
      echo "Total preguntas verificadas: {$count}\n";
      echo "Tiempo total: {$diff} segundos\n";
      echo "Promedio por pregunta: " . number_format($avg, 2) . " segundos\n";
    }

// Partidos verificados por hora
>>> $byHour = FootballMatch::whereNotNull('last_verification_attempt_at')
    ->where('last_verification_attempt_at', '>=', now()->subDay())
    ->selectRaw('DATE_FORMAT(last_verification_attempt_at, "%Y-%m-%d %H:00") as hour, count(*) as count')
    ->groupBy('hour')
    ->get();

>>> echo "Partidos verificados por hora (últimas 24h):\n";
>>> foreach ($byHour as $row) {
      echo "  {$row->hour}: {$row->count} partidos\n";
    }
```

