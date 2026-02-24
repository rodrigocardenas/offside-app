# Bug #6: Testing Guide - Validación de Prevención de Preguntas Duplicadas

## Pruebas Manuales

### Pre-requisitos
```bash
# Asegurarse que hay partidos próximos sin iniciar
php artisan tinker
>>> FootballMatch::where('status', 'Not Started')->count();
// Debe haber al menos 3 partidos

>>> Group::first(); // Anotar el ID del grupo (ej: 1)
```

---

## Test 1: No Se Crean Duplicadas Cuando Job Se Ejecuta 2 Veces

**Objetivo:** Verificar que `firstOrCreate()` con las nuevas claves previene duplicados.

**Pasos:**

1. **Limpiar preguntas existentes (opcional)**
   ```bash
   php artisan tinker
   
   $groupId = 1; // Cambiar según tu setup
   $matchId = 100; // Partido que usaremos para test
   
   Question::where('group_id', $groupId)
       ->where('match_id', $matchId)
       ->forceDelete(); // forceDelete porque está soft-deleted
   ```

2. **Ejecutar job primera vez**
   ```bash
   php artisan queue:work --once
   
   # O si no está en queue:
   php artisan tinker
   >>> dispatch(new \App\Jobs\CreatePredictiveQuestionsJob());
   ```

3. **Verificar cantidad de preguntas creadas**
   ```bash
   php artisan tinker
   
   $firstRun = Question::where('group_id', 1)
       ->where('type', 'predictive')
       ->where('created_at', '>', now()->subMinutes(5))
       ->count();
   
   echo "Preguntas después primer run: " . $firstRun;
   ```

4. **Ejecutar job segunda vez (inmediatamente)**
   ```bash
   php artisan dispatch CreatePredictiveQuestionsJob
   ```

5. **Verificar que NO aumentó el count**
   ```bash
   php artisan tinker
   
   $secondRun = Question::where('group_id', 1)
       ->where('type', 'predictive')
       ->where('created_at', '>', now()->subMinutes(5))
       ->count();
   
   echo "Preguntas después segundo run: " . $secondRun;
   echo "Diferencia: " . ($secondRun - $firstRun); // Debe ser 0
   ```

**Resultado Esperado:** `Diferencia: 0` (No hay duplicadas)

---

## Test 2: Preguntas Expiradas No Bloquean Nuevas

**Objetivo:** Verificar que una pregunta expirada no impide crear una nueva del mismo partido.

**Pasos:**

1. **Crear pregunta expirada manualmente**
   ```bash
   php artisan tinker
   
   $match = FootballMatch::where('status', 'Not Started')
       ->orderBy('date')
       ->first();
   $group = Group::first();
   
   $expiredQuestion = Question::create([
       'type' => 'predictive',
       'group_id' => $group->id,
       'match_id' => $match->id,
       'title' => 'Expired Question - ' . now(),
       'description' => 'This question has expired',
       'available_until' => now()->subMinutes(30), // ← Expirada hace 30 min
       'template_question_id' => TemplateQuestion::where('type', 'predictive')->first()->id ?? 1,
   ]);
   
   echo "Created expired question ID: " . $expiredQuestion->id;
   
   // Crear opciones
   QuestionOption::create([
       'question_id' => $expiredQuestion->id,
       'text' => 'Option 1',
       'is_correct' => true
   ]);
   QuestionOption::create([
       'question_id' => $expiredQuestion->id,
       'text' => 'Option 2',
       'is_correct' => false
   ]);
   ```

2. **Verificar que está expirada**
   ```bash
   php artisan tinker
   
   $q = Question::find($expiredQuestion->id);
   echo $q->available_until . " < " . now() . " = " . ($q->available_until < now() ? 'TRUE (Expirada)' : 'FALSE');
   ```

3. **Ejecutar job**
   ```bash
   php artisan dispatch CreatePredictiveQuestionsJob
   ```

4. **Verificar que se creó una NUEVA pregunta del mismo partido**
   ```bash
   php artisan tinker
   
   $questionsForMatch = Question::where('match_id', $match->id)
       ->where('group_id', $group->id)
       ->get(['id', 'title', 'available_until', 'created_at']);
   
   echo "Total preguntas del partido: " . $questionsForMatch->count();
   $questionsForMatch->each(function($q) {
       echo "ID: {$q->id} | Available: {$q->available_until} | Created: {$q->created_at}\n";
   });
   ```

**Resultado Esperado:** 
- Debe haber 2 registros (expirada + nueva)
- La nueva debe tener `available_until` en el futuro

---

## Test 3: Validación en Model (Exception)

**Objetivo:** Verificar que el boot hook en Question model previene creación duplicada.

**Pasos:**

1. **Crear una pregunta predictiva**
   ```bash
   php artisan tinker
   
   $match = FootballMatch::where('status', 'Not Started')->first();
   $group = Group::first();
   
   $q1 = Question::create([
       'type' => 'predictive',
       'group_id' => $group->id,
       'match_id' => $match->id,
       'title' => 'Test Q1',
       'available_until' => now()->addHours(2),
       'template_question_id' => TemplateQuestion::where('type', 'predictive')->first()->id ?? 1,
   ]);
   echo "Created Q1: " . $q1->id;
   
   // Crear opción
   QuestionOption::create([
       'question_id' => $q1->id,
       'text' => 'Opt1',
       'is_correct' => true
   ]);
   ```

2. **Intentar crear OTRA pregunta para el mismo match/group (debe fallar)**
   ```bash
   php artisan tinker
   
   try {
       $q2 = Question::create([
           'type' => 'predictive',
           'group_id' => $group->id,
           'match_id' => $match->id,
           'title' => 'Test Q2 (Duplicate)',
           'available_until' => now()->addHours(2),
           'template_question_id' => TemplateQuestion::where('type', 'predictive')->first()->id ?? 1,
       ]);
       echo "ERROR: Se creó Q2 cuando debería haber fallado!";
   } catch (\Exception $e) {
       echo "✓ Excepción capturada: " . $e->getMessage();
   }
   ```

**Resultado Esperado:** 
```
✓ Excepción capturada: Una pregunta predictiva para el partido X ya existe en el grupo Y
```

---

## Test 4: Preguntas Sociales No Se Ven Afectadas

**Objetivo:** Verificar que el fix solo aplica a preguntas predictivas, no a sociales.

**Pasos:**

1. **Crear dos preguntas sociales (SÍ están permitidas ser múltiples)**
   ```bash
   php artisan tinker
   
   $group = Group::first();
   
   $s1 = Question::create([
       'type' => 'social',
       'group_id' => $group->id,
       'title' => 'Social Q1',
       'available_until' => now()->addHours(24),
       'template_question_id' => TemplateQuestion::where('type', 'social')->first()->id ?? 1,
   ]);
   
   $s2 = Question::create([
       'type' => 'social',
       'group_id' => $group->id,
       'title' => 'Social Q2',
       'available_until' => now()->addHours(24),
       'template_question_id' => TemplateQuestion::where('type', 'social')->first()->id ?? 1,
   ]);
   
   echo "Social Q1: " . $s1->id . "\n";
   echo "Social Q2: " . $s2->id . "\n";
   ```

**Resultado Esperado:**
- Ambas se crean exitosamente (no hay validación de duplicados para tipo 'social')

---

## Test 5: Verificar Logs

**Objetivo:** Revisar que los logs registren intentos de crear duplicadas.

**Pasos:**

1. **Ver logs en tiempo real**
   ```bash
   # Terminal 1: Monitorear logs
   tail -f storage/logs/laravel.log
   ```

2. **En otra terminal, ejecutar el job**
   ```bash
   # Terminal 2
   php artisan dispatch CreatePredictiveQuestionsJob
   ```

3. **Buscar en logs**
   ```bash
   grep -i "duplicate\|deduplication\|attempt to create" storage/logs/laravel.log
   ```

**Resultado Esperado:**
- Si hay duplicadas intentadas: `WARNING: Attempt to create duplicate predictive question`
- Si no hay intentos: Log normal sin warnings

---

## Script de Testing Automático (Tinker)

```bash
php artisan tinker <<'EOF'
echo "=== Bug #6 Duplicate Prevention Tests ===\n";

$groupId = 1;
$match = FootballMatch::where('status', 'Not Started')->first();

if (!$match) {
    echo "ERROR: No hay partidos próximos\n";
    exit;
}

echo "\n1. Limpiando preguntas previas del partido " . $match->id . "...\n";
Question::where('match_id', $match->id)
    ->where('group_id', $groupId)
    ->forceDelete();

echo "2. Ejecutando job (primera vez)...\n";
dispatch(new \App\Jobs\CreatePredictiveQuestionsJob());

$count1 = Question::where('match_id', $match->id)
    ->where('group_id', $groupId)
    ->where('type', 'predictive')
    ->count();
echo "   - Preguntas creadas: $count1\n";

echo "\n3. Ejecutando job (segunda vez)...\n";
dispatch(new \App\Jobs\CreatePredictiveQuestionsJob());

$count2 = Question::where('match_id', $match->id)
    ->where('group_id', $groupId)
    ->where('type', 'predictive')
    ->count();
echo "   - Preguntas después: $count2\n";

$diff = $count2 - $count1;
echo "\n✓ RESULTADO: Diferencia = $diff (Esperado: 0)\n";
echo ($diff === 0 ? "✅ TEST PASSED" : "❌ TEST FAILED") . "\n";
EOF
```

---

## Checklist de Validación Post-Deploy

- [ ] Ejecutar job 2 veces consecutivas → No hay duplicadas
- [ ] Preguntas expiradas no bloquean nuevas del mismo partido
- [ ] Modelo Question rechaza duplicadas con Exception
- [ ] Preguntas sociales siguen funcionando normalmente
- [ ] Logs registran intentos de duplicados bloqueados
- [ ] Usuarios NO ven el mismo partido múltiples veces

---

## Rollback (Si algo falla)

```bash
# Revertir cambios
git checkout app/Traits/HandlesQuestions.php
git checkout app/Models/Question.php

# Limpiar cache
php artisan cache:clear
php artisan config:clear
```
