# 🔍 Problema: Preguntas no se Verifican (correct_option = null)

## El Síntoma

```json
{
    "is_correct": false,
    "correct_option": null,
    "points_earned": 0
}
```

**Todas las preguntas marcadas como falsas, pero `correct_option` es `null`.**

Frontend muestra error:
```
TypeError: Cannot read properties of null (reading 'text')
```

---

## La Raíz del Problema

### Cadena de Eventos

1. ✅ Partido actualizado con datos ficticios (Fallback random): `4 - 1`
2. ✅ Scores guardados en BD: `home_team_score = 4`, `away_team_score = 1`
3. ✅ Evento guardado como TEXT simple:
   ```
   "Partido actualizado desde Fallback (random): 4 goles del local, 1 del visitante"
   ```
4. ❌ Job de verificación intenta verificar preguntas
5. ❌ `QuestionEvaluationService` llama `parseEvents()`
6. ❌ `parseEvents()` intenta decodificar el texto como JSON
7. ❌ Falla el parseo → retorna `[]` (vacío)
8. ❌ Preguntas del tipo "primer gol", "tarjetas", etc. retornan 0 opciones correctas
9. ❌ `correct_option` queda `NULL`

### Código Problemático (Antes)

```php
// QuestionEvaluationService.php - ANTES
$events = $this->parseEvents($match->events ?? []);
// $match->events = "Partido actualizado desde Fallback..."
// parseEvents() intenta: json_decode("Partido...", true)
// Retorna: NULL → se convierte a []
// Resultado: No hay eventos para evaluar

$firstGoalTeam = null;
foreach ($events as $event) {  // $events = []
    if ($event['type'] === 'GOAL') {
        $firstGoalTeam = $event['team'];
    }
}
// Resultado: $firstGoalTeam = null (nunca entra al loop)
// Retorna: [] (sin opciones correctas)
```

---

## La Solución Implementada

### 1️⃣ Detectar Datos Ficticios

```php
private function hasVerifiedMatchData(FootballMatch $match): bool
{
    $statistics = json_decode($match->statistics, true);
    $source = $statistics['source'] ?? '';
    
    // ❌ Datos ficticios
    if (stripos($source, 'fallback') !== false ||
        stripos($source, 'random') !== false ||
        stripos($source, 'simulated') !== false) {
        return false;  // NO es verificado
    }
    
    // ✅ Datos verificados
    if (stripos($source, 'api football') !== false ||
        stripos($source, 'gemini') !== false) {
        return true;  // SÍ es verificado
    }
    
    return false;  // Por defecto: NO es verificado
}
```

### 2️⃣ Seleccionar Solo Preguntas Verificables

```php
// Clasificar por tipo de pregunta

// ✅ SCORE-BASED (siempre funciona, solo necesita home_team_score + away_team_score)
if ($this->isQuestionAbout($text, 'resultado|ganador')) {
    $correctOptions = $this->evaluateWinner($question, $match);  // Usa scores
}
if ($this->isQuestionAbout($text, 'ambos.*anotan')) {
    $correctOptions = $this->evaluateBothScore($question, $match);  // Usa scores
}
if ($this->isQuestionAbout($text, 'score.*exacto')) {
    $correctOptions = $this->evaluateExactScore($question, $match);  // Usa scores
}

// ❌ EVENT-BASED (necesita eventos detallados - solo si datos verificados)
if ($hasVerifiedData && $this->isQuestionAbout($text, 'primer gol')) {
    $correctOptions = $this->evaluateFirstGoal($question, $match);  // Usa eventos
}
if ($hasVerifiedData && $this->isQuestionAbout($text, 'tarjetas')) {
    $correctOptions = $this->evaluateYellowCards($question, $match);  // Usa eventos
}
```

### 3️⃣ Mejor UI para Datos Pendientes

**Antes:**
```
Respuesta correcta: [Pendiente de verificar] (Gray)
```

**Ahora:**
```
Estado: [Pendiente de verificación] (Yellow)
Este partido aún no tiene datos verificados para evaluar esta pregunta.
```

---

## Resultados Esperados

### Para Preguntas Score-Based (Siempre verificables)

```
Pregunta: "¿Cuál será el resultado?"
Opciones: [Dortmund gana, Bremen gana, Empate]
Datos: Dortmund 4 - 1 Bremen  ← Datos ficticios OK

✅ Se verifica correctamente:
   - Opción "Dortmund gana" → CORRECTA
   - Otras opciones → INCORRECTAS
   - correct_option es poblado correctamente
```

### Para Preguntas Event-Based (Solo con datos verificados)

```
Pregunta: "¿Quién anotará el primer gol?"
Datos: Partido con Fallback (random)

❌ NO se verifica:
   - correct_option queda NULL
   - UI muestra: "Pendiente de verificación"
   - Razón: Sin eventos detallados, no se puede determinar primer gol
```

---

## Configuración de Statistics

### ✅ Datos Verificados (API Football)

```json
{
    "source": "API Football (VERIFIED)",
    "verified": true,
    "timestamp": "2026-01-14T10:30:00Z"
}
```
→ **TODAS las preguntas se verifican**

### ✅ Datos Verificados (Gemini)

```json
{
    "source": "Gemini (web search - VERIFIED)",
    "verified": true,
    "verification_method": "grounding_search"
}
```
→ **TODAS las preguntas se verifican**

### ❌ Datos NO Verificados (Fallback)

```json
{
    "source": "Fallback (random)",
    "verified": false
}
```
→ **Solo preguntas score-based se verifican**

### ❌ Datos NO Verificados (Limpiados)

```json
{
    "source": "NO_ENCONTRADO",
    "verified": false,
    "api_failed": true,
    "gemini_failed": true
}
```
→ **Ninguna pregunta se verifica** (partido sin scores)

---

## Checklist de Verificación

### Para Usuarios

- [ ] ¿Tu grupo tiene preguntas con `correct_option = null`?
  - Si: Son partidos con datos ficticios/pendientes
  - Solución: Ejecutar `php cleanup-fictional-data.php` en producción

- [ ] ¿Las preguntas muestran "Pendiente de verificación"?
  - Si: Es normal - datos no verificados aún
  - Esperar: Hasta que el partido tenga datos verificados

- [ ] ¿Algunas preguntas SÍ tienen `correct_option` (ganador, score exacto)?
  - Si: Son score-based - funcionan incluso con datos ficticios
  - Bien: Sistema está funcionando correctamente

### Para Desarrolladores

```bash
# Verificar qué preguntas pueden verificarse
SELECT 
    q.id,
    q.title,
    q.type,
    fm.score,
    fm.statistics,
    q.result_verified_at
FROM questions q
JOIN football_matches fm ON q.football_match_id = fm.id
WHERE fm.status = 'Match Finished'
ORDER BY fm.updated_at DESC
LIMIT 20;
```

---

## Histórico del Problema

| Fase | Estado | Problema |
|------|--------|----------|
| 1. Fallback Generado | ❌ | Scores aleatorios guardados (4-1, 3-3, etc.) |
| 2. Limpieza | ✅ | 3 particiones limpias, 6 con Gemini verificado |
| 3. Verificación Jobs | ❌ | Job fallaba al parsear eventos ficticios |
| 4. Frontend Error | ❌ | `correct_option = null` → TypeError |
| 5. ESTA FIX | ✅ | Detectar datos ficticios, verificar solo lo posible |

---

## Migración de Datos Históricos

Si tienes preguntas antiguas con `correct_option = null`:

```sql
-- 1. Ver estado actual
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN correct_option_id IS NULL THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN correct_option_id IS NOT NULL THEN 1 ELSE 0 END) as verified
FROM answers
WHERE created_at > '2026-01-13';

-- 2. Re-ejecutar verificación
php artisan matches:process-recently-finished

-- 3. O ejecutar en sync
php artisan matches:process-finished-sync
```

---

## Conclusión

✅ **El código ahora:**
- Detecta cuando match tiene datos ficticios
- Solo verifica preguntas score-based para esos casos
- Mantiene event-based solo para datos verificados
- Muestra UI clara sobre estado de verificación

✅ **Próximos pasos:**
1. Deploy en producción
2. Ejecutar `cleanup-fictional-data.php` (opcional si hay partidos con Fallback)
3. Re-ejecutar `php artisan matches:process-recently-finished`
4. Verificar logs: No debe haber más errores de `null` properties
