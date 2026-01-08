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

## 📦 FASES DE IMPLEMENTACIÓN

### FASE 1: Comandos Cron (Scheduling)
**Objetivo:** Automatizar descarga de fixtures y resultados

#### 1.1 CreateScheduledCommandsJob
- [ ] `UpdateFixturesCommand` - Descargar fixtures noche
- [ ] `UpdateMatchResultsCommand` - Actualizar resultados cada hora
- [ ] Registrar en `schedule()` de `console/Kernel.php`

#### 1.2 Services de Football Data
- [ ] `FootballDataService::getCompetitionMatches()` - Obtener partidos
- [ ] `FootballDataService::getMatchDetails()` - Detalles completos
- [ ] Manejo de errores y retry logic

---

### FASE 2: Procesamiento de Datos
**Objetivo:** Guardar y procesar info de partidos

#### 2.1 Guardar Fixtures
- [ ] Validar que no exista (por external_id)
- [ ] Crear/Actualizar FootballMatch
- [ ] Asociar equipos correctamente
- [ ] Manejar transacciones

#### 2.2 Guardar Resultados
- [ ] Actualizar estado del match
- [ ] Guardar scores (home_team_score, away_team_score)
- [ ] Guardar eventos: goles, tarjetas, cambios
- [ ] JSON events + statistics columns

#### 2.3 Detección de Cambios
- [ ] Comparar estado anterior vs actual
- [ ] Sólo encolar job si hay cambio
- [ ] Evitar procesamiento duplicado

---

### FASE 3: Evaluación de Preguntas
**Objetivo:** Determinar respuestas correctas y calificar

#### 3.1 EvaluateQuestionAnswersService
- [ ] `determineCorrectOption(Question $q, Match $m)` - Lógica de evaluación
- [ ] Tipos de preguntas soportadas:
  - `"winner"` - ¿Quién gana? (1/X/2)
  - `"first_goal"` - ¿Quién hace primer gol?
  - `"goals_over_under"` - ¿Total de goles > X?
  - `"both_teams_score"` - ¿Ambos equipos anotan?
  - `"exact_score"` - ¿Resultado exacto?
  - `"social"` - Preguntas sociales (sin evaluar)
  - Otros según templates

#### 3.2 Lógica por Tipo de Pregunta
```php
// Ejemplo: "winner"
if ($match->home_team_score > $match->away_team_score)
    $correctOption = "home_wins"
else if ($match->away_team_score > $match->home_team_score)
    $correctOption = "away_wins"
else
    $correctOption = "draw"
```

#### 3.3 Puntuación
- [ ] `Answer::points_earned` - Guardar puntos obtenidos
- [ ] `Answer::is_correct` - Booleano si fue correcta
- [ ] `Question::result_verified_at` - Timestamp de verificación
- [ ] Actualizar puntuación total del usuario

---

### FASE 4: Jobs & Queue
**Objetivo:** Procesar en background

#### 4.1 ProcessQuestionResultsJob
```php
// Trigger: Cuando match termina
ProcessQuestionResultsJob::dispatch($matchId);

// Job:
- Obtener preguntas del match
- Evaluar cada una
- Procesar respuestas de usuarios
- Actualizar Answer table
- Actualizar User puntos
- Actualizar Group ranking
```

#### 4.2 Configuration
- [ ] Queue driver: database (ya configurado)
- [ ] Retry: 3 intentos
- [ ] Timeout: 120 segundos
- [ ] Logging de resultados

---

### FASE 5: API Endpoints (Opcional para ahora)
**Objetivo:** Consultar estado de predicciones

#### 5.1 Controllers
- [ ] GET `/api/matches/upcoming` - Próximos partidos
- [ ] GET `/api/matches/{id}/results` - Resultados de un match
- [ ] GET `/api/questions/{id}/result` - Estado de pregunta
- [ ] GET `/api/user/points` - Puntuación actual

#### 5.2 Responses
- [ ] Match con status
- [ ] Preguntas con resultado verificado
- [ ] Puntos del usuario

---

## 🗂️ ARCHIVOS A CREAR/MODIFICAR

### CREAR

```
app/
├─ Console/
│  └─ Commands/
│     ├─ UpdateFixturesCommand.php          [FASE 1]
│     └─ UpdateMatchResultsCommand.php      [FASE 1]
│
├─ Services/
│  ├─ QuestionEvaluationService.php         [FASE 3]
│  ├─ FootballDataMatchService.php          [FASE 2]
│  └─ MatchResultProcessingService.php      [FASE 2]
│
├─ Jobs/
│  └─ ProcessQuestionResultsJob.php         [FASE 4]
│
└─ Http/
   └─ Controllers/ (opcional)
      └─ MatchResultController.php          [FASE 5]

database/
└─ migrations/
   ├─ add_events_to_football_matches.php    [FASE 2]
   └─ add_verified_at_to_questions.php      [FASE 3]
```

### MODIFICAR

```
app/Console/Kernel.php                      [FASE 1] - Registrar crons
app/Models/Question.php                     [FASE 3] - Scopes para verificadas
app/Models/Answer.php                       [Verificar estructura]
app/Models/FootballMatch.php                [FASE 2] - Métodos helper
routes/api.php                              [FASE 5] - Nuevas rutas
```

---

## 📋 DETALLE POR FASE

### FASE 1: Comandos Cron

**UpdateFixturesCommand.php**
```php
// Ejecución: Noche (23:00)
// Logica:
1. Obtener La Liga, Premier, Champions, Serie A
2. Football-Data.org API
3. Para cada partido:
   - Si external_id no existe → Crear
   - Si existe → Actualizar
4. Log de cantidad importada
```

**UpdateMatchResultsCommand.php**
```php
// Ejecución: Cada hora (0 * * * *)
// Logica:
1. Obtener matches con status IN_PLAY o FINISHED
2. Consultar Football-Data.org para actualizaciones
3. Para cada match con cambios:
   - Actualizar status, scores, events
   - Encolar ProcessQuestionResultsJob
4. Log de cambios procesados
```

---

### FASE 2: Procesamiento de Datos

**FootballDataMatchService.php**
```php
public function getMatchesByLeague($league, $dateFrom, $dateTo)
// Retorna matches de una liga en rango de fechas

public function getMatchDetails($matchId)
// Detalles completos: goles, tarjetas, cambios, etc.

public function parseMatchData($apiData)
// Transforma respuesta API → datos de BD
```

**MatchResultProcessingService.php**
```php
public function updateMatchResult($match, $newData)
// Detecta cambios y actualiza

public function parseEventData($events)
// Estructura: goles, tarjetas, cambios
// Guardar en JSON en column 'events'

public function shouldProcessQuestions($match, $oldStatus)
// Retorna true si pasa de IN_PLAY a FINISHED
```

---

### FASE 3: Evaluación

**QuestionEvaluationService.php**
```php
public function evaluateQuestion(Question $q, FootballMatch $m)
// Retorna: ['correct_option_id' => X, 'type' => 'winner']

// Por tipo:
- winner: comparar scores
- first_goal: si hay goles en events
- goals_over_under: contar total goles
- both_teams_score: ambos > 0
- exact_score: comparar exacto
- social: null (no evaluar)
```

**Answer Evaluation**
```php
// Para cada Answer de la pregunta:
$correct = determineCorrect();
$answer->update([
    'is_correct' => $answer->option_id === $correct,
    'points_earned' => $isCorrect ? $question->points : 0
]);

// Actualizar usuario:
$user->points += $answer->points_earned;
```

---

### FASE 4: Queue Job

**ProcessQuestionResultsJob.php**
```php
public function handle()
{
    // 1. Obtener match
    // 2. Obtener preguntas asociadas
    // 3. Para cada pregunta:
    //    - Evaluar
    //    - Procesar respuestas
    //    - Actualizar puntos
    // 4. Marcar Question::result_verified_at
    // 5. Log exitoso
}
```

---

### FASE 5: API Endpoints

**GET `/api/matches/upcoming`**
```json
{
  "data": [
    {
      "id": 1,
      "home_team": "Girona FC",
      "away_team": "CA Osasuna",
      "date": "2026-01-10 17:30",
      "status": "TIMED",
      "questions_count": 5
    }
  ]
}
```

**GET `/api/matches/1/results`**
```json
{
  "data": {
    "id": 1,
    "status": "FINISHED",
    "score": {
      "home": 2,
      "away": 1
    },
    "events": {
      "goals": [...],
      "cards": [...],
      "substitutions": [...]
    },
    "questions": [
      {
        "id": 10,
        "title": "¿Quién gana?",
        "correct_option_id": 15,
        "user_answer_id": 15,
        "is_correct": true,
        "points_earned": 100
      }
    ]
  }
}
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### FASE 1
- [ ] UpdateFixturesCommand creado
- [ ] UpdateMatchResultsCommand creado
- [ ] Registrados en Kernel::schedule()
- [ ] Test manual de ambos
- [ ] Verificar imports en BD

### FASE 2
- [ ] FootballDataMatchService implementado
- [ ] MatchResultProcessingService implementado
- [ ] Parseador de eventos funcional
- [ ] Transacciones BD correctas
- [ ] Test de actualización

### FASE 3
- [ ] QuestionEvaluationService con todos los tipos
- [ ] Lógica correcta por tipo de pregunta
- [ ] Answer update con is_correct y points
- [ ] User points incremento correcto
- [ ] Test de evaluación

### FASE 4
- [ ] ProcessQuestionResultsJob creado
- [ ] Dispatch funciona desde comando
- [ ] Queue procesa correctamente
- [ ] Puntos finales correctos
- [ ] Logging funcional

### FASE 5
- [ ] Endpoints API creados
- [ ] Responses JSON correctas
- [ ] Autenticación Sanctum
- [ ] Rate limiting si needed
- [ ] Test de endpoints

---

## 🔄 DEPENDENCIAS ENTRE FASES

```
FASE 1 ──┐
         ├─→ FASE 2 ──┐
         │            ├─→ FASE 4 ──→ FASE 5
         │            │
         └────────────┤
                      └─→ FASE 3 ──┘
```

**Notas:**
- FASE 1 es independiente (cron puro)
- FASE 2 necesita FASE 1 (datos para procesar)
- FASE 3 y 4 dependen de FASE 2
- FASE 5 es la API que consulta todos

---

## 🚀 ORDEN RECOMENDADO DE DESARROLLO

1. **FASE 1** - Asegurar datos en BD
2. **FASE 2** - Procesar esos datos
3. **FASE 3** - Evaluar preguntas
4. **FASE 4** - Queue jobs
5. **FASE 5** - API (si needed)

---

## 📝 NOTAS IMPORTANTES

### Sobre Gemini
- No usaremos Gemini para EVALUAR respuestas (determinístico)
- La evaluación es lógica simple (quien anotó más, etc)
- Gemini se usa SOLO en: GenerateQuestionsService (ya implementado)

### Sobre Football-Data.org
- Actualmente: scores básicos
- Futuro: eventos completos (cuando se pague)
- Por ahora: guardar events/stats como JSON vacío o parsed

### Sobre Performance
- Actualizar resultados cada hora (no cada minuto)
- Queue jobs en background (no bloqueante)
- Cachear datos de partidos 5 minutos

### Sobre Errores
- Logging detallado de cada fase
- Retry automático para fallos de API
- Alertas si falla el job

---

## 📞 SIGUIENTES PASOS

1. ✅ Confirmar plan
2. ⬜ Comenzar FASE 1: UpdateFixturesCommand
3. ⬜ Completar FASE 1: UpdateMatchResultsCommand
4. ⬜ Proceder a FASE 2...

**¿Procedemos con FASE 1?**
