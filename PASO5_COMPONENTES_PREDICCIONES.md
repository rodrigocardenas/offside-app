# PASO 5: Componentes de Predicciones - Completado ✅

## 📦 Componentes Creados

### 1. Prediction Card (`predictions.prediction-card`)
**Ubicación:** `resources/views/components/predictions/prediction-card.blade.php`

**Props:**
- `question` (required): Objeto de la pregunta
- `match` (opcional): Objeto del partido asociado
- `timeLeft` (opcional): Tiempo restante para responder
- `userAnswer` (opcional): Respuesta del usuario si ya respondió
- `showResults` (bool, default: false): Mostrar resultados y respuestas correctas

**Uso:**
```blade
{{-- Predicción activa --}}
<x-predictions.prediction-card 
    :question="$question"
    :match="$question->match"
    :time-left="$question->timeUntilClose()"
    :user-answer="$userAnswers[$question->id] ?? null"
/>

{{-- Mostrar resultados --}}
<x-predictions.prediction-card 
    :question="$question"
    :match="$question->match"
    :user-answer="$userAnswer"
    :show-results="true"
/>
```

**Características:**
- Badge "Predicción del Día"
- Información del partido con logos
- Pregunta destacada
- Temporizador countdown
- Indicador de respuesta guardada
- Resultados con feedback visual
- Integración con prediction-options

---

### 2. Prediction Options (`predictions.prediction-options`)
**Ubicación:** `resources/views/components/predictions/prediction-options.blade.php`

**Props:**
- `question` (required): Objeto de la pregunta con opciones
- `userAnswer` (opcional): Respuesta del usuario
- `showResults` (bool, default: false): Mostrar resultados

**Uso:**
```blade
<x-predictions.prediction-options 
    :question="$question"
    :user-answer="$userAnswer"
    :show-results="false"
/>
```

**Características:**
- Grid 2 columnas de opciones
- Estado selected visual
- Botones deshabilitados después de responder
- Indicador de respuesta correcta
- Porcentajes de votos (cuando showResults=true)
- Check icon en opción seleccionada
- Loading state durante envío
- Container de feedback

---

## 🎨 JavaScript Modules

### 1. Prediction Handler (`prediction-handler.js`)
**Ubicación:** `public/js/predictions/prediction-handler.js`

**Funciones Principales:**
```javascript
// Seleccionar opción
selectPredictionOption(button, questionId, optionId);

// Enviar predicción al servidor
submitPrediction(questionId, optionId);

// Marcar opción como seleccionada
markOptionAsSelected(questionId, optionId);

// Mostrar feedback
showPredictionFeedback(questionId, 'success', 'Mensaje');

// Actualizar puntos del usuario
updateUserPoints(points);

// Verificar si completó todas
checkAllPredictionsCompleted();
```

**Características:**
- Submit via AJAX con fetch API
- Loading state con spinner
- Manejo de errores
- Feedback visual (success/error)
- Deshabilita opciones después de seleccionar
- Actualiza puntos automáticamente
- CSRF token handling
- Animaciones de confirmación

---

### 2. Countdown Timer (`countdown-timer.js`)
**Ubicación:** `public/js/predictions/countdown-timer.js`

**Funciones Principales:**
```javascript
// Inicializar todos los timers
initializeCountdownTimers();

// Actualizar timer específico
updateTimer(timerElement, endTime);

// Calcular tiempo restante
calculateTimeLeft(endTime);

// Formatear tiempo
formatTimeLeft(timeLeft);

// Crear timer programáticamente
createCountdownTimer(questionId, endTime);

// Configurar alertas
setupTimeWarning(minutes);
```

**Características:**
- Auto-inicialización al cargar página
- Actualización cada segundo
- Formato inteligente (días/horas/minutos/segundos)
- Alerta cuando queda poco tiempo
- Deshabilita opciones cuando expira
- Cambio de color según urgencia
- Notificaciones a 5min y 1min

**Formatos de Tiempo:**
- Más de 1 día: "2d 5h"
- Más de 1 hora: "3h 45m"
- Menos de 1 hora: "25m 30s"
- Menos de 1 minuto: "45s"

---

## 🔧 Backend Requirements

### Ruta Existente
```php
// routes/web.php (ya existe)
Route::post('questions/{question}/answer', [QuestionController::class, 'answer'])
    ->name('questions.answer');
```

### Respuesta Esperada del Servidor

```json
{
    "success": true,
    "message": "¡Predicción guardada exitosamente!",
    "points": 1250,
    "points_earned": 100,
    "is_correct": null
}
```

**En caso de error:**
```json
{
    "success": false,
    "message": "Ya has respondido esta pregunta",
    "errors": {
        "question_option_id": ["La opción seleccionada no es válida"]
    }
}
```

---

## 📋 Ejemplo de Implementación Completa

### Vista `groups/show.blade.php`

```blade
<x-app-layout>
    <div class="min-h-screen bg-gray-100 pb-24">
        
        {{-- Header del grupo --}}
        <div class="bg-white px-4 py-5 border-b border-gray-300 relative">
            <button onclick="history.back()" class="back-button">←</button>
            <div class="text-center">
                <h1 class="text-xl font-bold text-gray-800">{{ $group->name }}</h1>
            </div>
        </div>
        
        {{-- Ranking --}}
        <x-groups.ranking-section 
            :players="$group->rankedUsers()"
            :current-user-id="auth()->id()"
        />
        
        {{-- Predicciones --}}
        <div class="space-y-4">
            @foreach($matchQuestions as $question)
                <x-predictions.prediction-card 
                    :question="$question"
                    :match="$question->football_match"
                    :time-left="$question->available_until"
                    :user-answer="$userAnswers[$question->id] ?? null"
                />
            @endforeach
            
            {{-- Pregunta Social (si existe) --}}
            @if($socialQuestion)
                <x-predictions.prediction-card 
                    :question="$socialQuestion"
                    :user-answer="$userAnswers[$socialQuestion->id] ?? null"
                />
            @endif
        </div>
        
        {{-- Chat --}}
        {{-- ... --}}
        
        {{-- Modal de ranking --}}
        <x-groups.ranking-modal 
            :group-id="$group->id"
            :group-name="$group->name"
        />
        
        {{-- Navegación --}}
        <x-layout.bottom-navigation active-item="grupo" />
    </div>
    
    @push('scripts')
        <script src="{{ asset('js/predictions/prediction-handler.js') }}"></script>
        <script src="{{ asset('js/predictions/countdown-timer.js') }}"></script>
        <script src="{{ asset('js/rankings/ranking-modal.js') }}"></script>
        <script src="{{ asset('js/common/hover-effects.js') }}"></script>
    @endpush
</x-app-layout>
```

---

## ✅ Testing

### Checklist de Verificación:
- [x] Prediction card muestra partido correctamente
- [x] Opciones se muestran en grid 2 columnas
- [x] Click en opción envía AJAX
- [x] Loading state se muestra durante envío
- [x] Opción se marca como selected después de guardar
- [x] Feedback de éxito se muestra
- [x] Todas las opciones se deshabilitan después de responder
- [x] Countdown timer actualiza cada segundo
- [x] Timer muestra formato correcto
- [x] Timer cambia color cuando queda poco tiempo
- [x] Opciones se deshabilitan cuando expira
- [x] Alertas se muestran a 5min y 1min
- [x] Resultados se muestran correctamente
- [x] Porcentajes de votos funcionan
- [x] Feedback visual de correcto/incorrecto
- [x] Responsive en mobile

---

## 🎯 Estados de la Predicción

### 1. Estado Inicial (No respondida)
```blade
- Opciones habilitadas
- Sin selección
- Timer activo
- Color normal
```

### 2. Estado Respondida
```blade
- Opción selected resaltada
- Todas las opciones deshabilitadas
- Check icon en selección
- Mensaje "Tu respuesta: X"
- Timer sigue activo
```

### 3. Estado Expirada (No respondida)
```blade
- Todas las opciones deshabilitadas
- Timer muestra "Predicción cerrada"
- Color rojo en timer
```

### 4. Estado con Resultados
```blade
- Respuesta correcta con estrella
- Porcentajes visibles
- Feedback visual (verde/rojo)
- Puntos ganados mostrados
```

---

## 🎨 Estilos Visuales

### Badges y Colores
```css
.prediction-badge → bg-offside-primary text-black
.selected → bg-offside-dark text-offside-light
Correcto → bg-green-100 border-green-300
Incorrecto → bg-red-100 border-red-300
```

### Animaciones
- **Loading**: Spinner rotativo
- **Success**: Fade in del feedback
- **Selection**: Border color change
- **Timer warning**: Color amarillo < 1h
- **Timer expired**: Color rojo

---

## 🚀 Funcionalidades Avanzadas

### 1. Notificaciones de Tiempo
```javascript
// En countdown-timer.js
setupTimeWarning(5); // Alerta a 5 minutos
setupTimeWarning(1); // Alerta a 1 minuto
```

### 2. Auto-save (Opcional)
```javascript
// Guardar automáticamente selección
function autoSavePrediction(questionId, optionId) {
    localStorage.setItem(`temp_prediction_${questionId}`, optionId);
}

// Restaurar en caso de error
function restorePrediction(questionId) {
    return localStorage.getItem(`temp_prediction_${questionId}`);
}
```

### 3. Confirmación Doble (Opcional)
```javascript
function selectPredictionOption(button, questionId, optionId) {
    if (confirm('¿Estás seguro de tu predicción? No podrás cambiarla.')) {
        // Proceder con el submit
    }
}
```

### 4. Analytics
```javascript
// Track prediction time
function trackPredictionTime(questionId) {
    const startTime = Date.now();
    
    // Al guardar:
    const timeSpent = (Date.now() - startTime) / 1000;
    console.log(`Tiempo de decisión: ${timeSpent}s`);
}
```

---

## 📝 Notas de Integración

### Requisitos del Modelo Question

```php
// app/Models/Question.php

/**
 * Get time until close
 */
public function timeUntilClose()
{
    if (!$this->available_until) {
        return null;
    }
    
    $now = now();
    $end = Carbon::parse($this->available_until);
    
    return $end->greaterThan($now) ? $end->diffInSeconds($now) : 0;
}

/**
 * Check if expired
 */
public function isExpired()
{
    return $this->available_until && 
           Carbon::parse($this->available_until)->isPast();
}
```

### Relaciones Requeridas
```php
// Question
public function options() { return $this->hasMany(QuestionOption::class); }
public function answers() { return $this->hasMany(Answer::class); }
public function football_match() { return $this->belongsTo(FootballMatch::class, 'match_id'); }

// Answer
public function questionOption() { return $this->belongsTo(QuestionOption::class); }
```

---

## 🔒 Seguridad

### Validaciones en el Backend
```php
// QuestionController::answer()
$request->validate([
    'question_option_id' => 'required|exists:question_options,id'
]);

// Verificar que no haya respondido antes
if ($question->answers()->where('user_id', $user->id)->exists()) {
    return response()->json([
        'success' => false,
        'message' => 'Ya has respondido esta pregunta'
    ], 422);
}

// Verificar que no esté expirada
if ($question->isExpired()) {
    return response()->json([
        'success' => false,
        'message' => 'Esta predicción ya cerró'
    ], 422);
}
```

---

## 🎯 Próximo Paso

Con los componentes de predicciones listos, continuamos con:

**PASO 6: Componentes de Chat**
- Chat section (sección completa de chat)
- Chat message (mensaje individual)
- Chat input (input de envío)
- Chat handler (manejo de mensajes)
- Real-time con Pusher

---

## 📊 Métricas Disponibles

Los componentes pueden rastrear:
- **Tiempo de respuesta** por usuario
- **Tasa de respuesta** por pregunta
- **Distribución de respuestas** (porcentajes)
- **Predicciones completadas** vs pendientes
- **Tiempo promedio** de decisión

---

## 💡 Tips de UX

1. **Mostrar cuántas predicciones faltan**
2. **Destacar preguntas con poco tiempo**
3. **Confirmación visual inmediata**
4. **Deshabilitar después de responder**
5. **Mostrar progreso del grupo**

---

**Creado:** 2025-12-15  
**Estado:** Completado ✅  
**Tiempo total:** ~2 horas  
**Archivos creados:** 4 (2 Blade + 2 JS)
