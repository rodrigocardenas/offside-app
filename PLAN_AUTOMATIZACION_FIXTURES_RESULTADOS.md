# 📋 PLAN DE TRABAJO - AUTOMATIZACIÓN DE FIXTURES Y RESULTADOS

**Fecha de creación:** 8 de enero de 2026  
**Estado:** En diseño  
**Prioridad:** Alta

---

## 🎯 OBJETIVO GENERAL

Automatizar el ciclo completo de predicciones:
1. **Noche:** Descargar nuevos partidos (fixtures)
2. **Al abrir grupo:** Generar preguntas
3. **Cada hora:** Actualizar resultados y evaluar respuestas
4. **Job en Queue:** Procesar puntuaciones

---

## 📊 FLUJO ARQUITECTÓNICO

```
┌─────────────────────────────────────────────────────┐
│         NOCHE (23:00 - Cron Job)                    │
│  UpdateFixturesCommand                              │
│  ├─ Obtener La Liga, Premier, Champions, Serie A   │
│  ├─ Football-Data.org API                          │
│  └─ Almacenar en BD (football_matches)              │
└────────────────┬────────────────────────────────────┘
                 │
         ✅ Fixtures listos
                 │
┌────────────────▼────────────────────────────────────┐
│      USUARIO: Carga Show del Grupo                  │
│  GenerateQuestionsService                           │
│  ├─ Obtiene partidos próximos (7 días)             │
│  ├─ Genera preguntas (Plantillas + IA)             │
│  └─ Almacena asociadas a matches                   │
└────────────────┬────────────────────────────────────┘
                 │
         ✅ Preguntas generadas
                 │
┌────────────────▼────────────────────────────────────┐
│      CADA HORA (Cron Job)                           │
│  UpdateMatchResultsCommand                          │
│  ├─ Obtener resultados de Football-Data.org        │
│  ├─ Si hay cambio de estado:                        │
│  │  ├─ Actualizar score, eventos, stats            │
│  │  └─ ENCOLAR: ProcessQuestionResultsJob          │
│  └─ Guardar eventos (goles, tarjetas, cambios)     │
└────────────────┬────────────────────────────────────┘
                 │
         ✅ Resultado disponible
                 │
┌────────────────▼────────────────────────────────────┐
│     QUEUE: ProcessQuestionResultsJob                │
│  ├─ Obtener preguntas asociadas al match           │
│  ├─ Para cada pregunta:                             │
│  │  ├─ Determinar opción correcta                   │
│  │  ├─ Evaluar respuestas de usuarios               │
│  │  ├─ Asignar puntos (Answer.points_earned)       │
│  │  └─ Actualizar rankings del grupo               │
│  └─ Marcar preguntas como verificadas              │
└────────────────┬────────────────────────────────────┘
                 │
         ✅ Puntuaciones actualizadas
                 │
        Usuarios ven sus resultados
```

---

## 📦 FASES DE IMPLEMENTACIÓN (REVISADAS)

> **IMPORTANTE:** 70% de la funcionalidad YA EXISTE.  
> Este plan describe REFACTORES y CREACIONES MÍNIMAS.

### FASE 1: Obtener Fixtures (REFACTOR)
**Objetivo:** Cambiar de Gemini → Football-Data.org

#### 1.1 Refactor UpdateFootballData.php
- [ ] Cambiar de `FootballService::getNextMatches()` (Gemini)
- [ ] A: `FootballDataService::getMatchesByCompetition()` (Real)
- [ ] Mantener estructura `updateOrCreate()`
- [ ] Mantener logging

#### 1.2 Crear UpdateFixturesNightly.php
- [ ] Comando: `app:update-fixtures-nightly`
- [ ] Obtener: La Liga, Premier, Champions, Serie A
- [ ] Ejecutar: 23:00 cada noche
- [ ] Usar mismo patrón que UpdateFootballData

#### 1.3 Registrar en Kernel
- [ ] Agregar a `schedule()` en `console/Kernel.php`
- [ ] `$schedule->command('app:update-fixtures-nightly')->dailyAt('23:00')`

**Archivos a modificar:**
```
app/Console/Commands/UpdateFootballData.php [REFACTOR]
app/Console/Commands/UpdateFixturesNightly.php [CREAR]
app/Console/Kernel.php [ACTUALIZAR]
```

---

### FASE 2: Evaluar Preguntas (REFACTOR)
**Objetivo:** Cambiar de OpenAI → Lógica Determinística

#### 2.1 Crear QuestionEvaluationService
- [ ] `determineCorrectOption(Question $q, FootballMatch $m)` - Retorna option ID
- [ ] Tipos de preguntas:
  - `"winner"` - Comparar scores
  - `"first_goal"` - ¿Hay goles?
  - `"goals_over_under"` - Contar total
  - `"both_teams_score"` - Ambos > 0
  - `"exact_score"` - Match exacto
  - `"social"` - Retornar null (no evaluar)

#### 2.2 Lógica por Tipo
```php
case 'winner':
    return $match->home_team_score > $match->away_team_score ? 'home_wins'
         : ($match->away_team_score > $match->home_team_score ? 'away_wins'
         : 'draw');

case 'first_goal':
    return isset($match->events['goals']) && count($match->events['goals']) > 0
         ? $match->events['goals'][0]['player'] : null;

case 'both_teams_score':
    return $match->home_team_score > 0 && $match->away_team_score > 0
         ? 'yes' : 'no';
```

#### 2.3 Refactor VerifyQuestionResultsJob
- [ ] Reemplazar `$openAIService->verifyMatchResults()`
- [ ] Con `QuestionEvaluationService::determineCorrectOption()`
- [ ] Mantener estructura de bucles y Answer updates
- [ ] Misma funcionalidad, sin OpenAI

**Archivos a modificar:**
```
app/Services/QuestionEvaluationService.php [CREAR]
app/Jobs/VerifyQuestionResultsJob.php [REFACTOR - cambio interno]
```

---

### FASE 3: Actualizar Kernel (COMPLETAR)
**Objetivo:** Tener ambos schedulers funcionando

#### 3.1 Kernel.php
```php
// Obtener fixtures cada noche
$schedule->command('app:update-fixtures-nightly')
    ->dailyAt('23:00')
    ->onFailure(fn() => Log::error('Error updating fixtures'));

// Actualizar resultados cada hora
$schedule->command('matches:process-recently-finished')
    ->hourly()
    ->onFailure(fn() => Log::error('Error processing matches'));
```

**Archivos a modificar:**
```
app/Console/Kernel.php [ACTUALIZAR]
```

---

### FASE 4: Testing & Validación
**Objetivo:** Asegurar que todo funciona

#### 4.1 Test UpdateFootballData con Football-Data.org
- [ ] Ejecutar manual: `php artisan app:update-football-data`
- [ ] Verificar que usa Football-Data.org (no Gemini)
- [ ] Verificar que guarda en BD correctamente

#### 4.2 Test UpdateFixturesNightly
- [ ] Ejecutar: `php artisan app:update-fixtures-nightly`
- [ ] Verificar 4 ligas importadas
- [ ] Verificar timestamps

#### 4.3 Test QuestionEvaluationService
- [ ] Crear match FINISHED con scores 2-1
- [ ] Crear pregunta tipo "winner"
- [ ] Evaluar → debe retornar "home_wins"

#### 4.4 Test VerifyQuestionResultsJob
- [ ] Ejecutar job manualmente
- [ ] Verificar que NO llama OpenAI
- [ ] Verificar que Answer.is_correct se actualiza
- [ ] Verificar que points_earned se calcula

#### 4.5 Test Schedule
- [ ] Monitorear cron jobs en próximas horas
- [ ] Ver que UpdateFixturesNightly corre a las 23:00
- [ ] Ver que ProcessRecentlyFinishedMatches corre cada hora

**Archivos a crear:**
```
tests/Unit/Services/QuestionEvaluationServiceTest.php
tests/Feature/Jobs/VerifyQuestionResultsJobTest.php
tests/Feature/Commands/UpdateFixturesNightlyTest.php
```

---

## 🗂️ ARCHIVOS A CREAR/MODIFICAR (REVISADOS)

### CREAR

```
app/
├─ Services/
│  └─ QuestionEvaluationService.php         [FASE 2]
│
└─ Console/
   └─ Commands/
      └─ UpdateFixturesNightly.php          [FASE 1]

tests/
├─ Unit/
│  └─ Services/
│     └─ QuestionEvaluationServiceTest.php  [FASE 4]
└─ Feature/
   ├─ Jobs/
   │  └─ VerifyQuestionResultsJobTest.php   [FASE 4]
   └─ Commands/
      └─ UpdateFixturesNightlyTest.php      [FASE 4]
```

### MODIFICAR

```
app/
├─ Console/
│  ├─ Commands/
│  │  └─ UpdateFootballData.php             [FASE 1] - Cambiar Gemini → Football-Data.org
│  └─ Kernel.php                            [FASE 3] - Agregar schedule nocturno
│
└─ Jobs/
   └─ VerifyQuestionResultsJob.php          [FASE 2] - Cambiar OpenAI → QuestionEvaluationService
```

### NO MODIFICAR

```
✅ app/Jobs/UpdateFinishedMatchesJob.php
✅ app/Jobs/ProcessMatchBatchJob.php
✅ app/Jobs/UpdateAnswersPoints.php
✅ app/Jobs/CreatePredictiveQuestionsJob.php
✅ app/Jobs/ProcessRecentlyFinishedMatchesJob.php
✅ app/Console/Commands/ProcessRecentlyFinishedMatches.php
✅ app/Traits/HandlesQuestions.php (generación de preguntas)
```

---

## 📋 DETALLE POR FASE (REVISADO)

### FASE 1: Fixtures (REFACTOR UpdateFootballData.php)

**UpdateFootballData.php (MODIFICADO)**
```php
// ANTES: Usa Gemini
$matches = $footballService->getNextMatches($league, 1);

// AHORA: Usa Football-Data.org
$matches = $footballDataService->getMatchesByCompetition($league);
```

**UpdateFixturesNightly.php (NUEVO)**
```php
// Ejecución: 23:00 cada noche
// Lógica:
1. Para cada liga (La Liga, Premier, Champions, Serie A)
   - Llamar Football-Data.org
   - Guardar con updateOrCreate
2. Log de cantidad importada
```

---

### FASE 2: Evaluación (REFACTOR VerifyQuestionResultsJob.php)

**ANTES: Usa OpenAI**
```php
$correctAnswers = $openAIService->verifyMatchResults($matchData, $questionData);
```

**AHORA: Usa lógica determinística**
```php
$correctOption = $questionEvaluationService->determineCorrectOption($question, $match);
```

**QuestionEvaluationService.php (NUEVO)**
```php
public function determineCorrectOption(Question $q, FootballMatch $m): ?QuestionOption
{
    // Lógica determinística según tipo de pregunta
    // Retorna la opción correcta basada en datos del match
}
```

---

### FASE 3: Kernel (ACTUALIZAR schedule)

**Kernel.php (MODIFICADO)**
```php
protected function schedule(Schedule $schedule): void
{
    // Obtener fixtures cada noche
    $schedule->command('app:update-fixtures-nightly')
        ->dailyAt('23:00')
        ->onFailure(fn() => Log::error('Error updating fixtures'));

    // Actualizar resultados cada hora
    $schedule->command('matches:process-recently-finished')
        ->hourly()
        ->onFailure(fn() => Log::error('Error processing matches'));
}
```

---

### FASE 4: Testing

Test para cada componente nuevo/modificado.



---

## ✅ CHECKLIST DE IMPLEMENTACIÓN (REVISADO)

### FASE 1: Refactor Fixtures
- [ ] Refactor UpdateFootballData.php (Gemini → Football-Data.org)
- [ ] Crear UpdateFixturesNightly.php command
- [ ] Registrar en Kernel schedule (23:00)
- [ ] Test manual: obtener fixtures de 4 ligas
- [ ] Verificar en BD que se guardaron

### FASE 2: Refactor Evaluación
- [ ] Crear QuestionEvaluationService con todos los tipos de preguntas
- [ ] Refactor VerifyQuestionResultsJob (OpenAI → QuestionEvaluationService)
- [ ] Verificar que Answer.is_correct se actualiza
- [ ] Verificar que points_earned se calcula correctamente

### FASE 3: Actualizar Scheduler
- [ ] Verificar UpdateFixturesNightly en schedule (23:00)
- [ ] Verificar ProcessRecentlyFinishedMatches en schedule (hourly)
- [ ] Test de ejecución

### FASE 4: Tests
- [ ] Test QuestionEvaluationService
- [ ] Test VerifyQuestionResultsJob refactored
- [ ] Test UpdateFixturesNightly command
- [ ] Test schedule en Kernel

---

## 🔄 DEPENDENCIAS ENTRE FASES (REVISADO)

```
FASE 1: Fixtures
  ↓ (proporciona datos)
FASE 2: Evaluación (cambio interno)
  ↓ (actualiza preguntas)
FASE 3: Kernel scheduler
  ↓
FASE 4: Tests y validación
```

**Notas:**
- FASE 1 es independiente (obtiene nuevos fixtures)
- FASE 2 depende de FASE 1 (tiene datos para evaluar)
- FASE 3 solo registra los crons
- FASE 4 valida todo funciona
- NO HAY NUEVA LÓGICA DE JOBS (ya existen)

---

## 🚀 ORDEN RECOMENDADO DE DESARROLLO (REVISADO)

1. **FASE 1** - Cambiar Gemini → Football-Data.org en fixtures
2. **FASE 2** - Cambiar OpenAI → Lógica determinística en evaluación
3. **FASE 3** - Registrar ambos crons en Kernel
4. **FASE 4** - Validar con tests

---

## 📝 NOTAS IMPORTANTES (REVISADAS)

### Sobre Estructura Existente
- ✅ 70% del código YA EXISTE y FUNCIONA
- ✅ UpdateFinishedMatchesJob ya procesa lotes
- ✅ CreatePredictiveQuestionsJob ya genera preguntas
- ✅ ProcessRecentlyFinishedMatchesJob ya orquesta todo
- ❌ Solo necesita: Fixtures de Football-Data.org + Evaluación determinística

### Sobre Fuentes de Datos
- ✅ Football-Data.org API funciona perfecto para fixtures/resultados
- ✅ Para detalles complejos (Gemini) puede agregarse en futuro
- ✅ Actualmente: scores básicos es suficiente

### Sobre Performance
- ✅ Batch processing de 5 partidos (ya implementado)
- ✅ Delays entre requests (2s entre partidos)
- ✅ Delays entre jobs (5min entre cada fase)
- ✅ Cron cada hora es razonable

### Sobre Costos
- ✅ Football-Data.org: GRATIS en tiempos de clase
- ❌ NO usaremos OpenAI para evaluación (innecesario)
- ✅ Evaluación es determinística (sin IA)

---

## 📞 SIGUIENTES PASOS (REVISADOS)

1. ✅ Análisis completado
2. ✅ Plan revisado (4 fases simplificadas)
3. ⬜ Comenzar FASE 1: Refactor UpdateFootballData.php + Crear UpdateFixturesNightly.php
4. ⬜ Continuar FASE 2: Crear QuestionEvaluationService + Refactor VerifyQuestionResultsJob
5. ⬜ Completar FASE 3: Actualizar Kernel schedule
6. ⬜ Validar FASE 4: Tests

**¿Procedemos con FASE 1?**
