# IMPROVEMENTS: Match Ordering & Fuzzy Matching with API IDs

## Commit: `32ecced`

Se realizaron dos mejoras importantes en el sistema de verificación de preguntas:

### 1️⃣ **Fix: Match Ordering - Ahora muestra matches MÁS RECIENTES primero**

#### Problema
Cuando ejecutabas el comando con `--days=15 --limit=500`, mostraba matches antiguos primero en lugar de los más recientes.

```bash
php artisan app:force-verify-questions --days=15 --limit=500
# Mostraba: Match #458 (28 ENE), Match #459 (28 ENE)...
# Debería mostrar: Los matches más recientes DENTRO de esos 15 días
```

#### Causa
El comando ordenaba por `updated_at DESC` (fecha de última actualización):

```php
$matches = $query
    ->orderByDesc('updated_at')  // ❌ Trae el último UPDATE, no el más reciente
    ->limit($limit)
    ->get();
```

#### Solución
Cambiar ordenamiento a `date DESC` (fecha del partido):

```php
$matches = $query
    ->orderByDesc('date')  // ✅ Trae los matches más recientes primero
    ->limit($limit)
    ->get();
```

#### Impacto
- ✅ El comando ahora muestra primero los matches más recientes dentro del rango
- ✅ Los matches terminados hace poco se verifican primero (prioridad correcta)
- ✅ Máximo 500 matches = traes los 500 más recientes, no los 500 más actualizados

**Ejemplo:**
```bash
# Antes
php artisan app:force-verify-questions --days=15 --limit=5
# 1. Match #458: 28-01 ← antiguo
# 2. Match #459: 28-01 ← antiguo

# Después
php artisan app:force-verify-questions --days=15 --limit=5
# 1. Match #531: 01-02 ← más reciente ✓
# 2. Match #461: 28-01
# 3. Match #460: 28-01
# ...
```

---

### 2️⃣ **Improvement: Team Matching with API IDs**

#### Problema
El sistema solo hacía fuzzy matching por nombres de equipos, lo que causaba:
- Errores cuando nombres variaban (ej: "Man City" vs "Manchester City")
- No aprovechaba los IDs de la API que son más fiables

#### Solución
Mejorado el `teamNameMatches()` para usar IDs de la API como fallback:

```php
/**
 * Ahora acepta opcional el ID de la API del equipo
 */
private function teamNameMatches(
    string $optionText, 
    string $teamName, 
    ?int $teamApiId = null
): bool
{
    // 1. Match exacto por nombre
    if ($optionLower === $teamLower) {
        return true;
    }
    
    // 2. Contains check
    if (strpos($optionLower, $teamLower) !== false) {
        return true;
    }
    
    // 3. Fuzzy: Levenshtein distance
    $distance = levenshtein($optionLower, $teamLower);
    $threshold = ceil($maxLen * 0.3);
    if ($distance <= $threshold) {
        return true;
    }
    
    // 4️⃣ [NUEVO] Match por ID de API
    if ($teamApiId !== null) {
        if (preg_match('/\b' . preg_quote($teamApiId) . '\b/', $optionText)) {
            return true;  // ✅ La opción contiene el ID del equipo
        }
    }
    
    return false;
}
```

#### Implementación en `evaluateFirstGoal()`

```php
// Cargar IDs de equipos (cached)
$teamIds = $this->getTeamApiIds($match);
$homeTeamId = $teamIds['home_id'] ?? null;
$awayTeamId = $teamIds['away_id'] ?? null;

// Usar IDs para identificar qué equipo anotó
$scoringTeamId = null;
if ($this->teamNameMatches($firstGoalTeam, $match->home_team, $homeTeamId)) {
    $scoringTeamId = $homeTeamId;
}

// Comparar opciones usando NOMBRE + ID
foreach ($question->options as $option) {
    // Match por nombre
    if ($this->teamNameMatches($option->text, $firstGoalTeam)) {
        $correctOptionIds[] = $option->id;
    }
    // Match por ID (si la opción menciona el ID del equipo)
    elseif ($scoringTeamId !== null && preg_match('/\b' . $scoringTeamId . '\b/', $option->text)) {
        $correctOptionIds[] = $option->id;
    }
}
```

#### Cache de IDs
Agregado caché `$teamApiIdsCache` para evitar múltiples queries:

```php
private function getTeamApiIds(FootballMatch $match): ?array
{
    // Verificar caché
    if (isset($this->teamApiIdsCache[$match->id])) {
        return $this->teamApiIdsCache[$match->id];  // ✅ Rápido
    }

    // Cargar solo si necesario
    $homeTeam = $match->homeTeam()->first();
    $awayTeam = $match->awayTeam()->first();

    $ids = [
        'home_id' => (int) $homeTeam->external_id,
        'away_id' => (int) $awayTeam->external_id,
    ];

    // Cachear para futuras llamadas
    $this->teamApiIdsCache[$match->id] = $ids;
    return $ids;
}
```

#### Impacto
- ✅ Mejor precisión al identificar equipos
- ✅ Soporte para opciones que usan IDs de API (ej: "Equipo #501")
- ✅ Fallback a fuzzy matching si ID no funciona
- ✅ Cache optimiza performance (no cargas IDs múltiples veces)
- ✅ Más robusto ante variaciones de nombres

---

## Cómo Usar

### Caso 1: Verificar matches recientes

```bash
# Trae los últimos 15 días de matches terminados (los más recientes primero)
php artisan app:force-verify-questions --days=15 --limit=500

# O los últimos 7 días
php artisan app:force-verify-questions --days=7 --limit=50
```

### Caso 2: Re-verificar matches específicos

```bash
# Re-verifica un match específico
php artisan app:force-verify-questions --match-id=531

# Re-verifica los últimos 2 días
php artisan app:force-verify-questions --days=2 --re-verify
```

### Caso 3: Dry run (ver qué hará)

```bash
# Previsualiza sin ejecutar
php artisan app:force-verify-questions --days=15 --limit=500 --dry-run
```

---

## Files Modified

1. **app/Console/Commands/ForceVerifyQuestionsCommand.php**
   - Línea 77: Cambio de `orderByDesc('updated_at')` a `orderByDesc('date')`
   
2. **app/Services/QuestionEvaluationService.php**
   - Línea 44: Agregado `$teamApiIdsCache`
   - Línea 1023-1076: Mejorado `teamNameMatches()` con soporte API ID
   - Línea 1586-1618: Agregado `getTeamApiIds()` method
   - Línea 338-396: Actualizado `evaluateFirstGoal()` para usar IDs

---

## Testing Recomendado

```bash
# 1. Verifica que los matches se traen en orden correcto
php artisan app:force-verify-questions --days=7 --limit=5 --dry-run

# 2. Ejecuta la verificación
php artisan app:force-verify-questions --days=7 --limit=5

# 3. Revisa los logs
tail -f storage/logs/laravel.log | grep "question verified"

# 4. Verifica puntos asignados
mysql offside2 -e "SELECT COUNT(*) as total_points FROM answers WHERE points_earned > 0 AND created_at > NOW() - INTERVAL 1 HOUR;"
```

---

**Status**: ✅ Deployed to production
**Commit**: 32ecced
**Files**: 3 modified, 144 insertions(+), 6 deletions(-)
