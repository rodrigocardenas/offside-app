# 🎯 QUIZ GAME - MOBILE WORLD CONGRESS

**Fecha de Creación:** Febrero 26, 2026  
**Evento:** Mobile World Congress (MWC)  
**Objetivo:** Crear una nueva dinámica de juego para captar nuevos usuarios

---

## 📋 DESCRIPCIÓN GENERAL

Nueva modalidad de juego tipo **QUIZ** que reutiliza la infraestructura actual de preguntas y respuestas. Se compondrá de un conjunto de 10 preguntas asociadas a un **grupo público dedicado al MWC** con:

- Sistema de puntuación por respuesta correcta
- Ranking dinámico y público posicionado por:
  1. **Primario:** Puntos totales (respuestas correctas)
  2. **Secundario:** Tiempo de respuesta total (menor tiempo = mejor posición)

---

## 🎮 CARACTERÍSTICAS PRINCIPALES

### 1. **Tipo de Pregunta**
- **Nuevo tipo:** `quiz` (en columna `type` de `questions` y `template_questions`)
- **Fórmula de opciones:** Idéntica a preguntas actuales (múltiple choice)
- **Evaluación:** Manual o semiautomática (requiere validación admin)

### 2. **Grupo del MWC**
- **Scope:** Grupo público separado y específico para Mobile World Congress
- **Visibilidad:** Público (cualquier usuario puede unirse)
- **Duración:** Configurable (evento MWC)
- **Nombre sugerido:** "Mobile World Congress Quiz 2026" o similar

### 3. **Sistema de Puntuación**
```
puntos_por_respuesta = respuesta_correcta ? 100 : 0
total_puntos_usuario = SUM(puntos_por_respuesta)
```

### 4. **Ranking Público**
- **Ubicación:** Dentro del grupo del quiz (requiere login)
- **Ordenamiento:**
  1. `total_puntos_usuario DESC` (primario)
  2. `tiempo_respuesta_total ASC` (desempate)
- **Datos mostrados:**
  - Posición (1, 2, 3, etc.)
  - Nombre de usuario
  - Puntos totales
  - Tiempo total de respuesta
  - País (si aplica)

---

## 💾 CAMBIOS EN BASE DE DATOS

### 1. **Columna `type` en `template_questions`**
Agregar soporte del nuevo tipo:
```sql
VALUES: 'predictive' | 'social' | 'quiz'
```

### 2. **Columna `type` en `questions`**
Agregar soporte del nuevo tipo (si no está ya:)
```sql
VALUES: 'predictive' | 'social' | 'quiz'
```

### 3. **Nueva columna en `answers` (IMPORTANTE)**
Agregar timestamp de respuesta para calcular tiempo:
```sql
ALTER TABLE answers ADD COLUMN answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
```
Esto permite calcular:
- `tiempo_por_respuesta = answered_at - (pregunta mostrada_at)`
- `tiempo_total = SUM(tiempo_por_respuesta)` por usuario

### 4. **Grupo Público del MWC**
Crear con seeder:
- **Nombre:** "Mobile World Congress Quiz"
- **Código:** "MWC-2026-QUIZ"
- **Category:** "quiz"
- **visibility:** "public"
- **Type:** Fixed (no es competencia)

---

## 🔧 CAMBIOS EN BACKEND

### 1. **Servicio: QuestionEvaluationService**
**Archivos a modificar:** `app/Services/QuestionEvaluationService.php`

**Agregar método:**
```php
/**
 * Evalúa preguntas de tipo 'quiz'
 * Usa opciones de template_questions
 * @return array
 */
private function evaluateQuizQuestion(Question $question): array
```

**Cambios en `evaluateQuestion()`:**
```php
// Agregar caso para tipo 'quiz'
elseif ($question->type === 'quiz') {
    // Obtener respuesta correcta del template
    $correctOptions = $this->evaluateQuizQuestion($question);
    $questionHandled = true;
}
```

### 2. **Modelo: Answer**
**Archivos a modificar:** `app/Models/Answer.php`

**Agregar atributo:**
```php
protected $fillable = [
    'user_id',
    'question_id',
    'question_option_id',
    'is_correct',
    'points_earned',
    'category',
    'answered_at',  // NUEVO
];

protected $casts = [
    'is_correct' => 'boolean',
    'answered_at' => 'datetime',  // NUEVO
];
```

### 3. **Controlador: GroupController**
**Archivos a modificar:** `app/Http/Controllers/GroupController.php`

**Agregar método para ranking del grupo:**
```php
/**
 * GET /groups/{id}/ranking-data
 * Retorna usuarios ordenados por: puntos DESC, tiempo ASC
 * Incluye: posición, nombre, puntos, tiempo_total
 */
public function getRankingData(Group $group)
```

### 4. **Controlador: RankingController** (si aplica)
**Archivos a modificar:** `app/Http/Controllers/RankingController.php`

**Agregar método para ranking de quiz:**
```php
/**
 * GET /ranking/quiz/{groupId}
 * Ranking específico de quiz con ordenamiento dual
 */
public function quizRanking(Group $group)
```

### 5. **Servicio: QuestionService** (si existe)
Agregar lógica para:
- Validar que `answered_at` se registre al crear `Answer`
- Calcular `tiempo_total_respuesta` por usuario por grupo

---

## 🎨 CAMBIOS EN FRONTEND

### 1. **Vista: Ranking del Quiz**
**Archivo a crear:** `resources/views/groups/quiz-ranking.blade.php`

**Elementos:**
- Logo/Banner del MWC
- Tabla de ranking con:
  - Posición (con medallas para top 3)
  - Avatar/Nombre usuario
  - Puntos totales
  - Tiempo de respuesta total (formato: mm:ss)
  - País (si lo tienes en perfil)
- Filtros opcionales (por país, etc.)
- Paginación (si hay >100 usuarios)

### 2. **Vista: Show Group (Quiz)**
**Archivo a modificar:** `resources/views/groups/show-unified.blade.php`

**Agregar:**
- Indicador de tipo "quiz"
- Link a ranking público
- Badge "Public Quiz" o "MWC Quiz"

### 3. **Componente: Quiz Question Card**
**Archivo a crear:** `resources/views/components/quiz-question-card.blade.php`

**Elementos:**
- Número de pregunta (1/10)
- Texto de pregunta
- Opciones (radio buttons o botones)
- Botón "Enviar Respuesta"
- Indicador de progreso

---

## 🛣️ RUTAS Y ENDPOINTS API

### 1. **Rutas Web (para vistas)**
```php
Route::group(['middleware' => 'auth'], function () {
    // Mostrar grupo del quiz
    GET  /mwc-quiz               → GroupController@show
    
    // Ver ranking (dentro del grupo)
    GET  /groups/{id}/ranking    → GroupController@ranking
    
    // Tabla de ranking (para modal/página)
    GET  /api/groups/{id}/ranking-data → GroupController@getRankingData
});
```

### 2. **Endpoints API**
```php
// Obtener preguntas del quiz
GET  /api/quiz/mwc/questions
Response: [
    {
        id, title, type: 'quiz', 
        options: [{id, text}, ...],
        template_question_id,
        available_until
    }
]

// Enviar respuesta
POST /api/questions/{id}/answer
Body: {
    question_option_id: int,
    answered_at: timestamp  // IMPORTANTE
}
Response: {
    is_correct: bool,
    points_earned: int,
    message: string
}

// Obtener ranking actualizado
GET  /api/groups/{id}/ranking
Query: ?sort=points|time&page=1
Response: {
    data: [{
        position: int,
        user: {id, name, avatar},
        total_points: int,
        total_time: int (segundos),
        answered_count: int
    }],
    pagination
}
```

---

## 📦 ESTRUCTURA DE DATOS

### Flujo de Respuesta
```
1. Usuario selecciona opción en quiz
2. Frontend captura timestamp (answered_at)
3. POST /api/questions/{id}/answer con:
   - question_option_id
   - answered_at (timestamp actual)
4. Backend:
   - Crea Answer con answered_at
   - Valida si es_correcta
   - Calcula points_earned
   - Actualiza respuesta
5. Response: {is_correct, points_earned}
```

### Cálculo de Ranking
```
Para cada usuario en grupo_quiz:
  total_puntos = SUM(answers.points_earned 
                    WHERE group_id = mwc_group 
                    AND type = 'quiz')
  
  total_tiempo = SUM(TIMESTAMPDIFF(SECOND, 
                                   question.created_at, 
                                   answers.answered_at))

ORDER BY:
  1. total_puntos DESC
  2. total_tiempo ASC
```

---

## 🧪 CRITERIOS DE ACEPTACIÓN

### Funcionalidad
- [ ] Tipo 'quiz' funciona en template_questions y questions
- [ ] 10 preguntas quiz creadas en grupo público
- [ ] Respuestas se registran con timestamp correcto
- [ ] Puntuación se calcula correctamente (100 por acierto)
- [ ] Ranking ordena correctamente (puntos DESC, tiempo ASC)

### API
- [ ] `GET /api/groups/{id}/ranking` retorna datos correctos
- [ ] `POST /api/questions/{id}/answer` registra answered_at
- [ ] Respuestas de quiz se distinguen de predictivas en BD

### UI/UX
- [ ] Vista ranking es legible y responsive
- [ ] Top 3 usuarios destacados (medallas/badges)
- [ ] Formulario quiz es intuitivo
- [ ] Link a ranking visible en grupo

### Seguridad
- [ ] Solo usuarios autenticados pueden responder
- [ ] Timestamp no puede falsificarse desde frontend
- [ ] Validación de grupo público
- [ ] Rate limiting en POST /answer

---

## 📅 FASES DE IMPLEMENTACIÓN

### FASE 1: Preparación BD (2h)
- [ ] Crear migration para `answered_at` en answers
- [ ] Crear seeder con 10 preguntas quiz
- [ ] Crear grupo público "MWC Quiz"
- [ ] Crear template_questions para quiz

### FASE 2: Backend (3h)
- [ ] Implementar evaluateQuizQuestion() en QuestionEvaluationService
- [ ] Agregar lógica en GroupController para ranking
- [ ] Crear endpoint GET /api/groups/{id}/ranking
- [ ] Validar flujo de respuesta con timestamp

### FASE 3: Frontend (2.5h)
- [ ] Crear componente quiz-question-card
- [ ] Crear vista quiz-ranking.blade.php
- [ ] Agregar rutas y validación
- [ ] CSS responsive para tabla ranking

### FASE 4: Testing (1.5h)
- [ ] Tests unitarios para evaluación quiz
- [ ] Tests para cálculo de ranking
- [ ] Tests E2E de flujo usuario
- [ ] Testing manual en grupo

### FASE 5: Deploy & Tuning (1h)
- [ ] Deploy a staging/producción
- [ ] Validación con datos reales
- [ ] Optimizaciones si aplican

**TOTAL ESTIMADO: 10 horas**

---

## 🔄 REUTILIZACIÓN DE INFRAESTRUCTURA

### QUE REUTILIZAMOS ✅
- Sistema de grupos existente
- Modelo Question y QuestionOption
- Modelo Answer
- Servicios de evaluación (base)
- Sistema de usuarios
- Autenticación

### QUE ES NUEVO ❌
- Tipo `quiz` en schema
- Timestamp `answered_at` en answers
- Lógica de evaluación específica para quiz
- Ranking dual (puntos + tiempo)
- Endpoints específicos para ranking

---

## 📚 REFERENCIAS INTERNAS

**Archivos clave del proyecto:**
- [app/Services/QuestionEvaluationService.php](../app/Services/QuestionEvaluationService.php) - Evaluación
- [app/Models/Question.php](../app/Models/Question.php) - Modelo
- [app/Models/Answer.php](../app/Models/Answer.php) - Modelo
- [app/Http/Controllers/GroupController.php](../app/Http/Controllers/GroupController.php) - Controller
- [database/seeders/TemplateQuestionSeeder.php](../database/seeders/TemplateQuestionSeeder.php) - Seeder

**Documentación relacionada:**
- [NEW_QUESTION_TYPES_IMPLEMENTATION.md](./NEW_QUESTION_TYPES_IMPLEMENTATION.md) - Implementación de tipos
- [QUESTION_TYPES_REFERENCE.md](./QUESTION_TYPES_REFERENCE.md) - Referencia de tipos

---

## ❓ PREGUNTAS PENDIENTES (OPCIONALES)

1. ¿Hay un límite de tiempo por pregunta o por quiz total?
2. ¿Deben verse las respuestas correctas inmediatamente o al final?
3. ¿Se puede responder múltiples veces o es una sola vez?
4. ¿Hay premio o badge para el primer lugar?
5. ¿El quiz debe estar disponible solo durante MWC o permanentemente?

---

## 📝 NOTAS IMPORTANTES

- **Infraestructura:** El sistema actual soporta fácilmente este new tipo sin grandes cambios
- **Escalabilidad:** La lógica de ranking podría cachear vía Redis si hay muchos usuarios
- **Migración:** Necesita migration nueva solo para `answered_at` en answers
- **Compatibilidad:** No afecta preguntas `predictive` o `social` existentes

---

**Status:** 🟡 Planificación Completada - Lista para implementación
