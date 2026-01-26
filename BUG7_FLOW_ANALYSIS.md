# Bug #7: Análisis del Flujo de Verificación de Resultados y Asignación de Puntos

## 📊 Diagrama del Flujo Completo

```
SCHEDULER (Kernel.php)
│
├─ 00:00:00 → UpdateFinishedMatchesJob (CADA HORA)
│  │
│  ├─ 🔍 Busca partidos:
│  │  ├ status != 'FINISHED'
│  │  └ date <= now() - 2 horas
│  │
│  ├─ 📦 Divide en lotes de 5 partidos
│  │
│  └─ 🚀 Despacha ProcessMatchBatchJob con delays:
│     ├ Lote 1 → +10 segundos
│     ├ Lote 2 → +20 segundos
│     ├ Lote 3 → +30 segundos
│     └ ...
│
├─ 00:05:00 → VerifyFinishedMatchesHourlyJob (5 minutos DESPUÉS)
│  │
│  ├─ 🔍 Busca partidos:
│  │  ├ status = 'FINISHED' o 'Match Finished'
│  │  └ Tienen preguntas sin verificar (result_verified_at = NULL)
│  │
│  ├─ 📦 Despacha BATCH en paralelo:
│  │  ├ BatchGetScoresJob     ← Obtiene scores finales
│  │  └ BatchExtractEventsJob ← Extrae eventos/stats
│  │
│  └─ 🎯 DESPUÉS (finally):
│     └─ VerifyAllQuestionsJob
│        ├─ Llama a Gemini para evaluar cada pregunta
│        ├─ Determina opción correcta
│        ├─ Marca preguntas como verificadas
│        └─ ASIGNA PUNTOS a usuarios
│
└─ 🏁 FIN: Usuarios ven puntos actualizados
```

---

## 🔴 PUNTOS CRÍTICOS DE FALLO

### 1️⃣ UpdateFinishedMatchesJob (Timeout/Queue Issues)
- **Entrada:** Partidos no finalizados en BD
- **Función:** Buscar scores vía API Football o Gemini
- **Salida:** Actualizar status + scores del partido
- **Posible Fallo:** 
  - ❌ Queue worker no ejecutando en producción
  - ❌ ProcessMatchBatchJob nunca se ejecuta
  - ❌ API Football sin permisos de suscripción
  - ❌ Gemini timeout sin manejo

### 2️⃣ ProcessMatchBatchJob (Execution Gap)
- **Entrada:** Array de match_ids, batchNumber
- **Función:** Llamar API Football → Si falla: Gemini fallback
- **Salida:** Partido con status='FINISHED' + score actualizado
- **Posible Fallo:**
  - ❌ Job no ejecutarse por retraso de cola
  - ❌ API Football falla sin fallback a Gemini
  - ❌ Gemini timeout (está en `$tries = 3` pero puede exceder timeout global)

### 3️⃣ VerifyFinishedMatchesHourlyJob (Timing Gap)
- **Entrada:** Partidos con status='FINISHED' pero result_verified_at = NULL
- **Función:** Buscar preguntas sin verificar
- **Salida:** Despachar batch de jobs
- **Posible Fallo:**
  - ❌ Ejecutarse ANTES que UpdateFinishedMatchesJob terminen
  - ❌ No encontrar partidos porque aún no tienen status='FINISHED'
  - ⚠️ Timing: :00 vs :05 es apretado si hay lag

### 4️⃣ BatchGetScoresJob + BatchExtractEventsJob (Parallel Execution)
- **Entrada:** matchIds, verificationBatchId
- **Función:** Extraer scores + eventos/estadísticas
- **Salida:** FootballMatch con eventos/stats completos
- **Posible Fallo:**
  - ❌ Gemini timeout en batch (está en `$tries = 1` ← ¡PROBLEMA!)
  - ❌ Rate limiting en Gemini

### 5️⃣ VerifyAllQuestionsJob (Points Assignment - CRÍTICO)
- **Entrada:** matchIds, batchId (después de batch.finally)
- **Función:** Para cada pregunta:
  1. Obtener respuestas de usuarios
  2. Evaluar pregunta con Gemini
  3. Determinar opción correcta
  4. **ASIGNAR PUNTOS** a Answer model
- **Salida:** Usuarios con puntos actualizados
- **Posible Fallo:**
  - ❌ Nunca se ejecuta (batch failed)
  - ❌ Gemini timeout sin manejo
  - ❌ Lógica de asignación de puntos incorrecta
  - ❌ No persiste en BD

---

## 🔍 ANÁLISIS TÉCNICO DETALLADO

### Job 1: UpdateFinishedMatchesJob

**Ubicación:** [app/Jobs/UpdateFinishedMatchesJob.php](app/Jobs/UpdateFinishedMatchesJob.php)

**Lógica:**
```php
// Busca partidos "abandonados" (deberían haber terminado)
$finishedMatches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
    ->where('date', '<=', now()->subHours(2))  // ← Partidos hace 2+ horas
    ->where('date', '>=', now()->subHours($hoursBack))  // ← Últimas 24h (prod) o 72h (dev)
    ->pluck('id');

// Divide en lotes de 5
$batches = array_chunk($finishedMatches, 5);

// Despacha con delays
foreach ($batches as $batchNumber => $batch) {
    ProcessMatchBatchJob::dispatch($batch, $batchNumber + 1)
        ->delay(now()->addSeconds(($batchNumber + 1) * 10));
}
```

**⚠️ PROBLEMA IDENTIFICADO:**
- El job **NO verifica** que ProcessMatchBatchJob se ejecutó realmente
- Solo despacha a la cola y termina
- Si la cola tiene problemas, **nunca se entera**

**Solución:** Agregar logs + verificar que procesos se completaron

---

### Job 2: ProcessMatchBatchJob

**Ubicación:** [app/Jobs/ProcessMatchBatchJob.php](app/Jobs/ProcessMatchBatchJob.php)

**Configuración:**
```php
public $timeout = 120;  // 2 minutos por lote
public $tries = 3;      // Reintentos
```

**Lógica:**
```php
foreach ($matches as $match) {
    // PASO 1: Intentar API Football
    $updatedMatch = $footballService->updateMatchFromApi($match->id);
    
    if ($updatedMatch) {
        continue;  // ← ÉXITO, siguiente partido
    }
    
    // PASO 2: Si API falla, intentar Gemini
    $geminiResult = $geminiService->getMatchResult(...);
    
    if ($geminiResult) {
        // Setear score desde Gemini
        $match->update([...]);
    }
}
```

**⚠️ PROBLEMAS POTENCIALES:**
1. **Timeout en Gemini:** Si Gemini tarda >120s, el job falla
2. **No hay verificación de estado:** No se verifica que `$match->update()` funcionó
3. **Gemini tarda:** Especialmente con web search grounding

---

### Job 3: VerifyFinishedMatchesHourlyJob

**Ubicación:** [app/Jobs/VerifyFinishedMatchesHourlyJob.php](app/Jobs/VerifyFinishedMatchesHourlyJob.php)

**Búsqueda de candidatos:**
```php
$candidates = FootballMatch::query()
    ->withCount(['questions as pending_questions_count' => function ($query) {
        $query->whereNull('result_verified_at');
    }])
    ->whereIn('status', ['Match Finished', 'FINISHED'])  // ← DEBE estar FINISHED
    ->where('date', '>=', $windowStart)
    ->whereHas('questions', function ($query) {
        $query->whereNull('result_verified_at');
    })
    ->limit($this->maxMatches * 3)
    ->get();
```

**🔴 PROBLEMA CRÍTICO:**
- Espera `status = 'FINISHED'`
- Pero si ProcessMatchBatchJob falló, el status seguirá siendo "Not Started"
- **RESULTADO:** No encuentra candidatos → No hay verificación

**Timing Issue:**
```
:00 → UpdateFinishedMatchesJob (despacha a cola)
:05 → VerifyFinishedMatchesHourlyJob (ejecuta)
     ↓
     ¿ProcessMatchBatchJob completó en 5 minutos?
     Si lotes están con delays (10s, 20s, etc) → Probablemente SÍ
     Pero si hay lag en queue → Probablemente NO
```

**Flujo de Batch:**
```php
Bus::batch([
    new BatchGetScoresJob($matchIds, $batchId),
    new BatchExtractEventsJob($matchIds, $batchId),
])
    ->catch(function (Batch $batch, Throwable $exception) {
        Log::error('Batch error', ['error' => $exception->getMessage()]);
    })
    ->finally(function (Batch $batch) use ($matchIds, $batchId) {
        // ← AQUÍ SE DISPARA VerifyAllQuestionsJob
        dispatch(new VerifyAllQuestionsJob($matchIds, $batchId));
    })
    ->dispatch();
```

**⚠️ PROBLEMA:**
- `->finally()` se ejecuta **incluso si batch falló**
- Pero `VerifyAllQuestionsJob` intenta verificar preguntas sobre scores que podrían no existir

---

### Job 4 & 5: BatchGetScoresJob + BatchExtractEventsJob

**BatchGetScoresJob:**
```php
public $timeout = 600;  // 10 minutos
public $tries = 1;      // ← ¡SOLO 1 REINTENTO!

// En handle():
foreach ($matches as $match) {
    $updatedMatch = $footballService->updateMatchFromApi($match->id);
    if (!$updatedMatch) {
        $pendingForGemini[] = $match;  // ← Para Gemini
    }
}

// Luego:
if (!empty($pendingForGemini)) {
    $geminiBatchService->evaluateMatches($pendingForGemini);  // ← Gemini batch
}
```

**🔴 PROBLEMA CRÍTICO:**
- `$tries = 1` significa **sin reintentos**
- Si Gemini timeout en Gemini batch → El job falla completamente
- No hay fallback

---

### Job 5: VerifyAllQuestionsJob

**Ubicación:** [app/Jobs/VerifyAllQuestionsJob.php](app/Jobs/VerifyAllQuestionsJob.php) (Necesito leer)

**Función esperada:**
```
Para cada pregunta del partido:
1. Obtener respuestas de usuarios
2. Llamar a Gemini con contexto del partido
3. Determinar opción correcta
4. Comparar con respuestas de usuarios
5. ← ASIGNAR PUNTOS (AQUÍ FALLA)
6. Marcar pregunta como verificada
```

**⚠️ PROBLEMA PROBABLE:**
- Gemini puede timeout aquí también
- Si Gemini no responde → No asigna puntos → Usuario ve puntos = 0

---

## 📋 Checklist de Causas Probables

- [ ] Queue worker no está corriendo en producción (`php artisan queue:work`)
- [ ] UpdateFinishedMatchesJob se ejecuta pero ProcessMatchBatchJob nunca se despacha
- [ ] ProcessMatchBatchJob falla por Gemini timeout (>120s)
- [ ] VerifyFinishedMatchesHourlyJob :05 se ejecuta antes que UpdateFinishedMatchesJob :00 complete
- [ ] BatchGetScoresJob falla por Gemini (tries=1, sin reintentos)
- [ ] VerifyAllQuestionsJob nunca se ejecuta (batch falló, finally no disparó)
- [ ] VerifyAllQuestionsJob se ejecuta pero Gemini timeout en verificación
- [ ] Asignación de puntos falla en BD (transaction rollback)
- [ ] Permisos API Football insuficientes (requiere suscripción pagada)

---

## 🔧 Próximos Pasos

1. **LEER:** VerifyAllQuestionsJob.php (punto 5 del flujo)
2. **REVISAR:** Logs de producción para identificar dónde falla
3. **IMPLEMENTAR:** Mejoras en manejo de timeouts + reintentos
4. **AGREGAR:** Monitoreo/alertas para cada paso del flujo
5. **TESTING:** Simular fallo de cada componente

