# 📋 PLAN DE ACCIÓN: Optimización de Verificación de Resultados con Cadencia Horaria

## 1. ANÁLISIS DE LA SITUACIÓN ACTUAL

### 1.1 Limitaciones Conocidas

**Recursos Disponibles**:
- ✅ API Football: Versión gratuita (limitada)
- ✅ Gemini 2.5-flash: Versión gratuita (limitada a ~15 peticiones/minuto)
- ✅ Database queue: Sin limitaciones
- ✅ Cron jobs: Ejecutables cada hora

**Comportamiento Actual**:
```
Flujo Actual (Lento)
├─ ProcessRecentlyFinishedMatchesJob (coordinador)
├─ UpdateFinishedMatchesJob (busca partidos)
├─ ProcessMatchBatchJob (obtiene score de 1 partido por llamada a Gemini)
├─ ExtractMatchDetailsJob (obtiene eventos de 1 partido por llamada a Gemini)
├─ VerifyQuestionResultsJob (verifica preguntas)
└─ CreatePredictiveQuestionsJob (crea nuevas preguntas)

Problemas:
❌ ProcessMatchBatchJob hace 1 llamada Gemini POR PARTIDO
❌ ExtractMatchDetailsJob hace 1 llamada Gemini POR PARTIDO
❌ No hay deduplicación si se ejecuta varias veces
❌ No hay caché de resultados
❌ Espera demasiado entre cada etapa (5-10 segundos cada una)
❌ Total: puede tomar 2-5 minutos por todo el flujo
❌ Si algo falla, todo queda bloqueado
```

### 1.2 Impacto en Usuarios

```
Escenario Actual:
├─ 20:00 - Partido finaliza (Real Madrid 2-1 Barcelona)
├─ 20:05 - Usuario responde pregunta
├─ 20:30 - ProcessRecentlyFinishedMatchesJob se ejecuta
├─ 21:00 - Usuario aún NO ve sus puntos ❌
└─ Frustración del usuario

Escenario Deseado:
├─ 20:00 - Partido finaliza
├─ 20:05 - Usuario responde pregunta
├─ 21:00 - Primer cron job horario se ejecuta ✅
├─ 21:02 - Usuario VE sus puntos ✅
└─ Satisfacción del usuario
```

---

## 2. PROPUESTAS DE OPTIMIZACIÓN

### 2.1 Optimización #1: Batching Inteligente de Gemini

**Problema**: Cada llamada a Gemini es INDEPENDIENTE
- Llamada 1: "¿Liverpool vs Barnsley, score?" → 1 crédito
- Llamada 2: "¿Real Madrid vs Barcelona, score?" → 1 crédito
- Total: N créditos para N partidos

**Solución**: Batching de múltiples partidos en UNA llamada

```
Prompt Optimizado:
┌─────────────────────────────────────────────────┐
│ Proporciona en formato JSON los resultados de   │
│ estos 5 partidos (solo score, sin eventos):     │
│                                                 │
│ 1. Liverpool vs Barnsley (11 Jan 2026)         │
│ 2. Real Madrid vs Barcelona (11 Jan 2026)      │
│ 3. Manchester City vs Liverpool (12 Jan 2026)  │
│ 4. Bayern Munich vs Dortmund (12 Jan 2026)     │
│ 5. PSG vs Marseille (12 Jan 2026)              │
│                                                 │
│ Formato esperado:                               │
│ {                                               │
│   "results": [                                  │
│     {"home": "Liverpool", "away": "Barnsley",   │
│      "home_goals": 2, "away_goals": 0},        │
│     ...                                         │
│   ]                                             │
│ }                                               │
└─────────────────────────────────────────────────┘

Beneficio: 1 crédito para 5 partidos (~80% ahorro)
Limitación: Máximo 5-10 partidos/llamada (contexto Gemini)
```

**Implementación**:
- Crear nuevo servicio: `GeminiBatchService::getMultipleMatchResults()`
- Agrupar partidos en lotes de 5-10
- Implementar retry con fallback a peticiones individuales
- Agregar caché de 2 horas (no cambiarán scores en ese tiempo)

---

### 2.2 Optimización #2: Caché de Resultados

**Problema**: Si el job se ejecuta 2 veces en el mismo día, consulta a Gemini nuevamente

**Solución**: Caché con versionado

```php
// Estructura de caché
Cache::remember("match.{$matchId}.detailed_data", 120, function() {
    // 120 minutos = 2 horas
    return $geminiService->getDetailedMatchData(...);
});

Cache::remember("gemini.batch.{$batchHash}", 60, function() {
    // 60 minutos = 1 hora
    // Hash basado en IDs de partidos + fecha
    return $geminiBatchService->getMultipleResults(...);
});
```

**Beneficio**: 
- 1ª ejecución (09:00): Consulta Gemini
- 2ª ejecución (10:00): Lee caché (0 créditos)
- 3ª ejecución (11:00): Consulta Gemini nuevamente
- Ahorro: ~66% de créditos

---

### 2.3 Optimización #3: Priorización de Partidos

**Problema**: Procesa todos los partidos con igual prioridad

**Solución**: Priorizar por impacto en usuarios

```
Prioridad ALTA (Procesar primero):
├─ Partidos finalizados hace <30 minutos ⭐⭐⭐
│  └─ Usuarios más activos responden preguntas
├─ Partidos con respuestas sin verificar ⭐⭐
│  └─ Usuarios esperando ver puntos
└─ Partidos de ligas principales (Premier, La Liga, etc) ⭐
   └─ Más usuarios interesados

Prioridad MEDIA:
├─ Partidos finalizados hace 30min-2hrs
└─ Partidos con pocas respuestas

Prioridad BAJA:
├─ Partidos de ligas menores
├─ Partidos con muchos errores de Gemini
└─ Partidos sin preguntas asociadas
```

**Implementación**:
```sql
SELECT id, home_team, away_team, 
  CASE 
    WHEN TIMESTAMPDIFF(MINUTE, updated_at, NOW()) < 30 THEN 1
    WHEN questions_count > 0 AND unverified_answers > 5 THEN 2
    ELSE 3
  END as priority
FROM football_matches
WHERE status = 'Match Finished'
  AND NOT verified_at IS NOT NULL
  AND DATE(updated_at) = CURDATE()
ORDER BY priority, updated_at DESC
LIMIT 30;
```

---

### 2.4 Optimización #4: Paralelización Segura

**Problema**: Jobs se ejecutan secuencialmente con delays

**Solución**: Ejecutar en paralelo cuando sea posible

```
Antes (Secuencial - ~2 minutos):
└─ UpdateFinishedMatchesJob (5s)
   └─ ProcessMatchBatchJob (30s)
      └─ ExtractMatchDetailsJob (30s)
         └─ VerifyQuestionResultsJob (30s)

Después (Paralelo - ~45 segundos):
├─ [Paralelo] ProcessMatchBatchJob (batching) (20s)
├─ [Paralelo] ExtractMatchDetailsJob (batching) (20s)
└─ [Esperar ambos]
   └─ VerifyQuestionResultsJob (30s)
```

**Seguridad**:
- Usar database locks para evitar race conditions
- Implementar `with('lockForUpdate')`
- Marcar partidos como "in_progress" durante procesamiento

---

### 2.5 Optimización #5: Deduplicación Inteligente

**Problema**: Si un partido se procesa 2 veces, genera trabajo duplicado

**Solución**: Timestamp de "última verificación"

```php
// Nueva columna: last_verification_attempt_at
// Lógica:
if ($match->last_verification_attempt_at 
    && $match->last_verification_attempt_at->diffInMinutes(now()) < 30) {
    // Ya fue intentado hace <30 minutos, saltar
    continue;
}

// Marcar como intentado
$match->update(['last_verification_attempt_at' => now()]);

// Procesar...
```

**Beneficio**: Evita procesamiento duplicado de partidos "no verificables"

---

## 3. PLAN DE ACCIÓN PROPUESTO

### Fase 1: Preparación (1-2 días)

**Tareas**:
1. Crear `GeminiBatchService` con método `getMultipleMatchResults()`
   - Aceptar array de hasta 10 partidos
   - Parsear respuesta JSON
   - Implementar retry individual como fallback
   
2. Configurar caché Redis/Memcached
   - TTL 60-120 minutos para resultados
   - Caché de errores (15 minutos)
   
3. Agregar columnas a BD:
   - `last_verification_attempt_at` (nullable timestamp)
   - `verification_priority` (pequeint: 1=alta, 2=media, 3=baja)

**Archivos a crear**:
- `app/Services/GeminiBatchService.php`
- `database/migrations/add_verification_fields_to_matches.php`

**Tiempo estimado**: 4-6 horas

---

### Fase 2: Nuevo Job Coordinador (2-3 días)

**Crear: `VerifyFinishedMatchesHourlyJob` (reemplaza `ProcessRecentlyFinishedMatchesJob`)**

```
Flujo Nuevo:
┌─────────────────────────────────────────────┐
│ VerifyFinishedMatchesHourlyJob (coordinador)│
│ (ejecutable cada 1 hora)                    │
└─────────────────────────────────────────────┘
  │
  ├─── Etapa 1: Búsqueda Inteligente (5s)
  │    └─ Buscar partidos finalizados no verificados
  │    └─ Filtrar por: últimas 2 horas + prioridad
  │    └─ Limitar a 30-40 partidos máximo
  │
  ├─── Etapa 2: Batch Verification (20s)
  │    ├─ BatchGetScoresJob
  │    │  └─ GeminiBatchService::getMultipleMatchResults(10 partidos)
  │    │  └─ Guardar scores en batch
  │    │
  │    └─ BatchExtractEventsJob  
  │       └─ GeminiBatchService::getMultipleDetailedResults(10 partidos)
  │       └─ Guardar eventos en batch
  │
  ├─── Etapa 3: Asignación de Puntos (30s)
  │    └─ VerifyAllQuestionsJob (una sola ejecución para todos)
  │       └─ Verifica todas las respuestas pendientes
  │       └─ Asigna puntos
  │       └─ Notifica usuarios
  │
  └─── Etapa 4: Limpieza (5s)
       └─ Marcar como verificados
       └─ Generar estadísticas
       └─ Log de ejecución
```

**Archivos a crear**:
- `app/Jobs/VerifyFinishedMatchesHourlyJob.php` (coordinador)
- `app/Jobs/BatchGetScoresJob.php`
- `app/Jobs/BatchExtractEventsJob.php`
- `app/Jobs/VerifyAllQuestionsJob.php` (mejorado)

**Tiempo estimado**: 6-8 horas

---

### Fase 3: Configuración de Cron (1 día)

**En `app/Console/Kernel.php`**:

```php
// Ejecutar cada hora
$schedule->job(new VerifyFinishedMatchesHourlyJob)
    ->hourly()
    ->name('verify-matches-hourly')
    ->withoutOverlapping(15) // Max 15 min de duración
    ->onSuccess(function() {
        Log::info('✅ Verification hourly job completed successfully');
    })
    ->onFailure(function(Throwable $exception) {
        Log::error('❌ Verification hourly job failed', ['error' => $exception->getMessage()]);
        // Notificar admin
    });
```

**Opciones**:
- `hourly()`: Cada hora exacta (00:00, 01:00, etc.)
- `everyFiveMinutes()`: Cada 5 minutos (para testing)
- `everyTenMinutes()`: Cada 10 minutos (balance)

**Tiempo estimado**: 2-3 horas

---

### Fase 4: Monitoreo y Ajustes (1-2 días)

**Métricas a rastrear**:
- Tiempo de ejecución total por job
- Cantidad de partidos procesados
- Tasa de éxito/fallo de Gemini
- Créditos Gemini consumidos
- Hits/Misses de caché
- Latencia hasta asignación de puntos

**Alertas**:
- Si tiempo > 15 minutos
- Si fallo rate > 10%
- Si caché hit rate < 50% (ajustar TTL)
- Si Gemini rate limit alcanzado

**Tiempo estimado**: 3-5 horas (testing + ajustes)

---

## 4. ESTIMACIONES DE RECURSOS

### 4.1 Consumo Gemini (Antes vs Después)

**Escenario: 20 partidos finalizados/día**

```
ANTES (Procesamiento individual):
├─ ProcessMatchBatchJob: 20 llamadas (score básico)
├─ ExtractMatchDetailsJob: 20 llamadas (eventos)
├─ Total: 40 llamadas Gemini/día
└─ Costo: 40 créditos/día

DESPUÉS (Con batching y caché):
├─ Hora 1 (01:00): Batch scores (20→2 llamadas) + Eventos (2 llamadas) = 4 calls
├─ Hora 2-23 (02:00-00:00): Caché hit para mismos partidos = 0 calls
├─ Nuevos partidos cada hora: 2-5 partidos × 2 llamadas = ~4-10 calls
├─ Total: ~48-84 llamadas/día (vs 40 individuales en escenario estático)
└─ PERO en uso real:
   ├─ Partidos No cambian cada hora (mismo día)
   ├─ Caché evita re-consultar Gemini
   ├─ Total REAL: ~8-16 llamadas/día ✅
   └─ Ahorro: ~80%
```

**Limitación Gemini Gratuita**: ~15 req/min = ~21,600 req/día
- Nuestro uso: ~16 req/día
- Margen: 99.9% de holgura ✅

---

### 4.2 Consumo API Football

```
ANTES:
├─ UpdateFinishedMatchesJob: 20 llamadas
├─ Total: 20 calls/día

DESPUÉS (Mismo, no cambia):
├─ UpdateFinishedMatchesJob: 20 llamadas
├─ Total: 20 calls/día
```

**Limitación API Football Gratuita**: ~10 req/min
- Nuestro uso: 20 req/día
- Margen: Suficiente ✅

---

### 4.3 Base de Datos

**Nuevas columnas**:
- `last_verification_attempt_at` TIMESTAMP (nullable)
- `verification_priority` TINYINT (1-3)
- Índices: composite (status, verification_priority, updated_at)

**Impacto**: Mínimo (~500 bytes/registro)

---

## 5. ARQUITECTURA PROPUESTA

### 5.1 Flujo Completo Horario

```
00:00 - Inicio
│
├─ FASE 1: Descubrimiento (3s)
│  └─ SELECT partidos no verificados
│  └─ Ordenar por prioridad
│  └─ Limitar a 30 partidos
│
├─ FASE 2: Batch Processing (25s)
│  ├─ [PARALELO] BatchGetScoresJob
│  │  ├─ Dividir 30 partidos en lotes de 10
│  │  ├─ Gemini Batch 1 (10 partidos)
│  │  ├─ Gemini Batch 2 (10 partidos)  
│  │  └─ Gemini Batch 3 (10 partidos)
│  │  └─ Total: 3 llamadas en lugar de 30 ✅
│  │
│  └─ [PARALELO] BatchExtractEventsJob (después scores)
│     ├─ Gemini Batch 1 (10 partidos con eventos)
│     ├─ Gemini Batch 2 (10 partidos con eventos)
│     └─ Gemini Batch 3 (10 partidos con eventos)
│     └─ Total: 3 llamadas en lugar de 30 ✅
│
├─ FASE 3: Asignación de Puntos (30s)
│  ├─ Buscar respuestas sin verificar (de todos los partidos actualizados)
│  ├─ Para cada pregunta:
│  │  ├─ QuestionEvaluationService::evaluate()
│  │  ├─ Actualizar question_options.is_correct
│  │  ├─ Actualizar answers.is_correct
│  │  └─ Actualizar answers.points_earned
│  └─ Notificar usuarios vía WebSocket/Notification
│
├─ FASE 4: Finalización (2s)
│  ├─ Marcar partidos como verified_at = NOW()
│  ├─ Log de estadísticas
│  └─ Cache stats (hits, misses)
│
└─ 00:58 - FIN
   Siguiente ejecución: 01:00

Tiempo total: ~60 segundos ✅ (dentro del margen de 1 hora)
```

---

### 5.2 Clases Principales

```php
// GeminiBatchService - Nueva clase
class GeminiBatchService {
    public function getMultipleMatchResults(array $matches): array
    public function getMultipleDetailedResults(array $matches): array
}

// BatchGetScoresJob - Nuevo job
class BatchGetScoresJob {
    public function handle(GeminiBatchService $batchService)
}

// BatchExtractEventsJob - Nuevo job
class BatchExtractEventsJob {
    public function handle(GeminiBatchService $batchService)
}

// VerifyAllQuestionsJob - Job mejorado
class VerifyAllQuestionsJob {
    public function handle(QuestionEvaluationService $evaluationService)
}

// VerifyFinishedMatchesHourlyJob - Coordinador
class VerifyFinishedMatchesHourlyJob {
    public function handle()
}
```

---

## 6. CONSIDERACIONES SOBRE LIMITACIONES

### 6.1 Gemini Limitado

**Problema**: Versión gratuita tiene límite de tokens

**Soluciones**:
```
1. Batching (ya previsto)
   └─ Reduce peticiones 80%

2. Caché agresivo
   └─ TTL 120 minutos para scores
   └─ TTL 180 minutos para eventos

3. Compresión de prompts
   └─ Usar formato compacto JSON
   └─ Eliminar explicaciones innecesarias

4. Fallback a datos parciales
   └─ Si Gemini falla eventos, usar score solo
   └─ Score-based questions aún funcionan

5. Rate limiting inteligente
   └─ Espaciar peticiones si hay 429 response
   └─ Exponential backoff: 1s → 2s → 4s → 8s
```

### 6.2 API Football Limitada

**Problema**: Versión gratuita tiene límites bajos

**Soluciones**:
```
1. Priorizar Gemini
   └─ API Football solo como fuente secundaria

2. Caché de 2 horas
   └─ No re-consultar mismo partido 2 veces/hora

3. Batch si es posible (depende de API)
   └─ Verificar si API Football soporta multi-match query

4. Fallback a predicción
   └─ Si ambas fuentes fallan, usar score anterior
   └─ Marcar como "estimate" en lugar de "verified"
```

---

## 7. RIESGOS Y MITIGACIÓN

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|--------|-----------|
| Gemini rate limit alcanzado | Media | Alto | Exponential backoff + queue retry |
| Job tarda >15 min | Media | Medio | Timeout control + job splitting |
| Caché inválido (partido cambia) | Baja | Alto | Validación de score vs caché |
| Race condition en batch update | Media | Medio | Database locks (pessimistic) |
| Fallo en parseo JSON batch | Media | Medio | Fallback a peticiones individuales |
| Usuarios ven puntos incorrectos | Baja | Muy Alto | Validación pre-guardado + auditoría |
| Job se ejecuta 2 veces | Media | Bajo | `withoutOverlapping()` en scheduler |

---

## 8. FASES DE IMPLEMENTACIÓN RECOMENDADAS

### Timeline Estimado Total: 10-15 días

```
Semana 1:
├─ Día 1-2: Fase 1 (Preparación)
│  └─ GeminiBatchService + Caché + BD migrations
├─ Día 3-4: Fase 2 (Nuevos Jobs)
│  └─ BatchGetScoresJob + BatchExtractEventsJob + Coordinador
└─ Día 5: Testing inicial
   └─ Test con 5-10 partidos reales

Semana 2:
├─ Día 6-7: Fase 3 (Cron configuration)
│  └─ Kernel configuration + Monitoring setup
├─ Día 8-10: Fase 4 (Testing exhaustivo + Ajustes)
│  └─ A/B testing vs sistema antiguo
│  └─ Optimización de TTL caché
│  └─ Ajuste de batch size
└─ Día 11+: Rollout gradual
   ├─ 10% tráfico
   ├─ 50% tráfico
   └─ 100% tráfico

Métricas a validar:
✓ Tiempo promedio de verificación < 1 minuto
✓ Usuarios reciben puntos dentro de 1 hora de partido finalizado
✓ Precisión de puntos asignados > 99%
✓ Tasa de error < 2%
✓ Créditos Gemini ahorrados > 70%
```

---

## 9. ALTERNATIVAS CONSIDERADAS

### Alternativa A: Event-Driven en lugar de Schedule

```
Ventajas:
✓ Más rápido (responde inmediatamente a evento)
✓ Menos consumo de recursos

Desventajas:
✗ Requiere webhooks de API Football
✗ Requiere implementación de queue events
✗ Más complejo de debuggear
✗ Más frágil

Veredicto: No recomendado por ahora
```

### Alternativa B: Verificación Inline (sin queue)

```
Ventajas:
✓ Más rápido (sin delays de queue)
✓ Respuesta inmediata

Desventajas:
✗ Bloquea request HTTP
✗ Si Gemini tarda, usuario ve latencia
✗ Falta de retry automático
✗ Más fallos

Veredicto: No recomendado
```

### Alternativa C: Verificación Cada 15 Minutos

```
Ventajas:
✓ Más créditos disponibles

Desventajas:
✗ Usuarios ven puntos más tardío (hasta 15 min)
✗ Experiencia mediocre

Veredicto: Posible compromiso si Gemini no alcanza
```

---

## 10. RECOMENDACIÓN FINAL

**Implementar la propuesta de Batching + Caché + Horario:**

```
✅ PROS:
├─ Reduce latencia hasta 1 hora máximo
├─ Ahorra ~80% créditos Gemini
├─ Escalable (fácil agregar más partidos)
├─ Predecible (ejecución cada hora)
├─ Monitoreable (logs claros)
└─ Fallback incluido (partidos individuales si falla batch)

❌ CONTRAS MITIGABLES:
├─ Requiere 10-15 días de implementación
├─ Complexity media (4 nuevas clases)
└─ Testing exhaustivo necesario

Estimación de ROI:
├─ Costo: 40-50 horas de desarrollo
├─ Beneficio: 
│  ├─ Usuarios más satisfechos (puntos más rápido)
│  ├─ Ahorro 80% créditos Gemini
│  ├─ Sistema más escalable
│  └─ Mejor UX overall
└─ Payoff: Muy positivo
```

---

## 11. PRÓXIMOS PASOS

1. **Validar** este plan con el equipo
2. **Prioritizar** las fases según disponibilidad
3. **Crear issues/tasks** en el proyecto
4. **Comenzar Fase 1**: GeminiBatchService + Caché
5. **Testing continuo** en cada fase
6. **Rollout gradual** al completar

---

## 12. DOCUMENTACIÓN DE REFERENCIA

**Archivos existentes a revisar**:
- `app/Services/GeminiService.php` (actual)
- `app/Jobs/ProcessMatchBatchJob.php` (actual)
- `app/Jobs/ExtractMatchDetailsJob.php` (actual)
- `app/Console/Kernel.php` (scheduler)

**Nuevos archivos a crear**:
- `app/Services/GeminiBatchService.php`
- `app/Jobs/VerifyFinishedMatchesHourlyJob.php`
- `app/Jobs/BatchGetScoresJob.php`
- `app/Jobs/BatchExtractEventsJob.php`
- `app/Jobs/VerifyAllQuestionsJob.php`
- `database/migrations/add_verification_priority_to_matches.php`

---

**Documento preparado**: 15-01-2026
**Autor**: Plan de optimización horaria
**Estado**: LISTO PARA IMPLEMENTACIÓN
