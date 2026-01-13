# 🚨 DIAGNÓSTICO: Investigación de Bloqueo del Servidor en Producción

**Fecha:** 13 de Enero de 2026  
**Entorno:** AWS EC2 (Ubuntu, Nginx, PHP)  
**Problema:** Servidor pegado/bloqueado en producción

---

## 📋 RESUMEN EJECUTIVO

El servidor en producción **SÍ está siendo bloqueado** por el proceso que se ejecuta cada hora. El problema está en el **comando `matches:process-recently-finished`** que ejecuta una cadena de 3 jobs pesados de forma síncrona en la cola.

### 🔴 CRÍTICO: El Cuello de Botella

```
Cada HORA:
ProcessRecentlyFinishedMatchesJob (10 min timeout)
├── UpdateFinishedMatchesJob (5 min timeout)
│   ├── Consulta BD: Busca partidos que terminaron hace 2-24 horas
│   ├── Los divide en lotes de 5 partidos
│   ├── Para CADA lote: 2 segundos de delay (sleep() bloqueante)
│   └── Hace CALLS SÍNCRONOS a footballService->updateMatchFromApi()
│
├── VerifyQuestionResultsJob (5 min timeout)
│   ├── Busca TODAS las preguntas pendientes de partidos terminados
│   ├── Para CADA pregunta: evaluación + actualización DB
│   └── Procesa todas sequencialmente
│
└── CreatePredictiveQuestionsJob (5 min timeout)
    ├── Itera TODOS los grupos con competición
    ├── Para CADA grupo: fillGroupPredictiveQuestions() 
    └── Genera nuevas preguntas + notificaciones push
```

---

## 🔍 DETALLES TÉCNICOS DEL PROBLEMA

### 1. **El Comando Cada Hora** (SÍNCRONO)

**Archivo:** `app/Console/Commands/ProcessRecentlyFinishedMatches.php`

```php
protected $signature = 'matches:process-recently-finished';

public function handle()
{
    ProcessRecentlyFinishedMatchesJob::dispatch();
    // ← Este dispatch es SÍNCRONO porque no hay cola configurada correctamente
}
```

**Problema:** Si `QUEUE_CONNECTION=sync` en producción, **BLOQUEA todo**. Cada comando ejecuta los 3 jobs secuencialmente.

---

### 2. **UpdateFinishedMatchesJob - BLOQUEA CON SLEEP()**

**Archivo:** `app/Jobs/UpdateFinishedMatchesJob.php` (líneas 36-63)

```php
foreach ($batches as $batchNumber => $batch) {
    foreach ($matches as $index => $match) {
        try {
            if ($index > 0) {
                $delaySeconds = 2;
                sleep($delaySeconds); // ← 🔴 BLOQUEA LA COLA COMPLETA
            }
            
            // Llamada SÍNCRONA a API externa
            $updatedMatch = $footballService->updateMatchFromApi($match->id);
        } catch (\Exception $e) {
            // ...
        }
    }
}
```

**Problemas:**
- ❌ `sleep()` bloquea el worker PHP
- ❌ Si hay 10 partidos: 10 × 2 seg = 20 segundos de bloqueo
- ❌ `updateMatchFromApi()` hace HTTP calls externas (timeout risk)
- ❌ Timeout configurado: 300 segundos (5 min) - puede NO ser suficiente

---

### 3. **VerifyQuestionResultsJob - CONSULTAS PESADAS**

**Archivo:** `app/Jobs/VerifyQuestionResultsJob.php` (líneas 25-42)

```php
$pendingQuestions = Question::whereNull('result_verified_at')
    ->whereHas('football_match', function($query) {
        $query->whereIn('status', ['FINISHED', 'Match Finished']);
    })
    ->with('football_match', 'options', 'answers') // ← Eager loading
    ->get(); // ← Carga TODAS en memoria

foreach ($pendingQuestions as $question) {
    // Para CADA pregunta y CADA respuesta:
    foreach ($question->answers as $answer) {
        $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
        $answer->points_earned = $answer->is_correct ? $question->points ?? 300 : 0;
        $answer->save(); // ← Update individual (N queries)
    }
}
```

**Problemas:**
- ❌ Carga TODAS las preguntas en memoria (si hay 10K preguntas = 10K × 10 respuestas = 100K objetos)
- ❌ Updates individuales en loop (N+1 query problem)
- ❌ Sin índices en `result_verified_at` ni caché

---

### 4. **CreatePredictiveQuestionsJob - ITERACIONES INFINITAS**

**Archivo:** `app/Jobs/CreatePredictiveQuestionsJob.php` (líneas 19-45)

```php
$groups = Group::with('competition')
    ->whereNotNull('competition_id')
    ->get(); // ← Todos los grupos

foreach ($groups as $group) {
    $activeCount = $group->questions()
        ->where('type', 'predictive')
        ->where('available_until', '>', now())
        ->count(); // ← Query #1

    if ($activeCount < 5) {
        $allQuestions = $this->fillGroupPredictiveQuestions($group); // ← Operación pesada
        
        SendNewPredictiveQuestionsPushNotification::dispatch($group->id, $newQuestionsCount);
    }
}
```

**Problemas:**
- ❌ Itera TODOS los grupos (sin paginación)
- ❌ `fillGroupPredictiveQuestions()` puede hacer muchas queries
- ❌ Si hay 1000 grupos: 1000 queries + 1000 push notifications

---

## 📊 CHAIN REACTION DEL BLOQUEO

```
CADA HORA:
┌─ 23:00, 00:00, 01:00... 23:00 (CADA HORA)
│
├─ ProcessRecentlyFinishedMatches::handle()
│  ├─ DISPATCH ProcessRecentlyFinishedMatchesJob
│  │  ├─ TIMEOUT: 600 segundos (10 min)
│  │  │
│  │  ├─ UpdateFinishedMatchesJob  
│  │  │  ├─ TIMEOUT: 300 seg
│  │  │  ├─ SLEEP(): 2 × (número de partidos) segundos
│  │  │  ├─ API CALLS: En paralelo con sleep
│  │  │  └─ RESULTADO: Bloquea entre 20-120 segundos
│  │  │
│  │  ├─ VerifyQuestionResultsJob (delay +2 min)
│  │  │  ├─ TIMEOUT: 300 seg
│  │  │  ├─ MEMORY: Carga 10K+ preguntas
│  │  │  ├─ QUERIES: N+1 problem
│  │  │  └─ RESULTADO: Bloquea entre 30-180 segundos
│  │  │
│  │  └─ CreatePredictiveQuestionsJob (delay +5 min)
│  │     ├─ TIMEOUT: 300 seg
│  │     ├─ LOOP: 1000+ grupos
│  │     ├─ QUERIES: 1+ por grupo
│  │     └─ RESULTADO: Bloquea entre 60-300 segundos
│  │
│  └─ TOTAL: Hasta 10 minutos de bloqueo POR HORA


SI QUEUE_CONNECTION=sync EN PRODUCCIÓN:
└─ EL SERVIDOR SE BLOQUEA COMPLETAMENTE
   Todas las requests HTTP esperan a que termine
   El usuario ve: "504 Gateway Timeout" o "timeout connecting"
```

---

## 🔧 CONFIGURACIÓN ACTUAL

### Queue Configuration
**Archivo:** `config/queue.php`

```php
'default' => env('QUEUE_CONNECTION', 'sync'), // ← PELIGRO EN PRODUCCIÓN
```

**En producción probablemente está:**
```
QUEUE_CONNECTION=redis  // o database
```

### Scheduler Configuration
**Archivo:** `app/Console/Kernel.php`

```php
$schedule->command('matches:process-recently-finished')
    ->hourly()  // ← CADA HORA
    ->onFailure(function () {
        Log::error('Error en el procesamiento de partidos finalizados');
    });
```

---

## ✅ SOLUCIONES RECOMENDADAS

### **INMEDIATO (Producción):**

#### 1. **Cambiar de horario - Ejecutar en off-peak**
```php
$schedule->command('matches:process-recently-finished')
    ->dailyAt('03:00')  // En lugar de hourly
    ->timezone('America/Mexico_City');
```

#### 2. **Deshabilitar temporalmente**
```php
// Comentar o eliminar del scheduler
// $schedule->command('matches:process-recently-finished')->hourly();
```

#### 3. **Verificar configuración de cola en producción**
```bash
# En tu servidor de producción:
echo $QUEUE_CONNECTION  # Debe ser: redis, database, sqs
```

---

### **CORTO PLAZO (1-2 días):**

#### 4. **Eliminar sleep() bloqueante**
```php
// UpdateFinishedMatchesJob.php - ANTES:
foreach ($batches as $batchNumber => $batch) {
    foreach ($matches as $index => $match) {
        if ($index > 0) {
            sleep(2); // ← MALO
        }
    }
}

// DESPUÉS:
ProcessMatchBatchJob::dispatch($batch, $batchNumber + 1)
    ->delay($delay); // ← Usar delays de Laravel

// Y en ProcessMatchBatchJob, sin sleep():
foreach ($matches as $index => $match) {
    // Sin sleep() - Los delays están en queue
    $updatedMatch = $footballService->updateMatchFromApi($match->id);
}
```

#### 5. **Optimizar VerifyQuestionResultsJob - Usar chunking**
```php
// ANTES:
$pendingQuestions = Question::whereNull('result_verified_at')
    ->whereHas('football_match', ...)
    ->with('football_match', 'options', 'answers')
    ->get(); // ← Carga todo en memoria

// DESPUÉS:
Question::whereNull('result_verified_at')
    ->whereHas('football_match', ...)
    ->with('football_match', 'options', 'answers')
    ->chunk(100, function ($questions) { // ← Procesa de 100 en 100
        foreach ($questions as $question) {
            $evaluationService->evaluateQuestion($question, $question->football_match);
        }
    });
```

#### 6. **Optimizar CreatePredictiveQuestionsJob - Chunking y async**
```php
// ANTES:
$groups = Group::with('competition')->get(); // Todos

// DESPUÉS:
Group::with('competition')
    ->whereNotNull('competition_id')
    ->chunk(50, function ($groups) {
        foreach ($groups as $group) {
            $this->fillGroupPredictiveQuestions($group);
        }
    });
```

---

### **MEDIANO PLAZO (1-2 semanas):**

#### 7. **Reescribir los jobs como workers asincronos**
```php
// En lugar de despachar todos a la cola:
ProcessRecentlyFinishedMatchesJob::dispatch();  // Solo este

// El job debería SOLO despachar los otros en batches:
public function handle() {
    // Despachar UpdateFinishedMatchesJob en batches pequeños
    for ($i = 0; $i < 10; $i++) {
        UpdateFinishedMatchesJob::dispatch()
            ->delay(now()->addMinutes($i * 5)); // Max 1 cada 5 min
    }
}
```

#### 8. **Agregar índices a la BD**
```sql
ALTER TABLE questions ADD INDEX idx_result_verified_at (result_verified_at);
ALTER TABLE questions ADD INDEX idx_type_available (type, available_until);
ALTER TABLE answers ADD INDEX idx_is_correct (is_correct);
```

#### 9. **Implementar rate limiting en los jobs**
```php
// En ProcessRecentlyFinishedMatchesJob:
public function handle() {
    // Máximo 5 UpdateFinishedMatchesJob en paralelo
    $maxConcurrentJobs = 5;
    $existingJobs = app('queue')->connection()->peek('default', $maxConcurrentJobs * 2);
    
    if (count($existingJobs) < $maxConcurrentJobs) {
        UpdateFinishedMatchesJob::dispatch();
    }
}
```

---

## 🎯 PLAN DE ACCIÓN PARA HOY

1. **Verificar en producción:**
   ```bash
   ssh user@prod-server
   
   # Ver la configuración de cola
   grep QUEUE_CONNECTION .env
   
   # Ver los logs de los últimos bloqueos
   tail -f storage/logs/laravel.log | grep "ProcessRecentlyFinishedMatches"
   
   # Ver procesos PHP en ejecución
   ps aux | grep php-fpm
   ```

2. **Aplicar FIX inmediato:**
   - Cambiar el scheduler a ejecutar 1 vez al día (3:00 AM)
   - O comentar el comando

3. **Monitorear:**
   - Nginx access log a las 3:00 AM
   - `sudo tail -f /var/log/nginx/access.log`

---

## 📝 CHECKLIST DE VALIDACIÓN

- [ ] Verificar `QUEUE_CONNECTION` en `.env` de producción
- [ ] Revisar si hay workers Redis/queue ejecutándose: `queue:work`
- [ ] Revisar Supervisor config para el worker
- [ ] Revisar logs de laravel.log en últimos 24 horas
- [ ] Contar preguntas pendientes en BD: `SELECT COUNT(*) FROM questions WHERE result_verified_at IS NULL`
- [ ] Contar grupos activos: `SELECT COUNT(*) FROM groups WHERE competition_id IS NOT NULL`
- [ ] Ver estado actual de la cola: `artisan queue:failed`

---

## 📚 ARCHIVOS IMPLICADOS

| Archivo | Problema | Severidad |
|---------|----------|-----------|
| `app/Console/Kernel.php` | hourly en job pesado | 🔴 CRÍTICO |
| `app/Jobs/ProcessRecentlyFinishedMatchesJob.php` | Timeout 10 min | 🟠 ALTO |
| `app/Jobs/UpdateFinishedMatchesJob.php` | sleep() bloqueante | 🔴 CRÍTICO |
| `app/Jobs/VerifyQuestionResultsJob.php` | N+1 queries, memory | 🟠 ALTO |
| `app/Jobs/CreatePredictiveQuestionsJob.php` | Loop sin límite | 🟠 ALTO |
| `config/queue.php` | Config default sync | 🟠 ALTO |

---

## 🆘 PRÓXIMOS PASOS

¿Quieres que:
1. **Implemente las soluciones inmediatas?** (Cambiar scheduler, remover sleep)
2. **Verifique en el servidor de producción?** (Necesitarías SSH)
3. **Optimice todos los jobs ahora?** (Chunking, índices, etc)
4. **Cree un dashboard de monitoreo?** (Ver estados en tiempo real)
