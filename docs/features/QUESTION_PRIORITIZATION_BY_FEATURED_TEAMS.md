# Plan: Priorización de Preguntas por Equipos Destacados

## 📋 Resumen Ejecutivo

Actualmente, las preguntas se generan a partir de partidos de las principales ligas sin considerar si los equipos involucrados son destacados. Esta tarea añade lógica de priorización para:

1. **Ordenar partidos por relevancia** - Priorizar aquellos con equipos `is_featured=true`
2. **Distribuir preguntas inteligentemente** - Alocar más preguntas a partidos de equipos destacados
3. **Mantener variedad** - Garantizar que también se cubran equipos no destacados

**Impacto**: Mayor engagement al mostrar primero partidos de equipos interesantes (Clásicos, equipos top, derbis).

---

## 🎯 Requisitos

### Datos Disponibles

```
teams.is_featured = boolean (TRUE = Equipo destacado)

Relaciones:
- Match {home_team_id → Team.id, away_team_id → Team.id}
- Question → FootballMatch
- FootballMatch.home_team, away_team → ID del equipo
```

### Casos de Uso

| Escenario | Comportamiento |
|-----------|---|
| Ambos equipos destacados | **MÁXIMA PRIORIDAD** (1.0x) - Clásico típico |
| 1 equipo destacado | **MEDIA PRIORIDAD** (0.7x) - Derby típico |
| Ningún equipo destacado | **BAJA PRIORIDAD** (0.3x) - Apoyo |

---

## 🏗️ Arquitectura de la Solución

### Componentes Involucrados

```
┌─────────────────────────────────────────────────────────────────┐
│                   GROUP CONTROLLER                              │
│  generateQuestionsForMatches() <- AQUÍ OCURRE LA MAGIA          │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│              HANDLES QUESTIONS TRAIT                            │
│  - fillGroupPredictiveQuestions()                              │
│  - createQuestionFromTemplate() ← ESTABLECE is_featured ✨     │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│            FOOTBALL MATCH MODEL                                 │
│  - orderByFeaturedTeams()          ← SCOPE NUEVO              │
│  - getFeaturedPriorityScore()      ← MÉTODO NUEVO             │
│  - shouldBeMarkedAsFeatured()      ← DETECTA FEATURED ✨       │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│              DATABASE (QUERIES MEJORADAS)                       │
│  - Join teams para validar is_featured                          │
│  - Order by priority score DESC                                 │
│  - Update questions.is_featured automáticamente                 │
└─────────────────────────────────────────────────────────────────┘
```

### Flujo de Ejecución

```
1. getMatchQuestions($group)
   └─→ fillGroupPredictiveQuestions($group)
       └─→ getPredictiveMatches()               [Obtener partidos próximos]
           └─→ sortMatchesByFeaturedTeams()    [NUEVO: Ordenar por is_featured]
               └─→ Resultado: [
                     {id: 1, priority: 1.0},   // Clásico
                     {id: 2, priority: 0.7},   // Derby
                     {id: 3, priority: 0.3},   // Otros
                   ]

2. createQuestionFromTemplate($template, $match, $group)
   └─→ Las preguntas se crean en orden de prioridad
       (Las primeras preguntas están sobre equipos destacados)
```

---

## 📝 Cambios Específicos Necesarios

### ✨ 0. Automatización de `is_featured` en Preguntas [NUEVO - IMPORTANTE]

**Objetivo**: Cuando se crea una pregunta, automáticamente establecer `questions.is_featured = true` si el partido es entre equipos destacados.

**Archivo**: `app/Models/FootballMatch.php`

```php
/**
 * Determinar si este match debería marcar sus preguntas como destacadas
 * TRUE = Clásico o Derby (al menos 1 equipo destacado)
 * FALSE = Partido regular
 */
public function shouldQuestionsBeMarkedAsFeatured(): bool
{
    $homeIsFeatured = $this->homeTeam?->is_featured ?? false;
    $awayIsFeatured = $this->awayTeam?->is_featured ?? false;
    
    return $homeIsFeatured || $awayIsFeatured;
}

/**
 * Getter: Valor de is_featured que debe tener la pregunta
 */
public function getQuestionFeaturedValue(): bool
{
    return $this->shouldQuestionsBeMarkedAsFeatured();
}
```

**Integración en `createQuestionFromTemplate()`** - `app/Traits/HandlesQuestions.php`:

```php
protected function createQuestionFromTemplate($template, $match, $group)
{
    try {
        // ... código existente hasta crear la pregunta ...
        
        $question = Question::create([
            'title' => $questionText,
            'description' => $questionText,
            'type' => $template->type,
            'points' => $template->points ?? 100,
            'group_id' => $group->id,
            'match_id' => $match->id,
            'available_until' => $availableUntil,
            // 🆕 NUEVO: Establecer is_featured automáticamente
            'is_featured' => $match->getQuestionFeaturedValue(),
        ]);
        
        // ... resto del código ...
    }
}
```

**También en `Question.create()`** - `app/Http/Controllers/GroupController.php`:

```php
protected function createQuestionFromTemplate($template, $match, $group)
{
    // ... código existente ...
    
    $question = Question::create([
        'title' => $questionData['title'],
        'description' => $questionData['description'],
        'type' => 'predictive',
        'points' => $questionData['points'],
        'group_id' => $questionData['group_id'],
        'match_id' => $questionData['match_id'],
        'available_until' => $questionData['available_until'],
        // 🆕 NUEVO: Automatizar is_featured basado en match
        'is_featured' => $match->getQuestionFeaturedValue(),
    ]);
    
    // ... resto del código ...
}
```

**También en otros lugares donde se crean preguntas** (e.g., `CreateQuestionsForFinishedMatches.php`):

```php
$question1 = Question::create([
    'title' => "¿Cuál fue el resultado del partido {$match->home_team} vs {$match->away_team}?",
    'description' => "{$match->home_team} vs {$match->away_team}",
    'type' => 'multiple_choice',
    'category' => 'predictive',
    'points' => 300,
    'group_id' => $group->id,
    'match_id' => $match->id,
    'available_until' => now()->addDays(7),
    // 🆕 NUEVO
    'is_featured' => $match->getQuestionFeaturedValue(),
]);
```

---

### Validación de Flujo: is_featured Auto-Population

```
1. Match se crea (vía API externo)
   └─→ home_team_id + away_team_id asignados

2. Question se crea para ese match
   └─→ Se ejecuta: $match->getQuestionFeaturedValue()
       ├─→ Consulta: homeTeam.is_featured + awayTeam.is_featured
       └─→ Retorna: TRUE si al menos 1 equipo es featured
   
3. Question se crea con is_featured = [TRUE|FALSE]
   └─→ Automático: Sin intervención manual

4. Vista puede filtrar/destacar questions donde is_featured = 1
   └─→ Mostrar primero preguntas destacadas
```

---

## 📝 Cambios Específicos Necesarios

### 1. Modelo FootballMatch - Añadir método Helper

**Archivo**: `app/Models/FootballMatch.php`

```php
/**
 * Calcular score de prioridad basado en equipos destacados
 * 1.0 = Ambos destacados (Clásico)
 * 0.7 = Un equipo destacado (Derby)
 * 0.3 = Ninguno destacado
 */
public function getFeaturedPriorityScore(): float
{
    $homeIsFeatured = $this->homeTeam?->is_featured ?? false;
    $awayIsFeatured = $this->awayTeam?->is_featured ?? false;
    
    if ($homeIsFeatured && $awayIsFeatured) {
        return 1.0; // Clásico
    } elseif ($homeIsFeatured || $awayIsFeatured) {
        return 0.7; // Derby
    }
    
    return 0.3; // Otros
}

/**
 * Determinar si las preguntas para este match deben ser marcadas como featured
 * @return bool TRUE si al menos un equipo es featured
 */
public function getQuestionFeaturedValue(): bool
{
    $homeIsFeatured = $this->homeTeam?->is_featured ?? false;
    $awayIsFeatured = $this->awayTeam?->is_featured ?? false;
    
    return $homeIsFeatured || $awayIsFeatured;
}

/**
 * Scope: Obtener partidos ordenados por equipos destacados
 */
public function scopeOrderByFeaturedTeams($query)
{
    return $query
        ->leftJoin('teams as home_teams', 'football_matches.home_team_id', '=', 'home_teams.id')
        ->leftJoin('teams as away_teams', 'football_matches.away_team_id', '=', 'away_teams.id')
        ->selectRaw('football_matches.*')
        ->selectRaw('
            CASE 
                WHEN home_teams.is_featured = 1 AND away_teams.is_featured = 1 THEN 1.0
                WHEN home_teams.is_featured = 1 OR away_teams.is_featured = 1 THEN 0.7
                ELSE 0.3
            END as featured_priority
        ')
        ->orderByDesc('featured_priority')
        ->orderByDesc('date');
}
```

### 3. Trait HandlesQuestions - Modificar logística

**Archivo**: `app/Traits/HandlesQuestions.php`

**Función a Modificar**: `fillGroupPredictiveQuestions()`

```php
protected function fillGroupPredictiveQuestions($group)
{
    // ... código existente ...
    
    // CAMBIO: Obtener partidos ORDENADOS por equipos destacados
    $matchesSinPregunta = FootballMatch::query()
        ->where('status', 'Not Started')
        ->where('date', '>', now())
        // 🆕 NUEVO: Ordenar por equipos destacados
        ->orderByFeaturedTeams()
        // 🆕 NUEVO: Limitar a próximos 20 partidos (después se filtra)
        ->limit(20)
        ->get()
        ->filter(function ($match) use ($group) {
            // Filtrar por competencia si es necesario
            return !$match->hasQuestions();
        });
    
    // ... resto de la lógica ...
}
```

### 4. Service: FootballDataService - Función Auxiliar

**Archivo**: `app/Services/FootballDataService.php`

```php
/**
 * Calcular score de prioridad para un partido
 * Útil para APIs externas que no retornan is_featured
 */
public function calculateMatchPriorityFromNames(array $match): float
{
    $homeTeam = Team::where('name', 'like', "%{$match['homeTeam']['name']}%")->first();
    $awayTeam = Team::where('name', 'like', "%{$match['awayTeam']['name']}%")->first();
    
    $homeIsFeatured = $homeTeam?->is_featured ?? false;
    $awayIsFeatured = $awayTeam?->is_featured ?? false;
    
    if ($homeIsFeatured && $awayIsFeatured) {
        return 1.0;
    } elseif ($homeIsFeatured || $awayIsFeatured) {
        return 0.7;
    }
    
    return 0.3;
}
```

### 5. GroupController - Integración (Mínimo cambio)

**Archivo**: `app/Http/Controllers/GroupController.php`

En `generateQuestionsForMatches()`:

```php
protected function generateQuestionsForMatches($matches, $group)
{
    // 🆕 NUEVO: Ordenar matches por equipos destacados
    $matches = collect($matches)
        ->sort(function($a, $b) {
            $priorityA = $a['homeTeam']['is_featured'] + $a['awayTeam']['is_featured'];
            $priorityB = $b['homeTeam']['is_featured'] + $b['awayTeam']['is_featured'];
            return $priorityB <=> $priorityA; // Descendente
        })
        ->values()
        ->toArray();
    
    // ... resto del código ...
}
```

---

## 📊 Distribución Esperada de Preguntas

### Escenario: 10 Partidos, 5 Preguntas a Crear

```
Prioridad   Partidos    Peso    Preguntas Asignadas
────────────────────────────────────────────────────
1.0 (Clás)   2 partidos  ×1.0    → 2 preguntas
0.7 (Derb)   4 partidos  ×0.7    → 2 preguntas  
0.3 (Otro)   4 partidos  ×0.3    → 1 pregunta
────────────────────────────────────────────────────
Total:       10 partidos         5 preguntas ✓
```

### Lógica de Asignación: WeightedDistribution

```php
$weightedMatches = $matches->map(function($match) {
    return [
        'match' => $match,
        'weight' => $match->getFeaturedPriorityScore(),
        'questions_allocated' => 0
    ];
});

$totalWeight = $weightedMatches->sum('weight');
$questionsPerWeight = $totalQuestions / $totalWeight;

foreach ($weightedMatches as $item) {
    $item['questions_allocated'] = ceil(
        $item['weight'] * $questionsPerWeight
    );
}
```

---

## 🧪 Testing

### Test 1: Validar Scope `orderByFeaturedTeams()`

```php
/** @test */
public function matches_are_ordered_by_featured_teams()
{
    // Crear matches
    $classico = FootballMatch::create([
        'home_team_id' => Team::where('is_featured', true)->first()->id,
        'away_team_id' => Team::where('is_featured', true)->skip(1)->first()->id,
    ]);
    
    $derby = FootballMatch::create([
        'home_team_id' => Team::where('is_featured', true)->first()->id,
        'away_team_id' => Team::where('is_featured', false)->first()->id,
    ]);
    
    $other = FootballMatch::create([
        'home_team_id' => Team::where('is_featured', false)->first()->id,
        'away_team_id' => Team::where('is_featured', false)->skip(1)->first()->id,
    ]);
    
    // Test
    $ordered = FootballMatch::orderByFeaturedTeams()->get();
    
    $this->assertEquals($classico->id, $ordered[0]->id);
    $this->assertEquals($derby->id, $ordered[1]->id);
    $this->assertEquals($other->id, $ordered[2]->id);
}
```

### Test 2: Validar `getFeaturedPriorityScore()`

```php
/** @test */
public function calculates_correct_priority_scores()
{
    $classico = FootballMatch::create([...featured + featured...]);
    $derby = FootballMatch::create([...featured + not featured...]);
    $other = FootballMatch::create([...not featured + not featured...]);
    
    $this->assertEquals(1.0, $classico->getFeaturedPriorityScore());
    $this->assertEquals(0.7, $derby->getFeaturedPriorityScore());
    $this->assertEquals(0.3, $other->getFeaturedPriorityScore());
}
```

### Test 3: Integración - Preguntas Priorizadas

```php
/** @test */
public function questions_are_created_for_featured_matches_first()
{
    $group = Group::create([...]);
    $matches = [
        $featured = FootballMatch::create([...is_featured + is_featured...]),
        $notFeatured = FootballMatch::create([...]),
    ];
    
    // Generar solo 1 pregunta de 2 partidos disponibles
    $questions = $this->generatePredictiveQuestions($group, 1);
    
    // Debe ser del partido destacado
    $this->assertTrue(
        $questions->first()->match_id === $featured->id
    );
}
```

### Test 4: Validar `is_featured` en Preguntas [NEW]

```php
/** @test */
public function question_is_marked_featured_when_created_from_featured_match()
{
    // Setup
    $group = Group::create([...]);
    
    // Crear match con equipos destacados (ambos)
    $featuredMatch = FootballMatch::create([
        'home_team_id' => Team::where('is_featured', true)->first()->id,
        'away_team_id' => Team::where('is_featured', true)->skip(1)->first()->id,
        'date' => now()->addDay(),
        'status' => 'Not Started'
    ]);
    
    // Crear match sin equipos destacados
    $regularMatch = FootballMatch::create([
        'home_team_id' => Team::where('is_featured', false)->first()->id,
        'away_team_id' => Team::where('is_featured', false)->skip(1)->first()->id,
        'date' => now()->addDay(),
        'status' => 'Not Started'
    ]);
    
    // Generar preguntas
    $template = TemplateQuestion::create([...]);
    
    $featuredQuestion = Question::create([
        'title' => 'Test question',
        'match_id' => $featuredMatch->id,
        'group_id' => $group->id,
        'type' => 'predictive',
        // 🆕 Este es el campo que validamos
        'is_featured' => $featuredMatch->getQuestionFeaturedValue(),
    ]);
    
    $regularQuestion = Question::create([
        'title' => 'Test question 2',
        'match_id' => $regularMatch->id,
        'group_id' => $group->id,
        'type' => 'predictive',
        'is_featured' => $regularMatch->getQuestionFeaturedValue(),
    ]);
    
    // Assertions
    $this->assertTrue($featuredQuestion->is_featured);      // ✓ Debe ser TRUE
    $this->assertFalse($regularQuestion->is_featured);      // ✓ Debe ser FALSE
}
```

---

## 🚀 Plan de Implementación

### Fase 0: Automatización de `is_featured` en Preguntas

- [ ] **Paso 0.1**: Añadir método `getQuestionFeaturedValue()` a `FootballMatch`
- [ ] **Paso 0.2**: Integrar en `createQuestionFromTemplate()` (Trait)
- [ ] **Paso 0.3**: Integrar en `GroupController.php` en método que crea preguntas
- [ ] **Paso 0.4**: Integrar en `CreateQuestionsForFinishedMatches.php` command

**Tiempo Estimado**: 15 min | **Archivos**: `app/Models/FootballMatch.php`, `app/Traits/HandlesQuestions.php`, `app/Http/Controllers/GroupController.php`, `app/Console/Commands/CreateQuestionsForFinishedMatches.php`

### Fase 1: Backend - Modelos y Scopes

- [ ] **Paso 1.1**: Añadir método `getFeaturedPriorityScore()` a `FootballMatch`
- [ ] **Paso 1.2**: Añadir scope `orderByFeaturedTeams()` a `FootballMatch`
- [ ] **Paso 1.3**: Verificar relaciones `hasOne` con `Team` (home_team, away_team)

**Tiempo Estimado**: 10 min | **Archivos**: `app/Models/FootballMatch.php`

### Fase 2: Trait - Integración en Generación

- [ ] **Paso 2.1**: Modificar `fillGroupPredictiveQuestions()` para usar `orderByFeaturedTeams()`
- [ ] **Paso 2.2**: Reordenar matches antes de crear preguntas
- [ ] **Paso 2.3**: Validar que mantiene lógica existente de templates

**Tiempo Estimado**: 15 min | **Archivos**: `app/Traits/HandlesQuestions.php`

### Fase 3: Controller - Integración en API

- [ ] **Paso 3.1**: Actualizar `generateQuestionsForMatches()` en `GroupController`
- [ ] **Paso 3.2**: Implementar ordenamiento en respuesta de API (si aplica)

**Tiempo Estimado**: 10 min | **Archivos**: `app/Http/Controllers/GroupController.php`

### Fase 4: Testing

- [ ] **Paso 4.1**: Crear tests de modelo para `getFeaturedPriorityScore()`
- [ ] **Paso 4.2**: Crear tests de scope para `orderByFeaturedTeams()`
- [ ] **Paso 4.3**: Crear tests de integración en `HandlesQuestionsTest`
- [ ] **Paso 4.4**: Ejecutar Artisan command de testing

**Tiempo Estimado**: 20 min | **Archivos**: `tests/Unit/Models/FootballMatchTest.php`, `tests/Unit/Traits/HandlesQuestionsTest.php`

### Fase 5: Validación y Deploy

- [ ] **Paso 5.1**: Ejecutar suite completa de tests
- [ ] **Paso 5.2**: Verificar en local que preguntas se generan en orden correcto
- [ ] **Paso 5.3**: Commit + Git push + Deploy a producción

**Tiempo Estimado**: 30 min | **Trigger**: GitHub Actions (24 tests)

**Tiempo Total**: ~100 minutos (Fase 0: 15min + Fase 1: 10min + Fase 2: 15min + Fase 3: 10min + Fase 4: 20min + Fase 5: 30min)

---

## 🔍 Validación Post-Deploy

```bash
# 1. Verificar datos en DB
SELECT 
    fm.id, 
    fm.home_team, 
    fm.away_team,
    ht.is_featured as home_featured,
    at.is_featured as away_featured,
    CASE 
        WHEN ht.is_featured = 1 AND at.is_featured = 1 THEN 'CLÁSICO'
        WHEN ht.is_featured = 1 OR at.is_featured = 1 THEN 'DERBY'
        ELSE 'OTRO'
    END as priority
FROM football_matches fm
LEFT JOIN teams ht ON fm.home_team_id = ht.id
LEFT JOIN teams at ON fm.away_team_id = at.id
ORDER BY 
    (ht.is_featured + at.is_featured) DESC,
    fm.date DESC;

# 2. Verificar preguntas creadas
SELECT 
    q.id,
    q.title,
    fm.home_team,
    fm.away_team,
    ht.is_featured as home_featured,
    at.is_featured as away_featured
FROM questions q
JOIN football_matches fm ON q.match_id = fm.id
LEFT JOIN teams ht ON fm.home_team_id = ht.id
LEFT JOIN teams at ON fm.away_team_id = at.id
ORDER BY q.created_at DESC
LIMIT 10;

# 3. Validar que is_featured se estableció correctamente [NEW]
SELECT 
    q.id,
    q.title,
    q.is_featured as question_featured,
    CASE 
        WHEN ht.is_featured = 1 OR at.is_featured = 1 THEN 1
        ELSE 0
    END as should_be_featured,
    CASE 
        WHEN (ht.is_featured = 1 OR at.is_featured = 1) = q.is_featured THEN '✓ CORRECTO'
        ELSE '✗ ERROR'
    END as validation
FROM questions q
JOIN football_matches fm ON q.match_id = fm.id
LEFT JOIN teams ht ON fm.home_team_id = ht.id
LEFT JOIN teams at ON fm.away_team_id = at.id
WHERE q.created_at >= NOW() - INTERVAL 1 DAY
ORDER BY q.created_at DESC;

# 4. Estadísticas: Preguntas por tipo de partido
SELECT 
    CASE 
        WHEN ht.is_featured = 1 AND at.is_featured = 1 THEN 'CLÁSICO'
        WHEN ht.is_featured = 1 OR at.is_featured = 1 THEN 'DERBY'
        ELSE 'OTRO'
    END as match_type,
    COUNT(q.id) as total_questions,
    SUM(CASE WHEN q.is_featured = 1 THEN 1 ELSE 0 END) as marked_as_featured,
    ROUND(100 * SUM(CASE WHEN q.is_featured = 1 THEN 1 ELSE 0 END) / COUNT(q.id), 1) as pct_featured
FROM questions q
JOIN football_matches fm ON q.match_id = fm.id
LEFT JOIN teams ht ON fm.home_team_id = ht.id
LEFT JOIN teams at ON fm.away_team_id = at.id
GROUP BY match_type
ORDER BY total_questions DESC;
```

---

## ⚠️ Consideraciones Especiales

### Edge Cases

| Caso | Manejo |
|------|--------|
| Partido sin teams asociados | Usar `is_featured = 0` por defecto |
| Teams null en relación | Usar null-coalescent (`??`) |
| Apiexterno sin is_featured | Mapear por nombre y lookup en DB |
| Cambio de is_featured post-migración | Caché se invalida (5 min TTL) |
| Pregunta creada antes de asignar teams al match | `is_featured` será FALSE (por defecto) - ⚠️ Considerar asignar teams primero |
| Teams.is_featured cambia post-creación de pregunta | Pregunta mantiene `is_featured` antiguo - Se recomienda regenerar preguntas si hay cambios |

### Performance

- **Query**: 2 LEFT JOINs en `orderByFeaturedTeams()` - **Indexado con `teams.is_featured`** ✓
- **Cálculo Priority**: O(n) agregación en colección - **Negligible para <50 partidos** ✓
- **Caché**: 5 min TTL en `getMatchQuestions()` - **Reutiliza ordenamiento** ✓
- **is_featured Lookup**: Simple boolean check (O(1)) - **No afecta performance** ✓

### Backwards Compatibility

✅ **No hay breaking changes**:
- Métodos nuevos (no sobrescriben)
- Scopes compatibles con queries existentes
- Column `is_featured` ya existe en `questions` ✓
- Tests adicionales solo (no modifican existentes)
- Preguntas existentes: `is_featured` seguirán siendo NULL/0 (no se actualiza retroactivamente)

---

## 📚 Archivos Relacionados

```
docs/
├── features/POINTS_SYSTEM_ARCHITECTURE.md
├── features/PHASE_4_RANKING_OPTIMIZATION.md
└── THIS FILE
        └─ QUESTION_PRIORITIZATION_BY_FEATURED_TEAMS.md

app/
├── Models/
│   ├── FootballMatch.php          ← Modificar
│   └── Team.php                    ← Revisar relaciones
├── Traits/
│   └── HandlesQuestions.php        ← Modificar
├── Http/Controllers/
│   └── GroupController.php         ← Modificar leve
└── Services/
    └── FootballDataService.php     ← Método auxiliar

tests/
├── Unit/
│   ├── Models/FootballMatchTest.php  ← Crear
│   └── Traits/HandlesQuestionsTest.php ← Expandir
└── Feature/
    └── CriticalViewsTest.php        ← Verificar
```

---

## 🔗 Referencias

- [Eloquent Scopes Documentation](https://laravel.com/docs/11.x/eloquent#query-scopes)
- [Laravel Query Builder Ordering](https://laravel.com/docs/11.x/queries#ordering-grouping-limit-and-offset)
- [Database Query Optimization](https://laravel.com/docs/11.x/queries#select-statements)

---

**Status**: 📋 Planificación Completa | **Owner**: `@dev` | **Sprint**: `Próximo` | **Priority**: `Media`
