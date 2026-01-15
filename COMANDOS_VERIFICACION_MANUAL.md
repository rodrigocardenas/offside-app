# 📋 Comandos para Verificar Preguntas Manualmente

Dos nuevos comandos artisan para verificar preguntas y asignar puntos cuando los jobs fallan.

---

## 1️⃣ `questions:verify-answers` - Verificación Simple

**Uso básico**: Verifica todas las preguntas sin verificar

```bash
php artisan questions:verify-answers
```

**Con opciones**:

```bash
# Verificar solo preguntas de un partido específico
php artisan questions:verify-answers --match-id=123

# Forzar reverificación (incluso si ya están verificadas)
php artisan questions:verify-answers --force

# Descargar eventos antes de verificar (por defecto se activa si usas --match-id)
php artisan questions:verify-answers --match-id=123 --hydrate-events

# Procesar máximo 100 preguntas (por defecto 50)
php artisan questions:verify-answers --limit=100

# Combinadas
php artisan questions:verify-answers --match-id=123 --force --limit=50
```

**Salida**:
```
╔═══════════════════════════════════════════════════════════════╗
║ Verificación Manual de Respuestas de Preguntas               ║
╚═══════════════════════════════════════════════════════════════╝

📋 PASO 1: Buscando preguntas a verificar...
   Filtro: Match ID = 123
   Filtro: Sin verificar (result_verified_at = NULL)
✅ Encontradas 5 preguntas

📊 PASO 2: Verificando preguntas y asignando puntos...
 ████████████████░░░░ 50% [50 / 100]

════════════════════════════════════════════════════════════════
✅ VERIFICACIÓN COMPLETADA
════════════════════════════════════════════════════════════════

Resultados:
  ├─ Exitosas: 48 ✅
  ├─ Fallidas: 2 ❌
  └─ Saltadas: 0 ⏭️

Tasa de éxito: 96%

📈 DETALLES POR TIPO:
  ├─ winner: 12 verificadas
  ├─ first_goal: 18 verificadas
  ├─ exact_score: 15 verificadas
  └─ yellow_cards: 3 verificadas

💰 PUNTOS ASIGNADOS: 14400 puntos
```

---

## 2️⃣ `questions:repair` - Reparación Avanzada

**Uso básico**: Buscar y reparar todos los partidos finalizados

```bash
php artisan questions:repair
```

**Opciones disponibles**:

```bash
# Reparar un partido específico
php artisan questions:repair --match-id=123

# Especificar estado del partido
php artisan questions:repair --status=FINISHED

# Partidos finalizados hace 2-12 horas
php artisan questions:repair --min-hours=2 --max-hours=12

# Solo preguntas sin verificar
php artisan questions:repair --only-unverified

# Reprocesar TODAS las preguntas (resetea result_verified_at)
php artisan questions:repair --reprocess-all

# Ver detalles de cada pregunta procesada
php artisan questions:repair --show-details

# Combinaciones útiles
php artisan questions:repair --match-id=123 --reprocess-all --show-details
php artisan questions:repair --min-hours=1 --max-hours=4 --only-unverified
php artisan questions:repair --status=FINISHED --show-details
```

**Salida con --show-details**:
```
╔═══════════════════════════════════════════════════════════════╗
║ Reparación de Verificación de Preguntas (Modo Diagnóstico)    ║
╚═══════════════════════════════════════════════════════════════╝

📋 PASO 1: Buscando partidos...
   Filtro: Match ID = 123
✅ Encontrados 1 partidos

📊 PASO 2: Procesando partidos...

🏟️  Real Madrid vs Barcelona (3-0)
   Match ID: 123 | Status: Match Finished
   Datos: Gemini (web search - VERIFIED)
   Eventos detallados: ✅ SÍ
   📌 5 preguntas a procesar
      ✅ ¿Quién ganará? (1 opciones correctas, 45 respuestas)
      ✅ ¿Primer gol? (1 opciones correctas, 30 respuestas)
      ✅ ¿Score exacto? (1 opciones correctas, 20 respuestas)
      ✅ ¿Tarjetas amarillas? (2 opciones correctas, 15 respuestas)
      ⏭️  ¿Penalty goal? (Sin opción correcta)

════════════════════════════════════════════════════════════════
✅ REPARACIÓN COMPLETADA
════════════════════════════════════════════════════════════════

📊 ESTADÍSTICAS:
  ├─ Total procesadas: 5
  ├─ Verificadas: 4 ✅
  ├─ Sin opciones correctas: 1 ⏭️
  └─ Errores: 0 ❌

💯 Tasa de éxito: 80%

💰 Puntos totales asignados: 5500
```

---

## 🎯 Casos de Uso

### Caso 1: Los jobs no terminaron, verificar manualmente todo

```bash
# Opción rápida (sin detalles)
php artisan questions:verify-answers

# Opción con detalles (para debuggear)
php artisan questions:repair --show-details
```

### Caso 2: Reparar un partido específico que falló

```bash
# Match ID 123 con todas las preguntas
php artisan questions:verify-answers --match-id=123 --force

# O con más opciones de diagnóstico
php artisan questions:repair --match-id=123 --reprocess-all --show-details
```

### Caso 3: Verificar partidos de las últimas 2 horas

```bash
php artisan questions:repair --min-hours=2 --max-hours=0 --show-details
```

### Caso 4: Debuggear por qué fallan algunas preguntas

```bash
php artisan questions:repair --match-id=123 --show-details
```

Esto mostrará exactamente qué preguntas se verifican y cuáles fallan.

### Caso 5: Batch processing - Reprocesar todos los partidos del último día

```bash
# Partidos finalizados hace 0-24 horas
php artisan questions:repair --min-hours=24 --max-hours=0
```

---

## 🔍 Qué Hace Exactamente

### `questions:verify-answers`:
1. ✅ Busca preguntas sin verificar (o todas si --force)
2. ✅ Evalúa cada pregunta usando `QuestionEvaluationService`
3. ✅ Actualiza `options.is_correct`
4. ✅ Actualiza `answers.is_correct` y `answers.points_earned`
5. ✅ Marca `questions.result_verified_at = now()`
6. ✅ Muestra estadísticas finales

### `questions:repair`:
1. ✅ Busca partidos con filtros avanzados
2. ✅ Para cada partido, procesa sus preguntas
3. ✅ Muestra información del partido (source, eventos JSON, etc.)
4. ✅ Verifica cada pregunta y asigna puntos
5. ✅ Opcionalmente muestra detalles de cada una
6. ✅ Muestra estadísticas detalladas por partido

---

## 📊 Campos Que Se Actualizan

Cuando se ejecutan estos comandos:

```php
// Para cada opción de pregunta
$option->is_correct = true/false; // Si es una opción correcta

// Para cada respuesta de usuario
$answer->is_correct = true/false;                  // Si respondió correctamente
$answer->points_earned = 300 o 0;                // Puntos ganados
$answer->updated_at = now();                      // Marca de tiempo

// Para cada pregunta
$question->result_verified_at = now();             // Marca como verificada
$question->updated_at = now();
```

---

## 🔒 Validaciones

Ambos comandos verifican:
- ✅ El match existe y está finalizado
- ✅ La pregunta tiene opciones
- ✅ Las respuestas están asociadas correctamente
- ✅ Los puntos se calculan según `question.points`
- ✅ Solo actualiza si los valores cambian (no guarda innecesariamente)

---

## 📝 Logs

Todas las operaciones se registran en `storage/logs/laravel.log`:

```
[2026-01-14 14:30:22] local.INFO: Verificación manual de respuestas completada {
  "total_processed": 50,
  "success": 48,
  "failures": 2,
  "skipped": 0,
  "total_points_assigned": 14400
}

[2026-01-14 14:30:30] local.INFO: Reparación de verificación completada {
  "matches_processed": 3,
  "questions_total": 15,
  "questions_verified": 13,
  "questions_unverified": 1,
  "errors": 1,
  "points_assigned": 5500
}
```

---

## ⚠️ Notas Importantes

1. **Idempotente**: Puedes ejecutar estos comandos varias veces sin problemas
2. **Sin datos del partido**: Si el partido no tiene scores o datos, la pregunta se salta
3. **Preguntas sin opciones correctas**: Se marcan como ⏭️ y se saltan
4. **Points vacíos**: Si `question.points` es NULL, asigna 300 por defecto
5. **Progress bar**: Muestra barra de progreso para operaciones largas

---

## 🚀 Recomendación

**Para uso en producción**:
```bash
# Verificar cada 5 minutos si hay preguntas sin verificar
*/5 * * * * cd /path/to/app && php artisan questions:verify-answers > /dev/null 2>&1

# O más agresivo (cada minuto)
* * * * * cd /path/to/app && php artisan questions:verify-answers --limit=100 > /dev/null 2>&1
```

---

¡Usa estos comandos para asegurarte de que TODAS las preguntas se verifiquen y los puntos se asignen correctamente! 🎉
