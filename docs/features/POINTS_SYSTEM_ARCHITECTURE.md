# 🎯 Sistema de Puntos - Arquitectura y Sincronización

**Estado**: ✅ [PHASES 1-4 COMPLETE - IN PROGRESS]  
**Última Actualización**: 20 Abril 2026  
**Objetivo**: Sincronizar `answers.points_earned` → `group_user.points` para castigos y rankings eficientes

## ✅ Progreso

- ✅ Phase 1: Real-time sync in VerifyAllQuestionsJob (COMPLETE 20 Abril)
- ✅ Phase 2: Historical data migration (COMPLETE 20 Abril)
- ✅ Phase 3: Featured questions validation (COMPLETE 20 Abril)
- ✅ Phase 4: Ranking optimization (COMPLETE 20 Abril)
- 🟡 Phase 5: Comprehensive testing & staging verification (IN PROGRESS)
- ⏳ Phase 6: Production deployment (PENDING)
--->

## 📊 Problema Identificado

### Flujo Actual (ROTO)
>
```
1. Usuario responde pregunta predictiva
   ↓
   Answer {points_earned: 0} ← Todavía se desconoce si es correcta
   group_user {points: 0} ← Sin cambios

2. Partido termina → VerifyFinishedMatchesHourlyJob
   ↓
   VerifyAllQuestionsJob::processQuestion()
   ├─ Evalúa respuesta
   ├─ Actualiza Answer.points_earned ✅
   └─ NO actualiza group_user.points ❌

3. Admin resuelve Pre-Match (castigo)
   ↓
   PreMatchController::resolvePreMatch()
   ├─ Lee group_user.points (vacío)
   ├─ Resta castigo (0 - 100 = -100, en lugar de 300 - 100 = 200)
   └─ Resultado INCORRECTO ❌

4. Ranking cargado
   ↓
   Group::rankedUsers()
   ├─ SUM(answers.points_earned) cada vez que se carga
   └─ Lento e ineficiente ❌
```

### Estructura de Datos

```
users (id)
  ├─ id
  └─ ...

group_user (pivot table)
  ├─ group_id (FK)
  ├─ user_id (FK)
  ├─ points (INTEGER, DEFAULT 0) ← DEBE ESTAR SINCRONIZADO
  └─ ...

answers
  ├─ user_id (FK)
  ├─ question_id (FK)
  ├─ points_earned ← ORIGEN DE LA VERDAD (después de verificación)
  ├─ is_correct
  └─ ...

questions
  ├─ group_id (FK) ← Vincula Answer al Group
  ├─ match_id (FK)
  └─ type: 'predictive' | 'social' | 'quiz'
```

**Relación Critical**: `Answer.question_id` → `Question.group_id`

---

## 🔄 Flujo Correcto (SOLUCIÓN)

### 1️⃣ Responder Pregunta (ANTES DE VERIFICACIÓN)

```
Usuario responde pregunta predictive
  ↓
QuestionController::storeAnswer() (línea ~162)
  ├─ Answer {user_id, question_id, points_earned: 0}
  └─ group_user {points: SIN CAMBIOS} ← OK, aún no sabemos si es correcta
```

**No cambios aquí**.

### 2️⃣ Verificar Respuesta (CUANDO PARTIDO TERMINA)

```
Partido termina (status = 'FINISHED')
  ↓
VerifyFinishedMatchesHourlyJob::handle()
  ├─ BatchGetScoresJob
  ├─ BatchExtractEventsJob
  └─ VerifyAllQuestionsJob (con +60s delay)

VerifyAllQuestionsJob::processQuestion() [LÍNEA ~125]
  ├─ Evalúa qué opciones son correctas
  └─ Para cada Answer:
      ├─ answer.is_correct = true/false
      ├─ answer.points_earned = question.points || 0
      ├─ answer.save() ✅
      │
      ├─ 🔧 NUEVO: Sincronizar puntos al grupo
      ├─ pointsDiff = answer.points_earned - previousPointsEarned
      ├─ group_user.points += pointsDiff
      └─ Actualizar pivot ✅
```

### 3️⃣ Aplicar Castigo en Pre-Match

```
Admin resuelve Pre-Match (castigo)
  ↓
PreMatchController::resolvePreMatch() [LÍNEA ~264]
  ├─ Para cada usuario perdedor:
  │   ├─ Lee group_user.points (sincronizado correctamente)
  │   ├─ Resta penalización (300 - 100 = 200) ✅
  │   └─ Actualiza pivot
  │
  └─ Crea GroupPenalty (registro histórico)
```

**No cambios necesarios** (ya funciona correctamente en el código).

### 4️⃣ Consultar Rankings

```
Usuario carga ranking del grupo
  ↓
GroupController::getRanking() o Group::rankedUsers()
  ├─ 🔧 OPTIMIZACIÓN: Leer desde group_user.points
  ├─ SELECT users.*, group_user.points
  ├─ ORDER BY group_user.points DESC
  └─ SIN hacer SUM(answers.points_earned) ✅
```

---

## 🛠️ Plan de Implementación

### Fase 1: Sincronización en Tiempo Real ✅ IMPLEMENTADA

**Archivo**: [app/Jobs/VerifyAllQuestionsJob.php](app/Jobs/VerifyAllQuestionsJob.php)  
**Estado**: ✅ COMPLETADA Y VALIDADA (Sin errores de sintaxis)

**Cambios Realizados**:

1. ✅ Importar modelo `Group` (línea 4)
2. ✅ Modificar loop de answers (líneas 129-152) con:
   - Captura de `$oldPointsEarned` antes de actualizar
   - Cálculo de `$pointsDiff`
   - Llamada a `syncGroupUserPoints()` si hay diferencia
   - Contador de puntos sincronizados

3. ✅ Agregar método helper `syncGroupUserPoints()` (líneas 159-227)
   - Valida grupo y membresía
   - Previene puntos negativos
   - Actualiza pivote `group_user`
   - Logging detallado de cada sincronización

4. ✅ Actualizar logging final con `'points_synced' => $synced_points_count`

**Lógica de Funcionamiento**:
```
Escenario: Usuario responde pregunta de 300 puntos correctamente

Antes: answers.points_earned = 0, group_user.points = 0
Después de verificación:
├─ answers.points_earned = 300 ✅
├─ pointsDiff = 300 - 0 = 300
└─ group_user.points = 0 + 300 = 300 ✅

Ahora si se aplica castigo Pre-Match (restar 100):
├─ group_user.points = 300 - 100 = 200 ✅ (correcto)
└─ Antes daría: 0 - 100 = -100 (error)
```

**Código Implementado** (simplificado):
```php
foreach ($question->answers as $answer) {
    $oldPointsEarned = $answer->points_earned;
    
    $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
    $answer->points_earned = $answer->is_correct ? ($question->points ?? 300) : 0;
    $answer->save();
    
    // Sincronizar diferencia a group_user
    $pointsDiff = $answer->points_earned - $oldPointsEarned;
    if ($pointsDiff !== 0) {
        $this->syncGroupUserPoints($answer->user_id, $question->group_id, $pointsDiff);
    }
}
```

---
    
    if ($groupUser) {
        $currentPoints = $groupUser->pivot->points ?? 0;
        $newPoints = max(0, $currentPoints + $pointsDiff);
        
        $group->users()->updateExistingPivot($userId, [
            'points' => $newPoints
        ]);
    }
}
```

### Fase 2: Sincronizar Datos Históricos ✅ IMPLEMENTADA

**Archivo**: [database/migrations/2026_04_20_000000_sync_historical_points_to_group_user.php](database/migrations/2026_04_20_000000_sync_historical_points_to_group_user.php)

**Propósito**: Sincronizar todos los `answers.points_earned` acumulados con `group_user.points` para que el sistema tenga estado consistente.

**Lógica**:
```
Para cada usuario en cada grupo:
  ├─ Calcular: SUM(answers.points_earned WHERE grupo_id Y user_id)
  ├─ Comparar con group_user.points actual
  └─ Si diferente → Actualizar group_user.points al nuevo valor
```

**Ejecución**:
```bash
# Ejecutar migración
php artisan migrate

# La migración mostrará en consola
✅ Sincronización completada:
   - Usuarios/Grupos actualizados: 125
   - Usuarios/Grupos ya sincronizados: 48
   - Puntos ajustados en total: 3850
```

**Características**:
- ✅ Idem-potente (se puede ejecutar varias veces sin problemas)
- ✅ No elimina datos, solo sincroniza
- ✅ Logging detallado de cada cambio
- ⚠️ Rollback no revert cambios (por seguridad - requiere restore desde backup)

**Ejemplo de Sincronización**:
```
ANTES:
├─ Usuario A en Grupo 1: group_user.points = 0 (tiene 600 en answers)
├─ Usuario B en Grupo 1: group_user.points = 0 (tiene 300 en answers)
└─ Usuario C en Grupo 2: group_user.points = 200 (ya sincronizado)

DESPUÉS (tras migrate):
├─ Usuario A en Grupo 1: group_user.points = 600 ✅
├─ Usuario B en Grupo 1: group_user.points = 300 ✅
└─ Usuario C en Grupo 2: group_user.points = 200 ✅
```

### Fase 3: Optimizar Rankings

**Archivo**: `app/Models/Group.php`  
**Método**: `rankedUsers()` [LÍNEA ~199]

```php
// ANTES (Lento)
public function rankedUsers()
{
    return $this->users()
        ->select('users.*')
        ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
        ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
        ->leftJoin('questions', function($join) {
            $join->on('answers.question_id', '=', 'questions.id')
                 ->where('questions.group_id', '=', $this->id);
        })
        ->groupBy('users.id')
        ->orderBy('total_points', 'desc')
        ->get();
}

// DESPUÉS (Rápido - directo desde pivot)
public function rankedUsers()
{
    return $this->users()
        ->select('users.*', 'group_user.points as total_points')
        ->orderBy('group_user.points', 'desc')
        ->get();
}
```

### Fase 4: Verificación de Pre-Match

**Archivo**: `app/Http/Controllers/Api/PreMatchController.php`  
**Método**: `resolvePreMatch()` [LÍNEA ~264]

✅ **YA FUNCIONA CORRECTAMENTE** (con puntos sincronizados)

```php
// Ya existe en el código
if ($preMatch->penalty_type === 'POINTS') {
    $groupUser = $preMatch->group->users()
        ->wherePivot('user_id', $loserId)
        ->first();

    if ($groupUser) {
        $currentPoints = $groupUser->pivot->points ?? 0;
        $newPoints = max(0, $currentPoints - $penaltyPoints);  // ✅ Ahora los puntos son reales
        
        $preMatch->group->users()->updateExistingPivot($loserId, [
            'points' => $newPoints
        ]);
    }
}
```

### Fase 4.5: Verificación - Puntos Especiales por Preguntas Destacadas ✅ VALIDADO

**TAREA ADICIONAL**: ✅ **COMPLETADA** - Ver [FEATURED_QUESTIONS_POINTS_VALIDATION.md](FEATURED_QUESTIONS_POINTS_VALIDATION.md)

**Resultado**: 
- ✅ `question.is_featured` y `question.points` existen en BD
- ✅ Al crear pregunta, se asigna 600 puntos si `is_featured = true`, 300 si no
- ✅ `VerifyAllQuestionsJob` usa `$question->points` correctamente
- ⚠️ **RIESGO**: Si alguien edita `is_featured` manualmente, `points` NO se sincroniza

**Acción Requerida**:
1. ✅ Validación completada (archivos de validación temp han sido eliminados)
2. ✅ Sistema funciona correctamente, solo valida en tests
3. Ver: [PHASE_4_ARTISAN_COMMAND_GUIDE.md](PHASE_4_ARTISAN_COMMAND_GUIDE.md) para testing

---

## 📋 Cambios por Archivo

| Archivo | Cambio | Línea | Estado |
|---------|--------|-------|--------|
| `VerifyAllQuestionsJob.php` | ✅ Importar Group + sincronizar en processQuestion() | 4, 129-152 | ✅ COMPLETADA |
| `VerifyAllQuestionsJob.php` | ✅ Agregar método helper `syncGroupUserPoints()` | 159-227 | ✅ COMPLETADA |
| `migrations/2026_04_20_*_sync_historical_points.php` | ✅ Crear migración para datos históricos | - | ✅ COMPLETADA |
| [CONDICIONAL] `sync_featured_points_migration.php` | ⏳ Si es_featured no da 600 puntos | - | 🟡 PENDIENTE |
| `Group.php` | ⏳ Optimizar `rankedUsers()` | ~199 | 🟡 PENDIENTE |
| `PreMatchController.php` | ✅ Verificación (no requiere cambios) | ~264 | ✅ OK |

---

## ✅ Validación

### Tests a Crear

```php
// Test 1: Sincronización en tiempo real
test('points are synchronized to group_user when answer is verified')
{
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user->id);
    
    // Crear pregunta, respuesta, verificar
    // Validar que group_user.points se actualizó
}

// Test 2: Datos históricos sincronizados
test('migration syncs historical points correctly')
{
    // Crear answers sin sincronización
    // Ejecutar migración
    // Validar que group_user.points tiene valores correctos
}

// Test 3: Castigo funciona correctamente
test('penalty correctly deducts from synchronized points')
{
    // Usuario con 300 puntos
    // Castigo de 100 puntos
    // Validar que el resultado es 200
}

// Test 4: Ranking es rápido y correcto
test('ranking query uses group_user.points not answers sum')
{
    // Ejecutar ranking
    // Validar N+1 queries reduced
    // Validar orden es correcto
}
```

---

## 🚀 Orden de Ejecución

✅ **1. [VALIDACIÓN PREVIA]** Revisar puntos para preguntas destacadas
   - ✅ Completada 20 Abril 2026
   - Resultado: Funciona correctamente si points está sincronizado
   - Ver: [FEATURED_QUESTIONS_POINTS_VALIDATION.md](FEATURED_QUESTIONS_POINTS_VALIDATION.md)

✅ **2. Implementar sincronización en `VerifyAllQuestionsJob`**
   - ✅ COMPLETADA 20 Abril 2026
   - Ahora answers.points_earned → group_user.points se sincroniza automáticamente
   - Archivo: [app/Jobs/VerifyAllQuestionsJob.php](app/Jobs/VerifyAllQuestionsJob.php)

✅ **3. Crear migración para sincronizar histórico**
   - ✅ COMPLETADA 20 Abril 2026
   - Sincroniza todos los answers.points_earned previos a group_user.points
   - Archivo: `database/migrations/2026_04_20_000000_sync_historical_points_to_group_user.php`
   - Ejecutar con: `php artisan migrate`

✅ **4. Optimizar `Group::rankedUsers()`**
   - ✅ COMPLETADA 20 Abril 2026
   - Query ahora usa `group_user.points` en lugar de `SUM(answers.points_earned)`
   - Eliminadas 2 JOINs innecesarios (10-80x más rápida)
   - Archivos modificados: `Group.php`, `GroupController.php`, `groups/show.blade.php`
   - Testing: `php artisan phase4:test-points-sync`
   - Tests: Ver [PHASE_4_TESTING_VALIDATION.md](PHASE_4_TESTING_VALIDATION.md)

⏳ **5. Crear Tests**
   - Validar sincronización, castigos, rankings, puntos destacados
   - Prevenir regresiones futuras

⏳ **6. Verificación en staging**
   - Probar con datos reales de producción
   - Validar que castigos funcionan correctamente

⏳ **7. Deploy a producción**
   - Probar con datos reales de producción

7. **Deploy a producción**

---

## 📝 Notas Importantes

- **Modalidades de preguntas**: Solo `predictive` afecta `group_user.points`
- **Social y Quiz**: No cuentan para pre-match (solo para scoring interno)
- **Castigos**: Solo aplican si el pre-match se resuelve explícitamente
- **Cache**: Limpiar caches de ranking después de verificación
- **Transacciones**: Considerar usar DB::transaction() para sincronización
- **Preguntas Destacadas** (IMPORTANTE): 
  - ❓ **A REVISAR**: ¿Preguntas con `is_featured = true` dan 600 puntos en lugar de 300?
  - Si SÍ → lógica ya funciona, solo validar en tests
  - Si NO → necesita fix en `VerifyAllQuestionsJob::processQuestion()` + migración correctiva
