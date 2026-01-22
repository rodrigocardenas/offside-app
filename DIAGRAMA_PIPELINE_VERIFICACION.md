# 🔄 Diagrama del Pipeline Automático de Verificación

## Flujo Temporal (por hora)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         HORA 14:00 - 15:00                                  │
├─────────────────────────────────────────────────────────────────────────────┤

14:00:00 │  SCHEDULER DISPARA: UpdateFinishedMatchesJob
         │  ┌──────────────────────────────────────────────────────────────┐
         │  │ Buscar partidos que DEBERÍAN estar terminados              │
         │  │ Status: "Not Started", Fecha hace > 2 horas                 │
         │  ├──────────────────────────────────────────────────────────────┤
         │  │ Match #1: Barcelona vs Real Madrid                          │
         │  │ ├─ Intenta API Football → ✅ Score 2-1                      │
         │  │ └─ Status = "Match Finished"                               │
         │  │                                                              │
         │  │ Match #2: Atlético vs Sevilla                              │
         │  │ ├─ Intenta API Football → ❌ Sin datos                      │
         │  │ ├─ Intenta Gemini (web search+grounding) → ✅ Score 0-0    │
         │  │ └─ Status = "Match Finished"                               │
         │  └──────────────────────────────────────────────────────────────┘
         │
14:00:30 │  Jobs encolados: ProcessMatchBatchJob x2 (con delays de 10s)
         │

14:00:40 │  COLA: ProcessMatchBatchJob BATCH 1
         │  ├─ Ejecuta updateMatchFromApi(#1)
         │  └─ Actualiza Match #1: scores, status, events fields

14:00:50 │  COLA: ProcessMatchBatchJob BATCH 2
         │  ├─ Intenta updateMatchFromApi(#2) → ❌
         │  ├─ Intenta getMatchResult() de Gemini → ✅
         │  └─ Actualiza Match #2: scores 0-0, status "Match Finished"

14:05:00 │  SCHEDULER DISPARA: VerifyFinishedMatchesHourlyJob
         │  ┌──────────────────────────────────────────────────────────────┐
         │  │ Buscar partidos FINISHED con preguntas sin verificar        │
         │  │ Status: "Match Finished" + result_verified_at IS NULL       │
         │  ├──────────────────────────────────────────────────────────────┤
         │  │ Match #1: 8 preguntas sin verificar → Prioridad 1           │
         │  │ Match #2: 5 preguntas sin verificar → Prioridad 2           │
         │  │                                                              │
         │  │ Dispara BATCH de jobs paralelos:                           │
         │  │                                                              │
         │  │  ├─ BatchGetScoresJob([#1, #2])                             │
         │  │  │  └─ Obtiene score final de cada partido                 │
         │  │  │                                                          │
         │  │  ├─ BatchExtractEventsJob([#1, #2])                         │
         │  │  │  └─ Extrae: goles, tarjetas, substituciones, etc.      │
         │  │  │                                                          │
         │  │  └─ VerifyAllQuestionsJob([#1, #2]) [.finally()]          │
         │  │     └─ Se ejecuta DESPUÉS (o incluso si hay errores)      │
         │  └──────────────────────────────────────────────────────────────┘
         │

14:05:30 │  COLA: BatchGetScoresJob
         │  ├─ Intenta API Football para #1 → ✅ Score 2-1
         │  ├─ Intenta API Football para #2 → ❌
         │  ├─ Intenta Gemini para #2 → ✅ Score 0-0
         │  └─ Completa

14:06:00 │  COLA: BatchExtractEventsJob (PARALELO)
         │  ├─ Llama Gemini::getDetailedMatchData(#1)
         │  │  └─ Retorna eventos estructurados (goles minuto 12 y 34, etc.)
         │  ├─ Llama Gemini::getDetailedMatchData(#2)
         │  │  └─ Retorna eventos (0 goles) + tarjetas + estadísticas
         │  └─ Completa

14:06:30 │  COLA: VerifyAllQuestionsJob (INICIA gracias a .finally())
         │  ├─ Procesa en chunks de 50 preguntas
         │  │
         │  ├─ CHUNK 1: Preguntas #1-50
         │  │  ├─ Question #10: "¿Quién anotó el primer gol?"
         │  │  │  ├─ Datos del partido: Gol minuto 12 - Jugador X (Barcelona)
         │  │  │  ├─ Opciones: [A: Jugador X ✓, B: Jugador Y, C: Jugador Z]
         │  │  │  ├─ Respuestas del usuario: 3 usuarios eligieron A, 2 eligieron B
         │  │  │  └─ Actualiza: A.is_correct=true, B.is_correct=false
         │  │  │     - User 1: points_earned = 300 (correcto)
         │  │  │     - User 2: points_earned = 0 (incorrecto)
         │  │  │
         │  │  ├─ Question #15: "¿Cuántos goles anotó Real Madrid?"
         │  │  │  ├─ Datos: 1 gol (minuto 45)
         │  │  │  ├─ Opciones: [A: 0, B: 1 ✓, C: 2, D: 3+]
         │  │  │  └─ Actualiza respuestas correctamente
         │  │  │
         │  │  └─ ...más preguntas del chunk
         │  │
         │  └─ Se repite para cada chunk hasta procesar todas
         │

14:07:00 │  ✅ VerifyAllQuestionsJob COMPLETA
         │  └─ LOG: "Processed 13 questions, updated 52 answers, 5 errors"

14:07:30 │  📊 RESULTADOS FINALES:
         │  ├─ Match #1: 8 preguntas verificadas, ~24 respuestas procesadas
         │  ├─ Match #2: 5 preguntas verificadas, ~15 respuestas procesadas
         │  ├─ Usuarios ganaron puntos automáticamente
         │  └─ Próxima ejecución: 15:00:00

└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Árbol de Jobs

```
SCHEDULER (cada hora a :00 y :05)
│
├─ :00 ──→ UpdateFinishedMatchesJob
│          ├─ Query: Partidos con date <= now()-2h Y status != FINISHED
│          └─ Dispatcha: ProcessMatchBatchJob (delayado 10s, 20s, etc.)
│             └─ Para cada partido:
│                ├─ Intenta: footballService::updateMatchFromApi()
│                └─ Si falla: Intenta geminiService::getMatchResult()
│                   └─ Si ok: Actualiza status="Match Finished"
│
└─ :05 ──→ VerifyFinishedMatchesHourlyJob
           ├─ Query: Partidos con status="Match Finished" + preguntas sin verificar
           ├─ Priority Sort: Por minutos desde update y cantidad de preguntas
           ├─ Dispatcha Batch paralelo:
           │  ├─ BatchGetScoresJob([match_ids])
           │  │  ├─ API Football → scores finales
           │  │  └─ Fallback: Gemini web search
           │  │
           │  ├─ BatchExtractEventsJob([match_ids])
           │  │  └─ Gemini::getDetailedMatchData()
           │  │     └─ Eventos estructurados (JSON)
           │  │
           │  └─ VerifyAllQuestionsJob([match_ids]) [.finally()]
           │     └─ QuestionEvaluationService::evaluateQuestion()
           │        ├─ Determina opciones correctas
           │        ├─ Actualiza options.is_correct
           │        ├─ Actualiza answers.is_correct
           │        └─ Calcula points_earned
```

---

## Estados de Partidos

```
Status: "Not Started"
   │ (2 horas después de la fecha programada)
   ↓
[UpdateFinishedMatchesJob ejecuta]
   │ ✅ API o Gemini retorna score
   ↓
Status: "Match Finished"
   │ (Esperando verificación de respuestas)
   ↓
[VerifyFinishedMatchesHourlyJob ejecuta]
   │ Busca: Preguntas sin verificar
   │ Procesa: Contra datos obtenidos
   ↓
Preguntas con result_verified_at = now()
Respuestas con is_correct y points_earned
   │
   ↓ (Final)
Usuarios reciben puntos correctos
```

---

## Configuración de Timing

```
CADA HORA:
┌────────────────────────────────────────────────┐
│ :00 - :01  UpdateFinishedMatchesJob inicia     │
│ :00 - :03  ProcessMatchBatchJob ejecuta en cola│
│ :04        Se completa actualización           │
│            ↓ Partidos ahora tienen status FINISHED
│ :05        VerifyFinishedMatchesHourlyJob      │
│ :05 - :06  BatchGetScoresJob + ExtractEvents   │
│ :06 - :07  VerifyAllQuestionsJob ejecuta       │
│ :07        Preguntas verificadas ✅            │
│ :08 - :59  Sistema en reposo                   │
└────────────────────────────────────────────────┘
```

---

## Flujo de Datos - UpdateFinishedMatches

```
FootballMatch (Not Started)
     ↓
     ├─→ API Football ?
     │   ├─ ✅ Yes: home_score, away_score, score, status="Match Finished"
     │   └─ ❌ No: Continúa
     │
     ├─→ Gemini (web search + grounding) ?
     │   ├─ ✅ Yes: Mismo resultado
     │   └─ ❌ No: NO ACTUALIZA (verified-only policy)
     │
     └─→ FootballMatch (actualizado)
         ├─ status = "Match Finished"
         ├─ home_team_score = X
         ├─ away_team_score = Y
         ├─ score = "X - Y"
         └─ statistics = { source: "API|Gemini", verified: true }
```

---

## Flujo de Datos - VerifyFinished

```
FootballMatch (Match Finished) + Questions (sin verificar)
     ↓
     ├─→ BatchGetScoresJob: Obtiene score final
     │   └─ home_team_score, away_team_score
     │
     ├─→ BatchExtractEventsJob: Obtiene eventos
     │   └─ goals: [{minute, scorer, team}, ...],
     │      cards: [{minute, player, team, type}, ...],
     │      substitutions, possession, etc.
     │
     └─→ VerifyAllQuestionsJob
         ├─ Por cada Question:
         │  ├─ Evalúa contra datos obtenidos
         │  ├─ Determina: opciones correctas
         │  ├─ Actualiza Question: result_verified_at
         │  ├─ Actualiza Options: is_correct
         │  └─ Actualiza Answers: is_correct, points_earned
         │
         └─ Usuarios reciben puntos ✅
```

