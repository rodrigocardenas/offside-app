# Bug #6: Partidos Repetidos en Preguntas Predictivas - Análisis y Solución

**Descripción del Bug:** 
El mismo partido aparece múltiples veces como pregunta predictiva dentro de un mismo grupo, permitiendo a usuarios predecir el resultado del mismo partido varias veces.

**Root Cause Analysis:**

### 1. Problema en `fillGroupPredictiveQuestions()` (Línea 218-226)

```php
$matchesSinPregunta = $matches->filter(function($match) use ($group) {
    return !\App\Models\Question::where('type', 'predictive')
        ->where('group_id', $group->id)
        ->where('match_id', $match->id)
        ->where('available_until', '>', now())  // ❌ AQUÍ EL PROBLEMA
        ->exists();
});
```

**Issue:** La condición `available_until > now()` solo considera preguntas *vigentes*. 

**Escenario problemático:**
1. Match X: Pregunta creada a 8:00 PM, `available_until` = 8:30 PM
2. 8:35 PM: Pregunta caduca (available_until <= now())
3. 8:40 PM: Job ejecuta `fillGroupPredictiveQuestions()` 
4. El filtro `available_until > now()` = FALSE → Pregunta expirada se ignora
5. ✅ Se crea OTRA pregunta para el mismo Match X
6. Resultado: Dos preguntas del mismo partido en el mismo grupo

### 2. Problema en `createQuestionFromTemplate()` (Línea 335-340)

```php
$question = Question::firstOrCreate([
    'title' => $questionData['title'],           // ⚠️ Puede variar
    'group_id' => $questionData['group_id'],
    'match_id' => $questionData['match_id'],
    'template_question_id' => $questionData['template_question_id']
], [ ... ]);
```

**Issue:** `title` puede no ser un buen identificador porque:
- Dos plantillas diferentes podrían generar títulos similares
- Variaciones en espacios o caracteres especiales no detectadas
- El verdadero identificador único debería ser `(match_id, group_id)`

---

## Solución Implementada

### Paso 1: Actualizar `fillGroupPredictiveQuestions()` 

Cambiar la validación para **incluir preguntas expiradas en las últimas 24 horas**:

```php
$matchesSinPregunta = $matches->filter(function($match) use ($group) {
    // Buscar preguntas del match en el grupo creadas en las últimas 24 horas
    // (vigentes o expiradas recientemente)
    return !\App\Models\Question::where('type', 'predictive')
        ->where('group_id', $group->id)
        ->where('match_id', $match->id)
        ->where('created_at', '>', now()->subHours(24))  // ✅ Última 24 horas
        ->exists();
});
```

### Paso 2: Mejorar `createQuestionFromTemplate()` 

Usar `firstOrCreate()` con las claves correctas: `(match_id, group_id, template_question_id)`:

```php
$question = Question::firstOrCreate([
    'match_id' => $questionData['match_id'],
    'group_id' => $questionData['group_id'],
    'template_question_id' => $questionData['template_question_id']
], [
    'type' => $template->type,
    'title' => $questionData['title'],
    // ... resto de datos
]);
```

**Beneficio:** Garantiza que para cada combinación (match, group, template) solo existe 1 pregunta, incluso si se ejecuta el job múltiples veces.

### Paso 3: Agregar Validación en Model (Opcional pero Recomendado)

En [Question.php](app/Models/Question.php), agregue una validación:

```php
public static function boot()
{
    parent::boot();
    
    static::creating(function ($question) {
        // Para preguntas predictivas, validar unicidad de (match_id, group_id)
        if ($question->type === 'predictive' && $question->match_id) {
            $exists = self::where('type', 'predictive')
                ->where('group_id', $question->group_id)
                ->where('match_id', $question->match_id)
                ->where('created_at', '>', now()->subHours(24))
                ->exists();
            
            if ($exists) {
                throw new \Exception("Una pregunta predictiva para este partido ya existe en este grupo");
            }
        }
    });
}
```

---

## Archivos Modificados

1. **[app/Traits/HandlesQuestions.php](app/Traits/HandlesQuestions.php)**
   - Línea 218-226: Cambiar `available_until > now()` → `created_at > now()->subHours(24)`
   - Línea 335-340: Cambiar keys de `firstOrCreate()` a `(match_id, group_id, template_question_id)`

2. **[app/Models/Question.php](app/Models/Question.php)** (Opcional)
   - Agregar validación en `boot()` si se requiere protección adicional

---

## Testing

### Caso de Prueba 1: Preguntas Expiradas No Bloquean Nuevas
```bash
# 1. Crear pregunta predictiva con available_until = hace 10 minutos
php artisan tinker
>>> $match = FootballMatch::where('is_featured', true)->first();
>>> $group = Group::first();
>>> Question::create([
    'type' => 'predictive',
    'match_id' => $match->id,
    'group_id' => $group->id,
    'title' => 'Test Q1',
    'available_until' => now()->subMinutes(10),
    'created_at' => now()->subHours(2)
]);

# 2. Ejecutar job
php artisan dispatch CreatePredictiveQuestionsJob

# 3. Verificar: Solo debe haber 1 pregunta de este match
>>> Question::where('match_id', $match->id)->where('group_id', $group->id)->count();
// Esperado: 1 (la expirada)
```

### Caso de Prueba 2: Preguntas Duplicadas No Se Crean
```bash
# 1. Crear pregunta predictiva vigente
php artisan tinker
>>> $match = FootballMatch::where('status', 'Not Started')->first();
>>> $group = Group::first();
>>> $template = TemplateQuestion::where('type', 'predictive')->first();
>>> Question::create([
    'type' => 'predictive',
    'match_id' => $match->id,
    'group_id' => $group->id,
    'template_question_id' => $template->id,
    'title' => 'Original',
    'available_until' => now()->addHours(2),
    'created_at' => now()->subMinutes(5)
]);

# 2. Ejecutar job (debería detectar que match ya tiene pregunta)
php artisan dispatch CreatePredictiveQuestionsJob

# 3. Verificar: Solo debe haber 1 pregunta
>>> Question::where('match_id', $match->id)->where('group_id', $group->id)->count();
// Esperado: 1 (sin duplicado)
```

### Caso de Prueba 3: Múltiples Plantillas Para Mismo Match
```bash
# Verificar que cada plantilla puede crear 1 pregunta por match/group
# pero no se crean duplicados con la misma plantilla

php artisan tinker
>>> $match = FootballMatch::first();
>>> $group = Group::first();

# Ejecutar job 2 veces rápido
php artisan dispatch CreatePredictiveQuestionsJob
// Wait 2 seconds
php artisan dispatch CreatePredictiveQuestionsJob

# Verificar sin duplicados
>>> Question::where('match_id', $match->id)->where('group_id', $group->id)->pluck('id');
// Debería tener el mismo número de registros ambas ejecuciones
```

---

## Impacto Esperado

- ✅ Usuarios no verán el mismo partido repetido
- ✅ Preguntas expiradas no bloquearán nuevas preguntas del mismo partido
- ✅ El job puede ejecutarse múltiples veces sin crear duplicados
- ✅ Mantiene compatibilidad con preguntas sociales (no afectadas)

## Status

- **Bug Original:** ✅ Identificado y documentado
- **Root Cause:** ✅ Analizado
- **Solución:** ⏳ Pendiente implementación (próximo paso)
