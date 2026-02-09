# Flujo Automático de Actualización y Verificación de Partidos

## 📋 Resumen

Tu sistema ahora tiene un **pipeline de 2 pasos cada hora** que funciona así:

```
HORA:00 → UpdateFinishedMatchesJob (Actualizar status + scores)
         ↓
HORA:05 → VerifyFinishedMatchesHourlyJob (Verificar respuestas)
```

---

## 🔄 Flujo Detallado

### **PASO 1: Actualizar Status de Partidos** (`:00` de cada hora)
**Job:** `UpdateFinishedMatchesJob`
**Archivo:** `app/Jobs/UpdateFinishedMatchesJob.php`

```
1. Busca partidos que deberían estar terminados:
   - Status actual: "Not Started" (sin verificar)
   - Fecha: hace más de 2 horas (+ 2h margin)
   - Rango de búsqueda: últimas 24-72 horas según APP_ENV

2. Para cada partido encontrado:
   
   a) Intenta obtener score desde API Football:
      ✅ SI: Actualiza status → "Match Finished" + scores
      ❌ NO: Continúa al paso b)
   
   b) Intenta obtener score desde Gemini (web search con grounding):
      ✅ SI: Actualiza status → "Match Finished" + scores
      ❌ NO: Partido NO se actualiza (política verified-only)

3. Resultado: Partidos con status "Match Finished" listos para verificación
```

### **PASO 2: Verificar Respuestas** (`:05` de cada hora)
**Job:** `VerifyFinishedMatchesHourlyJob`
**Archivo:** `app/Jobs/VerifyFinishedMatchesHourlyJob.php`

```
1. Busca partidos con status "Match Finished" que tengan preguntas sin verificar:
   - Últimas 12 horas (configurable)
   - Con preguntas donde result_verified_at IS NULL

2. Prioriza por:
   - Partidos actualizados hace < 30 min → Prioridad 1
   - Partidos con ≥ 5 preguntas sin verificar → Prioridad 2
   - Otros → Prioridad 3

3. Dispara un batch de jobs:
   
   a) BatchGetScoresJob:
      - Obtiene score final desde API Football
      - Si no disponible, usa Gemini
   
   b) BatchExtractEventsJob:
      - Extrae eventos detallados del partido (goles, tarjetas, etc.)
      - Usa Gemini con grounding para estructura JSON
   
   c) VerifyAllQuestionsJob (`.finally()` = se ejecuta SIEMPRE):
      - Evalúa cada pregunta contra los datos obtenidos
      - Actualiza: is_correct, points_earned
      - Marca: result_verified_at = now()

4. Cooldown: Los partidos no se reintentan antes de 5 minutos
   - Esto evita verificaciones repetidas muy rápido
```

---

## 🔧 Configuración

### Scheduler
```php
// app/Console/Kernel.php

// 1️⃣ Cada hora a :00
$schedule->job(new UpdateFinishedMatchesJob())
    ->hourly()
    ->name('update-finished-matches');

// 2️⃣ Cada hora a :05 (5 minutos después)
$schedule->job(new VerifyFinishedMatchesHourlyJob())
    ->hourly()
    ->at(':05')
    ->name('verify-matches-hourly');
```

### Para Ejecutar el Scheduler
```bash
# Terminal 1: Ejecutar el scheduler
php artisan schedule:work

# Terminal 2: Ejecutar los jobs en la cola
php artisan queue:work
```

---

## 🎯 Parámetros Personalizables

### `UpdateFinishedMatchesJob`
```php
// Búsqueda en últimas N horas
$hoursBack = env('APP_ENV') === 'production' ? 24 : 72;

// Margen de tiempo después de la fecha programada
$where('date', '<=', now()->subHours(2))
```

### `VerifyFinishedMatchesHourlyJob`
```php
// Constructor con parámetros
new VerifyFinishedMatchesHourlyJob(
    maxMatches: 30,          // Partidos a procesar por hora
    windowHours: 12,         // Buscar partidos de últimas N horas
    cooldownMinutes: 5       // Esperar N minutos antes de reintentar
)

// Uso en scheduler
$schedule->job(new VerifyFinishedMatchesHourlyJob(30, 12, 5))
    ->hourly();
```

---

## 📊 Ejemplo de Ejecución (Hora Actual: 14:00)

```
14:00:00 → UpdateFinishedMatchesJob INICIA
           - Busca partidos de hace 2-72 horas
           - Encuentra: Match #1 (Barca vs Real), Match #2 (Atleti vs Sevilla)
           - API retorna score para Match #1 → Status = "Match Finished"
           - Gemini retorna score para Match #2 → Status = "Match Finished"
           - LOG: "Dispatched 2 batches with 1 match each"
           
14:01:00 → ProcessMatchBatchJob EJECUTA en la cola
           - Actualiza Match #1 con datos de API
           - Actualiza Match #2 con datos de Gemini
           - LOG: "✅ 2 matches updated from verified sources"

14:05:00 → VerifyFinishedMatchesHourlyJob INICIA
           - Busca: status="Match Finished" + preguntas sin verificar
           - Encuentra: Match #1 y Match #2 (con preguntas pendientes)
           - Dispara:
             * BatchGetScoresJob([1,2])
             * BatchExtractEventsJob([1,2])
             * VerifyAllQuestionsJob([1,2]) [se ejecuta después]
           
14:06:00 → BatchGetScoresJob + BatchExtractEventsJob EN PARALELO
           - Job 1: Obtiene score final de cada partido
           - Job 2: Extrae eventos (goles, tarjetas, substituciones, etc.)
           
14:07:00 → VerifyAllQuestionsJob INICIA (gracias al .finally())
           - Procesa preguntas por chunks de 50
           - Evalúa cada pregunta: ¿cuál es la respuesta correcta?
           - Actualiza: options.is_correct, answers.is_correct, answers.points_earned
           - LOG: "Question #45 verified: 3 answers updated, 2 points earned"

15:00:00 → UpdateFinishedMatchesJob EJECUTA DE NUEVO (siguiente hora)
           - Busca nuevos partidos terminados
           - ...ciclo se repite
```

---

## ⚙️ Cómo Cambia el Status

```
Inicial: status = "Not Started"
              ↓
         [UpdateFinishedMatchesJob ejecuta]
              ↓
       Status = "Match Finished"
         ✅ Score actualizado
         ✅ Events opcionalmente enriquecido
              ↓
     [VerifyFinishedMatchesHourlyJob ejecuta]
              ↓
  Preguntas verificadas (result_verified_at = now())
  Puntos asignados correctamente
```

---

## 🚀 Cambios Realizados

1. ✅ **Agregado**: `UpdateFinishedMatchesJob` al scheduler (`:00` cada hora)
2. ✅ **Modificado**: `VerifyFinishedMatchesHourlyJob` para ejecutarse a `:05`
3. ✅ **Cambiado**: Batch usa `.finally()` para SIEMPRE verificar preguntas
4. ✅ **Reducido**: Cooldown de 30 min → 5 min (para reintentos más rápidos)
5. ✅ **Agregado**: Import de `UpdateFinishedMatchesJob` en Kernel.php

---

## 🧪 Testing Manual

```bash
# Simular UpdateFinishedMatchesJob ahora mismo
php artisan tinker
>>> use App\Jobs\UpdateFinishedMatchesJob;
>>> UpdateFinishedMatchesJob::dispatch();

# Simular VerifyFinishedMatchesHourlyJob con parámetros personalizados
>>> use App\Jobs\VerifyFinishedMatchesHourlyJob;
>>> VerifyFinishedMatchesHourlyJob::dispatch(maxMatches: 5, windowHours: 24);

# Ver jobs en la cola
>>> php artisan queue:work --max-jobs=10
```

---

## 📝 Notas Importantes

- **Timing**: Los jobs están configurados con `withoutOverlapping()` para evitar solapamientos
- **Timeout**: UpdateFinishedMatches = 5 min, VerifyFinished = 15 min
- **Reintentos**: UpdateFinished = 3 reintentos, VerifyFinished = 1 intento
- **Grounding**: Ambos jobs usan **Google Gemini con web search** para obtener datos verificados
- **Data Policy**: Solo actualiza con datos de API Football o Gemini verificados, **NUNCA datos ficticios**

