# Grounding (Búsqueda Web) - Estrategia Inteligente

## ¿Qué es Grounding?

**Grounding** = Habilitar búsqueda web en Gemini para obtener información en tiempo real de internet.

- **Con grounding**: Gemini busca en internet → Más lento (10-30s extra) pero preciso
- **Sin grounding**: Usa solo conocimiento entrenado → Más rápido (~5s) pero potencialmente desactualizado

## Estrategia Implementada

### 1. **Verificación de Preguntas** (Más Común)
```php
// QuestionEvaluationService.php
$hasVerified = $this->hasVerifiedMatchData($match);
$useGrounding = !$hasVerified; // Solo si NO hay datos en BD
```

✅ **Lógica**:
- Si la BD tiene datos verificados (API Football o Gemini previo) → **Sin grounding** (rápido)
- Si la BD NO tiene datos o son ficticios → **Con grounding** (preciso)

**Resultado**: ~90% de preguntas se verifican en 5-10 segundos, solo 10% necesita 20-30 segundos.

### 2. **Obtener Resultados de Partidos** (getMatchResult)
```php
// Intenta PRIMERO sin grounding
$response = $this->callGemini($prompt, false);

// Si falla, reintenta CON grounding
if (!success) return $this->getMatchResult(..., $useGroundingAttempt=true);
```

✅ **Lógica**:
- Primer intento: SIN grounding (datos locales del modelo)
- Si falla: Reintento CON grounding (búsqueda web)

**Resultado**: Mayoría de partidos resolvidos sin búsqueda web, solo casos raros necesitan internet.

### 3. **Obtener Datos Detallados** (getDetailedMatchData)
```php
// Misma estrategia de reintentos
$response = $this->callGemini($prompt, false);
if (no result && !useGroundingAttempt) {
    return $this->getDetailedMatchData(..., $useGroundingAttempt=true);
}
```

✅ **Lógica**: Idéntica al anterior, pero para eventos detallados.

---

## Cómo Usar

### Opción 1: Deshabilitar grounding globalmente (para testing)
```bash
php artisan questions:repair --match-id=296 --no-grounding
```

Esto configura `GeminiService::setDisableGrounding(true)` que aplica a TODAS las llamadas.

### Opción 2: Config en .env
```bash
# Cambiar modelo más rápido
GEMINI_MODEL=gemini-2.5-flash

# Aumentar timeout para búsquedas web
GEMINI_TIMEOUT=90

# Deshabilitar grounding permanentemente
GEMINI_GROUNDING_ENABLED=false
```

### Opción 3: A nivel de código
```php
// Deshabilitar grounding globalmente
GeminiService::setDisableGrounding(true);

// Llamadas posteriores respetarán esta configuración
```

---

## Impacto en Rendimiento

### Caso 1: Partido con datos en BD (Típico)
```
Pregunta 1: ✅ Primer gol → Sin grounding → 5s
Pregunta 2: ✅ Gol antes de 15' → Sin grounding → 5s
Pregunta 3: ✅ Posesión > 50% → Sin grounding → 5s
Total: 15s para 3 preguntas
```

### Caso 2: Partido SIN datos en BD (Raro)
```
Pregunta 1: ❌ Sin resultado local → Reintenta CON grounding → 25s
Pregunta 2: ✅ Ahora con datos cacheados → Sin grounding → 5s
Total: 30s para 2 preguntas (después, todas son rápidas)
```

---

## Logs y Debugging

Ver decisiones de grounding en logs:
```bash
grep -i "grounding\|use_grounding" storage/logs/laravel.log
```

Ejemplo de log:
```
[2026-01-21 12:34:56] local.INFO: Gemini fallback decision [
  "question_id" => 123,
  "has_verified_data" => true,
  "use_grounding" => false,        ← Sin grounding (rápido)
  "reason" => "empty_result"
]

[2026-01-21 12:34:58] local.WARNING: No se obtuvieron datos sin grounding [
  "home_team" => "Man City",
  "away_team" => "Liverpool"
]
[2026-01-21 12:34:58] local.WARNING: Reintentando con búsqueda web [...]
[2026-01-21 12:35:22] local.INFO: Datos detallados del partido obtenidos [
  "grounding_used" => true         ← Finalmente CON grounding
]
```

---

## Cuando Cambiar Estrategia

### ✅ Usar grounding DESHABILITADO
- Testing/desarrollo local
- BD tiene datos completos y verificados
- API de Gemini está lenta o con rate limit

### ✅ Usar grounding HABILITADO (por defecto)
- Producción, datos no verificados
- Necesidad de exactitud máxima
- Partidos recientes sin datos locales

### ⚠️ Ajuste fino
- Aumentar `GEMINI_TIMEOUT` a 90s si hay timeouts
- Reducir `GEMINI_MAX_RETRIES` a 2 para evitar esperas largas
- Usar `--no-grounding` durante picos de carga

---

## Resumen

| Situación | Grounding | Tiempo | Precisión |
|-----------|-----------|--------|-----------|
| Verificar pregunta con datos en BD | ❌ No | 5-10s | Alta |
| Verificar pregunta sin datos | ✅ Sí | 20-30s | Máxima |
| Obtener resultado partidos | Intenta 2x | 5-25s | Alta |
| Obtener datos detallados | Intenta 2x | 5-25s | Alta |

**Ventaja Principal**: Grounding es INTELIGENTE - solo se usa cuando realmente es necesario, no por defecto.
