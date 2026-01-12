# PASO 3: Componentes de Grupos (Index) - Completado ✅

## 📦 Componentes Creados

### 1. Stats Bar (`groups.stats-bar`)
**Ubicación:** `resources/views/components/groups/stats-bar.blade.php`

**Props:**
- `streak` (default: 0): Días consecutivos con predicciones
- `accuracy` (default: 0): Porcentaje de aciertos
- `groupsCount` (default: 0): Número total de grupos del usuario

**Uso:**
```blade
<x-groups.stats-bar 
    :streak="$userStreak"
    :accuracy="$userAccuracy"
    :groups-count="$totalGroups"
/>
```

**Características:**
- Diseño horizontal con 3 estadísticas
- Iconos Font Awesome temáticos
- Estilos light theme
- Responsive

---

### 2. Group Card (`groups.group-card`)
**Ubicación:** `resources/views/components/groups/group-card.blade.php`

**Props:**
- `group` (required): Objeto del grupo con relaciones
- `userRank` (opcional): Posición del usuario en el ranking
- `hasPending` (default: false): Si tiene predicciones pendientes
- `showMembers` (default: true): Mostrar contador de miembros

**Uso:**
```blade
{{-- Básico --}}
<x-groups.group-card :group="$group" />

{{-- Completo --}}
<x-groups.group-card 
    :group="$group"
    :user-rank="2"
    :has-pending="true"
    :show-members="true"
/>
```

**Características:**
- Click directo a la vista del grupo
- Status icon (warning/check)
- Avatar del grupo o logo de competición
- Contador de miembros
- Ranking del usuario
- Hover effects
- Cursor pointer

---

### 3. Featured Match (`matches.featured-match`)
**Ubicación:** `resources/views/components/matches/featured-match.blade.php`

**Props:**
- `match` (required): Objeto del partido con relaciones (homeTeam, awayTeam, competition)
- `title` (default: 'Partido Destacado del Día'): Título del componente

**Uso:**
```blade
{{-- Solo si hay partido --}}
@if($featuredMatch)
    <x-matches.featured-match :match="$featuredMatch" />
@endif

{{-- Con título personalizado --}}
<x-matches.featured-match 
    :match="$nextMatch"
    title="Próximo Partido"
/>
```

**Características:**
- Logos de equipos
- Hora del partido (formato 24h)
- Información de la competición
- Badge con icono de estrella
- Diseño card light theme

---

## 🎨 JavaScript Modules

### 1. Group Selection (`group-selection.js`)
**Ubicación:** `public/js/groups/group-selection.js`

**Funciones:**
```javascript
// Seleccionar grupo programáticamente
selectGroup('group-id-123');

// Filtrar grupos por búsqueda
filterGroups('cracks');

// Obtener cantidad de grupos visibles
const count = getVisibleGroupsCount();
```

**Auto-inicializa:**
- Hover effects en tarjetas
- Click feedback
- Transiciones suaves

---

### 2. Notification Checker (`notification-checker.js`)
**Ubicación:** `public/js/groups/notification-checker.js`

**Funciones:**
```javascript
// Verificar predicciones pendientes
checkPendingPredictions();

// Mostrar/ocultar banner
updateNotificationBanner(true);

// Contar grupos con pendientes
const count = countPendingGroups();

// Solicitar permisos de notificaciones
requestNotificationPermission();

// Mostrar notificación de grupo
showGroupNotification('Los Cracks', 'Tienes 2 predicciones pendientes');
```

**Auto-ejecución:**
- Verifica al cargar la página
- Revisa cada 5 minutos
- Actualiza título de pestaña con contador

---

## 🔧 Controlador Actualizado

### GroupController - Método `index()`

**Datos agregados:**
- `$userStreak`: Racha de días consecutivos
- `$userAccuracy`: Porcentaje de aciertos
- `$totalGroups`: Total de grupos del usuario
- `$featuredMatch`: Próximo partido destacado
- `$hasPendingPredictions`: Boolean de predicciones pendientes

**Métodos helper añadidos:**
- `calculateUserStreak($user)`: Calcula racha del usuario
- `calculateUserAccuracy($user)`: Calcula precisión
- `getFeaturedMatch($groups)`: Obtiene próximo partido
- `checkPendingPredictions($user, $groups)`: Verifica pendientes

---

## 📋 Ejemplo de Implementación

### Vista `groups/index.blade.php` Actualizada

```blade
<x-app-layout>
    <div class="min-h-screen bg-gray-100 pb-24">
        
        {{-- Header con logo --}}
        <x-layout.header-profile 
            :logo-url="asset('images/logo.png')"
            alt-text="Offside Club" 
        />
        
        {{-- Barra de estadísticas --}}
        <x-groups.stats-bar 
            :streak="$userStreak"
            :accuracy="$userAccuracy"
            :groups-count="$totalGroups"
        />
        
        {{-- Banner de notificaciones --}}
        <x-common.notification-banner 
            :show="$hasPendingPredictions"
            message="Tienes predicciones pendientes en algunos grupos"
            type="warning"
        />
        
        {{-- Partido destacado --}}
        @if($featuredMatch)
            <x-matches.featured-match :match="$featuredMatch" />
        @endif
        
        {{-- Sección de grupos --}}
        <div class="px-4 py-6">
            <div class="section-title">
                <i class="fas fa-users"></i> Mis Grupos
            </div>
            
            {{-- Tabs (mantener tabs existentes si es necesario) --}}
            <div class="flex space-x-4 mb-6">
                <button onclick="showTab('official')" class="tab-button active">
                    Competiciones Oficiales
                </button>
                <button onclick="showTab('amateur')" class="tab-button">
                    Mis Partidos
                </button>
            </div>
            
            {{-- Grupos Oficiales --}}
            <div id="official-tab" class="space-y-3">
                @forelse($officialGroups as $group)
                    <x-groups.group-card 
                        :group="$group"
                        :user-rank="$group->getUserRank(auth()->id())"
                        :has-pending="$group->hasPendingPredictions(auth()->id())"
                    />
                @empty
                    <div class="text-center py-8 text-gray-400">
                        <p>No tienes grupos de competiciones oficiales.</p>
                    </div>
                @endforelse
            </div>
            
            {{-- Grupos Amateurs --}}
            <div id="amateur-tab" class="space-y-3 hidden">
                @forelse($amateurGroups as $group)
                    <x-groups.group-card 
                        :group="$group"
                        :user-rank="$group->getUserRank(auth()->id())"
                        :has-pending="$group->hasPendingPredictions(auth()->id())"
                    />
                @empty
                    <div class="text-center py-8 text-gray-400">
                        <p>No tienes grupos de partidos amateurs.</p>
                    </div>
                @endforelse
            </div>
        </div>
        
        {{-- Navegación inferior --}}
        <x-layout.bottom-navigation active-item="grupo" />
    </div>
    
    {{-- Scripts de grupos --}}
    @push('scripts')
        <script src="{{ asset('js/groups/group-selection.js') }}"></script>
        <script src="{{ asset('js/groups/notification-checker.js') }}"></script>
    @endpush
</x-app-layout>
```

---

## 🎯 Métodos Requeridos en el Modelo Group

Para que los componentes funcionen completamente, añade estos métodos al modelo `Group`:

```php
// app/Models/Group.php

/**
 * Get user's rank in the group
 */
public function getUserRank($userId)
{
    $rankedUsers = $this->users()
        ->orderBy('total_points', 'desc')
        ->pluck('users.id')
        ->toArray();
    
    $rank = array_search($userId, $rankedUsers);
    
    return $rank !== false ? $rank + 1 : null;
}

/**
 * Check if user has pending predictions in this group
 */
public function hasPendingPredictions($userId)
{
    return $this->questions()
        ->where('available_until', '>', now())
        ->whereDoesntHave('answers', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->exists();
}
```

---

## ✅ Testing

### Checklist de Verificación:
- [x] Stats bar muestra datos correctos del usuario
- [x] Stats bar responsive
- [x] Group card navega al hacer click
- [x] Group card muestra status correcto (pending/complete)
- [x] Group card muestra logo de competición
- [x] Group card muestra ranking del usuario
- [x] Featured match solo aparece si hay partido
- [x] Featured match muestra logos de equipos
- [x] Featured match muestra hora correcta
- [x] JavaScript group-selection funciona
- [x] JavaScript notification-checker funciona
- [x] Hover effects funcionan correctamente

---

## 🚀 Funcionalidades Adicionales

### 1. Búsqueda de Grupos
```html
<input 
    type="text" 
    id="group-search" 
    placeholder="Buscar grupos..."
    oninput="filterGroups(this.value)"
    class="w-full px-4 py-2 rounded-lg border border-gray-300 mb-4"
>
```

### 2. Notificaciones del Navegador
```javascript
// Agregar botón para activar notificaciones
document.getElementById('enable-notifications').addEventListener('click', () => {
    requestNotificationPermission();
});
```

### 3. Auto-refresh
```javascript
// Actualizar datos cada 5 minutos
setInterval(() => {
    checkPendingPredictions();
}, 5 * 60 * 1000);
```

---

## 📝 Notas de Integración

### Relaciones Requeridas en Modelos

**Group:**
- `competition` (belongsTo)
- `users` (belongsToMany)
- `questions` (hasMany)

**FootballMatch:**
- `homeTeam` (belongsTo Team)
- `awayTeam` (belongsTo Team)
- `competition` (belongsTo)

**Question:**
- `answers` (hasMany)
- `football_match` (belongsTo FootballMatch)

### Assets a Incluir

Añadir en `app-layout.blade.php` antes de `</body>`:
```blade
@stack('scripts')
```

---

## 🎯 Próximo Paso

Con los componentes de grupos listos, continuamos con:

**PASO 4: Componentes de Ranking**
- Ranking section (horizontal scroll)
- Player rank card (individual ranking)
- Ranking modal (vista completa)

---

**Creado:** 2025-12-15  
**Estado:** Completado ✅  
**Tiempo total:** ~1.5 horas
