# Force Verify Questions Command Guide

## Descripción

El comando `app:force-verify-questions` permite re-verificar preguntas de partidos específicos y asignar puntos a los usuarios, incluso si las preguntas ya fueron verificadas anteriormente.

## Uso Básico

```bash
php artisan app:force-verify-questions [OPTIONS]
```

## Opciones Disponibles

| Opción | Valor Default | Descripción |
|--------|---------------|-------------|
| `--days=N` | 30 | Número de días hacia atrás para buscar partidos |
| `--limit=N` | 100 | Máximo número de matches a procesar |
| `--match-id=ID` | - | ID específico del match (omite filtros de fecha) |
| `--re-verify` | - | Re-verifica preguntas ya verificadas y asigna puntos nuevamente |
| `--dry-run` | - | Solo previsualiza qué se verificaría sin ejecutar |

## Ejemplos de Uso

### 1. Ver qué se verificaría (sin ejecutar)

```bash
# Últimos 30 días, máximo 100 matches
php artisan app:force-verify-questions --dry-run

# Últimos 90 días, máximo 200 matches
php artisan app:force-verify-questions --days=90 --limit=200 --dry-run
```

### 2. Verificar preguntas pendientes (sin verificación previa)

```bash
# Matches con preguntas no verificadas de los últimos 30 días
php artisan app:force-verify-questions --limit=100

# Matches con preguntas no verificadas de los últimos 7 días
php artisan app:force-verify-questions --days=7 --limit=50
```

### 3. Re-verificar preguntas ya verificadas

**IMPORTANTE:** Esta opción resetea `result_verified_at` y `result` para que se verifiquen nuevamente.

```bash
# Re-verificar todos los partidos de los últimos 30 días
php artisan app:force-verify-questions --re-verify

# Re-verificar un match específico
php artisan app:force-verify-questions --match-id=445 --re-verify

# Re-verificar últimos 90 días con límite de 200 matches
php artisan app:force-verify-questions --re-verify --days=90 --limit=200
```

### 4. Previsualizar antes de re-verificar

```bash
# Ver qué se re-verificaría sin ejecutar realmente
php artisan app:force-verify-questions --re-verify --dry-run --days=30
```

## Flujo de Procesamiento

Cuando ejecutas el comando (sin `--dry-run`), ocurre lo siguiente:

1. **Búsqueda de matches** según los criterios (días, limit, match-id)
2. **Filtrado** según el tipo de verificación:
   - Sin `--re-verify`: Solo matches con preguntas pendientes (`result_verified_at = NULL`)
   - Con `--re-verify`: Todos los matches con cualquier pregunta
3. **Reset de datos** (solo si es `--re-verify`): 
   - Limpia `result_verified_at`
   - Limpia `result`
4. **Dispatch de jobs**:
   - `BatchGetScoresJob` → Obtiene scores del match
   - `BatchExtractEventsJob` → Extrae eventos
   - `VerifyAllQuestionsJob` → Verifica cada pregunta y asigna puntos

## Casos de Uso

### Caso 1: Backfill de preguntas antiguas

Algunos partidos antiguos tenían preguntas que no se verificaron:

```bash
php artisan app:force-verify-questions --days=90 --limit=200 --dry-run
# Si ves matches, ejecuta sin --dry-run
php artisan app:force-verify-questions --days=90 --limit=200
```

### Caso 2: Re-verificar después de mejorar algoritmos

Después de actualizar la lógica de evaluación, re-verifica partidos anteriores:

```bash
php artisan app:force-verify-questions --re-verify --days=30 --dry-run
# Ver si hay matches, luego ejecutar
php artisan app:force-verify-questions --re-verify --days=30
```

### Caso 3: Verificar un match específico

Para debugging o verificación puntual:

```bash
# Ver qué pasaría
php artisan app:force-verify-questions --match-id=445 --dry-run

# Ejecutar (verifica solo preguntas no verificadas)
php artisan app:force-verify-questions --match-id=445

# O re-verificar todas sus preguntas
php artisan app:force-verify-questions --match-id=445 --re-verify
```

## Output del Comando

El comando muestra:

```
📖 USAGE:
  php artisan app:force-verify-questions [OPTIONS]

📋 OPTIONS:
  --days=N       Número de días hacia atrás (default: 30)
  --limit=N      Máximo de matches a verificar (default: 100)
  --match-id=ID  ID específico del match (omite otros filtros)
  --re-verify    Re-verificar preguntas ya verificadas y asignar puntos
  --dry-run      Solo previsualizar sin ejecutar

🔍 FORCE VERIFY QUESTIONS
═══════════════════════════════════════════════════════════════
Days back: 30
Limit: 100
Match ID: ANY
Re-verify: NO
Dry Run: YES
═══════════════════════════════════════════════════════════════

✅ Encontrados 5 matches para VERIFICAR:

  Match #445
    • Real Madrid vs Monaco (6-1)
    • Fecha: 2026-01-31 20:00
    • Status: Finished
    • Preguntas: 3 verificadas, 2 pendientes (total: 5)
```

## Monitoreo

Puedes monitorear el progreso con:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -E "VerifyAllQuestionsJob|BatchGetScoresJob"

# Ver jobs en queue
php artisan queue:work --verbose
```

## Notas Importantes

⚠️ **Advertencias:**

1. **Re-verify** limpia puntos previamente asignados. Los usuarios perderán/recibirán puntos basado en la nueva verificación
2. El batch puede tardar varios minutos dependiendo del número de matches y preguntas
3. Asegúrate de ejecutar `php artisan queue:work` para que los jobs se procesen
4. Usa `--dry-run` siempre primero para ver qué se procesará

## Estado Actual de la Base de Datos (02-Feb-2026)

```
✅ Total matches finished (30 days): 85
   - status='Finished': 39
   - status='Match Finished': 46
   - status='FINISHED': 39

❓ Total pending questions: 15

🔍 Matches finished WITH pending questions: 0
   → Todas las preguntas ya fueron verificadas
```

Si quieres re-verificar todas las preguntas del último mes:

```bash
php artisan app:force-verify-questions --re-verify --days=30 --limit=100
```
