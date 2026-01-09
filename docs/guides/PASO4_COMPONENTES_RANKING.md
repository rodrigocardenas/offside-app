# PASO 4: Componentes de Ranking - Completado ✅

## 📦 Componentes Creados

### 1. Ranking Section (`groups.ranking-section`)
**Ubicación:** `resources/views/components/groups/ranking-section.blade.php`

**Props:**
- `players` (Collection, default: empty): Colección de usuarios ordenados por puntos
- `currentUserId` (int, opcional): ID del usuario actual para resaltar
- `showExpandButton` (bool, default: true): Mostrar botón "Ver todos"
- `title` (string, default: 'Ranking'): Título de la sección

**Uso:**
```blade
{{-- Básico --}}
<x-groups.ranking-section 
    :players="$group->rankedUsers()"
    :current-user-id="auth()->id()"
/>

{{-- Con título personalizado --}}
<x-groups.ranking-section 
    :players="$topPlayers"
    :current-user-id="auth()->id()"
    title="Top 10 Jugadores"
    :show-expand-button="false"
/>
```

**Características:**
- Scroll horizontal
- Muestra máximo 10 jugadores
- Botón expandir para ver todos
- Responsive
- Mensaje cuando no hay jugadores

---

### 2. Player Rank Card (`groups.player-rank-card`)
**Ubicación:** `resources/views/components/groups/player-rank-card.blade.php`

**Props:**
- `player` (required): Objeto del usuario con total_points
- `rank` (required): Posición en el ranking (1, 2, 3, etc.)
- `isCurrentUser` (bool, default: false): Si es el usuario actual

**Uso:**
```blade
<x-groups.player-rank-card 
    :player="$user"
    :rank="1"
    :is-current-user="$user->id == auth()->id()"
/>
```

**Características:**
- Badges de medallas (oro, plata, bronce)
- Resalta usuario actual
- Muestra puntos
- Trunca nombres largos
- Colores diferenciados por posición

---

### 3. Ranking Modal (`groups.ranking-modal`)
**Ubicación:** `resources/views/components/groups/ranking-modal.blade.php`

**Props:**
- `groupId` (required): ID del grupo
- `groupName` (string, default: 'Grupo'): Nombre del grupo

**Uso:**
```blade
{{-- En la vista del grupo --}}
<x-groups.ranking-modal 
    :group-id="$group->id"
    :group-name="$group->name"
/>
```

**Características:**
- Modal full-screen overlay
- Carga datos vía AJAX
- Loading state
- Error handling
- Estadísticas del usuario
- Cierre con ESC o click afuera
- Scroll interno
- Gradient header

---

## 🎨 JavaScript Modules

### 1. Ranking Modal (`ranking-modal.js`)
**Ubicación:** `public/js/rankings/ranking-modal.js`

**Funciones Principales:**
```javascript
// Abrir modal de ranking
expandRanking();

// Abrir modal con ID específico
openRankingModal(groupId);

// Cerrar modal
closeRankingModal();

// Cargar datos del ranking
loadFullRanking(groupId);
```

**Características:**
- Fetch API para cargar datos
- Renderizado dinámico
- Manejo de errores
- Loading states
- Auto-scroll en lista
- Cierre con ESC
- Click outside to close

---

### 2. Hover Effects (`hover-effects.js`)
**Ubicación:** `public/js/common/hover-effects.js`

**Funciones Principales:**
```javascript
// Inicializar todos los efectos
initializeHoverEffects();

// Agregar efecto ripple
addRippleEffect(element, event);

// Animar elemento con pulse
pulseElement(element);

// Shake element (para errores)
shakeElement(element);
```

**Auto-inicializa:**
- Card hover effects
- Button hover effects
- Icon hover effects

**Animaciones CSS incluidas:**
- `pulse` - Escala 1.05
- `shake` - Movimiento lateral
- `ripple` - Efecto material design

---

## 🔧 Backend Implementation

### Ruta API Agregada
```php
// routes/web.php
Route::get('groups/{group}/ranking', [GroupController::class, 'getRanking'])
    ->name('groups.ranking');
```

### Método en GroupController
**`getRanking(Group $group)`**

**Retorna:**
```json
{
    "players": [
        {
            "id": 1,
            "name": "Juan",
            "total_points": 1250,
            "correct_answers": 15,
            "rank": 1,
            "is_current_user": false
        }
    ],
    "stats": {
        "total_players": 8,
        "user_position": 4,
        "user_points": 650
    }
}
```

**Seguridad:**
- Verifica que el usuario pertenezca al grupo
- Retorna 403 si no tiene acceso

---

### Métodos Agregados al Modelo Group

```php
// app/Models/Group.php

/**
 * Get user's rank in the group
 */
public function getUserRank($userId)

/**
 * Check if user has pending predictions in this group
 */
public function hasPendingPredictions($userId)

/**
 * Get ranked users with points
 */
public function rankedUsers()
```

---

## 📋 Ejemplo de Implementación

### Vista `groups/show.blade.php` Actualizada

```blade
<x-app-layout>
    <div class="min-h-screen bg-gray-100 pb-24">
        
        {{-- Header del grupo con botón volver --}}
        <div class="bg-white px-4 py-5 border-b border-gray-300">
            <button onclick="history.back()" class="back-button">
                ←
            </button>
            <div class="text-center">
                <h1 class="text-xl font-bold text-gray-800">{{ $group->name }}</h1>
            </div>
        </div>
        
        {{-- Ranking Horizontal --}}
        <x-groups.ranking-section 
            :players="$group->rankedUsers()"
            :current-user-id="auth()->id()"
        />
        
        {{-- Resto del contenido del grupo --}}
        <div class="px-4 py-6">
            {{-- Predicciones, chat, etc. --}}
        </div>
        
        {{-- Modal de ranking completo --}}
        <x-groups.ranking-modal 
            :group-id="$group->id"
            :group-name="$group->name"
        />
        
        {{-- Navegación inferior --}}
        <x-layout.bottom-navigation active-item="grupo" />
    </div>
    
    {{-- Scripts necesarios --}}
    @push('scripts')
        <script src="{{ asset('js/rankings/ranking-modal.js') }}"></script>
        <script src="{{ asset('js/common/hover-effects.js') }}"></script>
    @endpush
</x-app-layout>
```

---

## ✅ Testing

### Checklist de Verificación:
- [x] Ranking section muestra jugadores ordenados
- [x] Scroll horizontal funciona
- [x] Badges de medallas correctos (oro, plata, bronce)
- [x] Usuario actual resaltado
- [x] Botón "Ver todos" abre modal
- [x] Modal carga datos correctamente
- [x] Modal muestra loading state
- [x] Modal maneja errores
- [x] Modal cierra con ESC
- [x] Modal cierra con click fuera
- [x] Estadísticas se muestran correctamente
- [x] Hover effects funcionan
- [x] Responsive en mobile

---

## 🎯 Badges de Ranking

### Colores por Posición
```php
1º lugar: bg-yellow-400 (Oro) 🥇
2º lugar: bg-gray-400 (Plata) 🥈
3º lugar: bg-orange-400 (Bronce) 🥉
4º+: bg-gray-600 (Gris)
```

### Aplicación Automática
El componente `player-rank-card` usa un `match` statement para aplicar automáticamente la clase CSS según el rank:

```php
@php
    $rankClass = match($rank) {
        1 => 'rank-gold',
        2 => 'rank-silver',
        3 => 'rank-bronze',
        default => 'rank-other'
    };
@endphp
```

---

## 🚀 Funcionalidades Avanzadas

### 1. Auto-refresh del Ranking
```javascript
// Actualizar ranking cada 30 segundos
setInterval(() => {
    if (document.getElementById('ranking-modal').classList.contains('hidden') === false) {
        loadFullRanking(currentGroupId);
    }
}, 30000);
```

### 2. Animaciones al Subir/Bajar de Posición
```javascript
// Detectar cambio de posición
function detectRankChange(oldRank, newRank) {
    const card = document.querySelector(`[data-user-id="${userId}"]`);
    
    if (newRank < oldRank) {
        // Subió de posición
        pulseElement(card);
        card.classList.add('rank-up-animation');
    } else if (newRank > oldRank) {
        // Bajó de posición
        shakeElement(card);
    }
}
```

### 3. Compartir Posición
```javascript
function shareRanking(rank, points) {
    const text = `¡Estoy en el puesto #${rank} con ${points} puntos! 🏆`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Mi Ranking',
            text: text,
            url: window.location.href
        });
    }
}
```

---

## 📝 Notas de Integración

### Requisitos de Base de Datos

La columna `total_points` debe existir en la tabla `users`:

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->integer('total_points')->default(0);
});
```

### Actualización de Puntos

Los puntos se actualizan cuando se verifican las respuestas:

```php
// En QuestionController o similar
$user->total_points += $points_earned;
$user->save();
```

### Cache (Opcional)

Para mejorar performance en grupos grandes:

```php
public function rankedUsers()
{
    return Cache::remember("group_{$this->id}_ranked_users", 300, function() {
        return $this->users()
            ->orderBy('total_points', 'desc')
            ->get();
    });
}
```

---

## 🎨 Estilos CSS Aplicados

Las clases definidas en `components.css`:
- `.ranking-list` - Container con scroll horizontal
- `.player-item` - Card de jugador
- `.rank-gold`, `.rank-silver`, `.rank-bronze`, `.rank-other` - Badges
- Animaciones de hover
- Scrollbar personalizada

---

## 🎯 Próximo Paso

Con los componentes de ranking listos, continuamos con:

**PASO 5: Componentes de Predicciones**
- Prediction card (tarjeta de predicción)
- Prediction options (opciones interactivas)
- Countdown timer (temporizador)
- Prediction handler (manejo de respuestas)

---

## 📊 Métricas y Estadísticas

El componente de ranking puede mostrar:
- **Total de jugadores** en el grupo
- **Posición del usuario** actual
- **Puntos del usuario** actual
- **Aciertos totales** por jugador
- **Racha de victorias** (futuro)

---

**Creado:** 2025-12-15  
**Estado:** Completado ✅  
**Tiempo total:** ~2 horas
