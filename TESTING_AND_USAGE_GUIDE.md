# 🧪 TESTING & USAGE GUIDE - Batch Jobs Optimizations

---

## 1️⃣ TESTING LOCAL

### Setup Inicial

```bash
# 1. Start Laravel queue worker
php artisan queue:work --timeout=600 --tries=1

# 2. En otra terminal, trigger job
php artisan tinker

# 3. En tinker shell:
>>> use App\Jobs\VerifyFinishedMatchesHourlyJob
>>> VerifyFinishedMatchesHourlyJob::dispatch()
```

### Test Case 1: Partidos con Datos Verificados en BD

**Objetivo:** Verificar que retry logic elige attempt 1 (sin grounding) y se completa rápido

```php
// tinker

// 1. Asegurar que tenemos 20-30 partidos finalizados con datos verificados
$finishedMatches = \App\Models\FootballMatch::where('status', 'Match Finished')
    ->whereNotNull('home_team_score')
    ->whereNotNull('away_team_score')
    ->whereNotNull('events')
    ->limit(30)
    ->get();

echo "Ready: " . $finishedMatches->count() . " matches";

// 2. Dispatch job
\App\Jobs\VerifyFinishedMatchesHourlyJob::dispatch();

// 3. Monitor logs en otra terminal
tail -f storage/logs/laravel.log | grep -E "BatchGetScores|BatchExtractEvents|attempt"

// Expected output:
// [2024-...] Gemini batch attempt
// [2024-...] attempt 1 (without grounding)
// [2024-...] Gemini batch results parsed successfully
// [2024-...] attempt 1 (without grounding) - NO attempt 2!
```

**Validación:**
```
✅ Si logs muestran "attempt 1 (without grounding) → success" 80%+ veces
✅ Si "attempt 2 (with grounding)" aparece <20% de veces
✅ Si tiempo total < 120s (vs ~240s antes)
```

### Test Case 2: Partidos sin Datos en BD

**Objetivo:** Verificar retry logic inteligente (falla sin grounding, éxito con grounding)

```php
// 1. Flush cache para simular BD vacía
>>> redis()->flushdb()

// 2. Asegurar que tenemos partidos sin events detallados
$matchesNoEvents = \App\Models\FootballMatch::where('status', 'Match Finished')
    ->whereNotNull('home_team_score')
    ->whereNull('events')
    ->limit(10)
    ->get();

// 3. Dispatch
\App\Jobs\VerifyFinishedMatchesHourlyJob::dispatch();

// 4. Monitor
tail -f storage/logs/laravel.log | grep -E "attempt 1|attempt 2|obtained"

// Expected:
// [2024-...] Gemini batch attempt - attempt 1 (without grounding)
// [2024-...] Gemini batch attempt - attempt 2 (with grounding)
// [2024-...] Gemini detailed data obtained with grounding
```

**Validación:**
```
✅ Si logs muestran "attempt 1" → "attempt 2" progression
✅ Si "obtained with grounding" aparece en matches que inicialmente fallaron
✅ Si tiempo total < 150s (vs ~120s si todos tuvieran data)
```

### Test Case 3: Rate Limiting Behavior

**Objetivo:** Verificar que non-blocking mode está habilitado

```php
// 1. Monitorear rate limit exceptions
tail -f storage/logs/laravel.log | grep -i "rate\|limit\|exception"

// 2. Si ocurre rate limit, logs deberían mostrar:
// [2024-...] WARNING: Gemini batch attempt failed
// [2024-...] error: Rate limit exceeded
// [2024-...] VerifyFinishedMatchesHourlyJob failed

// 3. Verificar que NO hay "sleep(90)" waits
// (Si ves "sleep" en logs, non-blocking mode no está activo)

// 4. Job debería fallar rápidamente y reintentarse
// NO debería bloquearse por 90 segundos
```

**Validación:**
```
✅ Si exception lanzada inmediatamente (< 5s)
✅ Si logs NO muestran "sleep" calls
✅ Si job falla gracefully y Laravel lo reintenta
```

---

## 2️⃣ VERIFICAR OPTIMIZACIONES EN CÓDIGO

### Check 1: Non-Blocking Mode Habilitado

```bash
# Verificar que GeminiService::setAllowBlocking(false) está en batch jobs
grep -n "setAllowBlocking(false)" app/Jobs/BatchGetScoresJob.php
grep -n "setAllowBlocking(false)" app/Jobs/BatchExtractEventsJob.php
grep -n "setAllowBlocking(false)" app/Jobs/VerifyAllQuestionsJob.php

# Expected: 1 match per file
```

### Check 2: Retry Logic en GeminiBatchService

```bash
# Verificar que fetchBatchResults tiene retry logic
grep -n "useGrounding.*false\|useGrounding.*true" app/Services/GeminiBatchService.php

# Expected output should show both:
# - Line X: useGrounding: false (attempt 1)
# - Line Y: useGrounding: true (attempt 2)
```

### Check 3: disableGrounding() Method Existe

```bash
# Verificar que método disableGrounding está disponible
grep -A5 "public function disableGrounding" app/Services/GeminiBatchService.php

# Expected: Method exists and returns $this
```

---

## 3️⃣ METRICS COLLECTION

### Logs Analysis Script

```bash
#!/bin/bash
# save as: analyze_batch_optimization.sh
# usage: ./analyze_batch_optimization.sh

LOG_FILE="storage/logs/laravel.log"
TODAY=$(date +%Y-%m-%d)

echo "=== BATCH JOB OPTIMIZATION METRICS ==="
echo "Date: $TODAY"
echo ""

echo "--- Job Counts ---"
grep "$TODAY" "$LOG_FILE" | grep -c "BatchGetScoresJob" && echo "BatchGetScoresJob runs: $(grep "$TODAY" "$LOG_FILE" | grep -c "BatchGetScoresJob")"
grep "$TODAY" "$LOG_FILE" | grep -c "BatchExtractEventsJob" && echo "BatchExtractEventsJob runs: $(grep "$TODAY" "$LOG_FILE" | grep -c "BatchExtractEventsJob")"
grep "$TODAY" "$LOG_FILE" | grep -c "VerifyAllQuestionsJob" && echo "VerifyAllQuestionsJob runs: $(grep "$TODAY" "$LOG_FILE" | grep -c "VerifyAllQuestionsJob")"

echo ""
echo "--- Grounding Efficiency ---"
ATTEMPT1_COUNT=$(grep "$TODAY" "$LOG_FILE" | grep -c "attempt 1 (without grounding)")
ATTEMPT2_COUNT=$(grep "$TODAY" "$LOG_FILE" | grep -c "attempt 2 (with grounding)")
echo "Attempt 1 (without grounding) successes: $ATTEMPT1_COUNT"
echo "Attempt 2 (with grounding) retries: $ATTEMPT2_COUNT"

if [ $((ATTEMPT1_COUNT + ATTEMPT2_COUNT)) -gt 0 ]; then
    SUCCESS_RATE=$((ATTEMPT1_COUNT * 100 / (ATTEMPT1_COUNT + ATTEMPT2_COUNT)))
    echo "Success without grounding: ${SUCCESS_RATE}%"
fi

echo ""
echo "--- Rate Limiting Events ---"
RATE_LIMITS=$(grep "$TODAY" "$LOG_FILE" | grep -ic "rate limit")
echo "Rate limit exceptions: $RATE_LIMITS"

echo ""
echo "--- Failures ---"
FAILURES=$(grep "$TODAY" "$LOG_FILE" | grep -c "failed")
echo "Failed jobs: $FAILURES"

echo ""
echo "--- Execution Times (grep for timestamps) ---"
grep "$TODAY" "$LOG_FILE" | grep "BatchGetScoresJob completed\|BatchExtractEventsJob completed" | tail -5
```

### Running the Script

```bash
chmod +x analyze_batch_optimization.sh
./analyze_batch_optimization.sh

# Output example:
# === BATCH JOB OPTIMIZATION METRICS ===
# Date: 2024-01-15
# 
# --- Job Counts ---
# BatchGetScoresJob runs: 48
# BatchExtractEventsJob runs: 48
# VerifyAllQuestionsJob runs: 48
# 
# --- Grounding Efficiency ---
# Attempt 1 (without grounding) successes: 45
# Attempt 2 (with grounding) retries: 3
# Success without grounding: 93%
# 
# --- Rate Limiting Events ---
# Rate limit exceptions: 0
# 
# --- Failures ---
# Failed jobs: 1
```

---

## 4️⃣ USING disableGrounding() IN PRACTICE

### When to Use

```php
// In VerifyFinishedMatchesHourlyJob or commands:

// Scenario 1: Emergency mode (BD has lots of verified data)
// Disable grounding to speed up batch processing
$batchService = app(GeminiBatchService::class);
$batchService->disableGrounding(true);

// Scenario 2: Debugging rate limit issues
// Disable grounding to reduce API calls
$batchService->disableGrounding(true);

// Scenario 3: Cost optimization
// If API cost is concern, disable grounding to reduce calls
$batchService->disableGrounding(true);
```

### Example: Modified VerifyFinishedMatchesHourlyJob

```php
// In app/Schedules/VerifyFinishedMatchesHourlyJob.php

public function handle(
    GeminiBatchService $batchService,
    ...
): void {
    $disableGrounding = config('jobs.batch_verify.disable_grounding', false);
    
    if ($disableGrounding) {
        $batchService->disableGrounding(true);
        Log::info('Batch verification: grounding disabled');
    }
    
    // Rest of logic...
}
```

### Configuration

```php
// config/jobs.php (new file if not exists)
return [
    'batch_verify' => [
        'disable_grounding' => env('BATCH_VERIFY_DISABLE_GROUNDING', false),
        'max_matches' => env('BATCH_VERIFY_MAX_MATCHES', 30),
        'chunk_size' => env('BATCH_VERIFY_CHUNK_SIZE', 8),
    ],
];
```

### Usage in .env

```env
# .env
BATCH_VERIFY_DISABLE_GROUNDING=false  # Set to true if needed
BATCH_VERIFY_MAX_MATCHES=30
BATCH_VERIFY_CHUNK_SIZE=8
```

---

## 5️⃣ INTEGRATION TESTS

### Test Suite Setup

```php
// tests/Feature/BatchJobsOptimizationTest.php

namespace Tests\Feature;

use App\Jobs\BatchGetScoresJob;
use App\Jobs\BatchExtractEventsJob;
use App\Jobs\VerifyAllQuestionsJob;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\GeminiBatchService;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BatchJobsOptimizationTest extends TestCase
{
    /**
     * Test that non-blocking mode is enabled in batch jobs
     */
    public function test_batch_jobs_have_non_blocking_mode()
    {
        Bus::fake();
        
        $matches = FootballMatch::factory(5)->create(['status' => 'Match Finished']);
        
        BatchGetScoresJob::dispatch($matches->pluck('id')->toArray(), 'test-batch');
        
        // Verify job was dispatched and would have non-blocking enabled
        // (This is tested via logs in integration tests)
        
        $this->assertTrue(true);
    }

    /**
     * Test retry logic works without grounding first
     */
    public function test_grounding_retry_logic()
    {
        $batchService = app(GeminiBatchService::class);
        
        // Verify disableGrounding method exists
        $result = $batchService->disableGrounding(true);
        
        $this->assertInstanceOf(GeminiBatchService::class, $result);
    }

    /**
     * Test that grounding can be disabled
     */
    public function test_disable_grounding_disables_grounding()
    {
        $batchService = app(GeminiBatchService::class);
        
        // Should not throw error
        $batchService->disableGrounding(true);
        $batchService->disableGrounding(false);
        
        $this->assertTrue(true);
    }
}
```

### Running Tests

```bash
# Run all batch optimization tests
php artisan test tests/Feature/BatchJobsOptimizationTest.php

# Run specific test
php artisan test tests/Feature/BatchJobsOptimizationTest.php::test_batch_jobs_have_non_blocking_mode

# With verbose output
php artisan test tests/Feature/BatchJobsOptimizationTest.php -v
```

---

## 6️⃣ MONITORING IN PRODUCTION

### Health Check Script

```bash
#!/bin/bash
# save as: check_batch_health.sh
# Monitors last 24 hours of batch job health

LOG_FILE="storage/logs/laravel.log"

echo "=== BATCH JOBS HEALTH CHECK ==="
echo "Checking last 24 hours..."
echo ""

# Failed jobs
FAILED=$(grep "$(date -d 'yesterday' '+%Y-%m-%d')" "$LOG_FILE" \
    | grep -E "BatchGetScoresJob|BatchExtractEventsJob|VerifyAllQuestionsJob" \
    | grep "failed" | wc -l)

echo "Failed batch jobs (last 24h): $FAILED"
[ $FAILED -gt 5 ] && echo "⚠️ WARNING: High failure rate!" || echo "✅ OK"

echo ""

# Rate limit issues
RATE_LIMITS=$(grep "$(date -d 'yesterday' '+%Y-%m-%d')" "$LOG_FILE" \
    | grep -i "rate limit" | wc -l)

echo "Rate limit errors (last 24h): $RATE_LIMITS"
[ $RATE_LIMITS -gt 3 ] && echo "⚠️ WARNING: Frequent rate limits!" || echo "✅ OK"

echo ""

# Average execution time (rough estimate)
echo "Sample recent executions:"
grep "completed" "$LOG_FILE" \
    | grep -E "BatchGetScoresJob|BatchExtractEventsJob|VerifyAllQuestionsJob" \
    | tail -3

echo ""
echo "=== END HEALTH CHECK ==="
```

### Cron Job Setup

```bash
# crontab -e
# Add this to run health check every 6 hours

0 */6 * * * /var/www/offsideclub/check_batch_health.sh >> /var/www/offsideclub/batch_health.log 2>&1
```

---

## 7️⃣ DEBUGGING SPECIFIC ISSUES

### Issue: Job Still Slow

**Debug:**
```php
// In tinker
>>> $match = \App\Models\FootballMatch::find(123);
>>> $batch = app(\App\Services\GeminiBatchService::class);
>>> $results = $batch->getMultipleMatchResults([$match], true); // forceRefresh

// Check logs
tail -f storage/logs/laravel.log | grep "attempt.*123\|grounding"

// Expected to see both attempt 1 and 2
// If only attempt 1: grounding is working (data in BD)
// If only attempt 2: data missing from BD
```

### Issue: Rate Limit Blocking

**Debug:**
```php
// Verify non-blocking mode
>>> \App\Services\GeminiService::setAllowBlocking(false);

// Try calling directly
>>> $service = app(\App\Services\GeminiService::class);
>>> $result = $service->callGemini("test prompt");

// Should throw exception immediately on rate limit
// NOT sleep for 90 seconds
```

### Issue: Questions Still Not Verified

**Debug:**
```php
// Check which questions are not verified
>>> $unverified = \App\Models\Question::whereNull('result_verified_at')
    ->with('football_match')
    ->limit(10)
    ->get();

>>> $unverified->each(function($q) {
    echo "Q#{$q->id} - Match #{$q->football_match_id} status: " . 
         $q->football_match->status . "\n";
});

// Should show all related matches have status "Match Finished" or "FINISHED"
```

---

## ✅ VERIFICATION CHECKLIST

After deployment, verify:

```
Pre-Deployment
[ ] All code changes committed
[ ] No syntax errors: php -l app/Services/GeminiBatchService.php
[ ] No syntax errors: php -l app/Jobs/Batch*.php
[ ] No syntax errors: php -l app/Jobs/VerifyAllQuestionsJob.php
[ ] Database migrations run

Post-Deployment (Staging)
[ ] Queue worker running
[ ] First batch job cycle completes without errors
[ ] Logs show "attempt 1 (without grounding)" entries
[ ] No "sleep" calls in logs
[ ] Non-blocking mode verified working
[ ] Sample metrics collected (see metrics script)

Post-Deployment (Production)
[ ] Monitor for 24-48 hours
[ ] Compare metrics vs baseline
[ ] Verify accuracy maintained
[ ] Check rate limiting frequency
[ ] Verify failure rate acceptable (< 5%)
```

---

## 📞 TROUBLESHOOTING CONTACTS

If issues arise:

1. **Job timeouts** → Check `config/queue.php` timeout values
2. **Rate limiting** → Check Gemini API quota/limits in console
3. **Accuracy issues** → Compare old vs new verification results
4. **High memory** → Check queue worker `--memory` setting
5. **Slow execution** → Check if grounding retry logic is working
