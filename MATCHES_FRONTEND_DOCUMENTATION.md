# Frontend - Matches Calendar View

## 📋 Descripción General

La vista de calendario de partidos es la interfaz principal para que los usuarios vean el listado de partidos próximos agrupados por fecha, con opción de filtrar por competencia y ver estadísticas de los partidos.

**URL:** `/matches/calendar`  
**Método:** GET  
**Autenticación:** Requerida (Sanctum)  
**Plantilla Base:** Blade con tema light/dark

---

## 🏗️ Estructura de Componentes

### 1. **Vista Principal: `matches/calendar.blade.php`**

```blade
@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
@endphp

<x-dynamic-layout :layout="$layout">
    <x-layout.header-profile ... />
    <x-matches.calendar-filters ... />
    <div class="matches-calendar-section">
        @forelse($matchesByDate as $date => $matches)
            <x-matches.calendar-day :date="$date" :matches="$matches" />
        @empty
            <!-- Sin partidos -->
        @endforelse
    </div>
    <x-matches.calendar-stats :stats="$statistics" />
    <x-layout.bottom-navigation active-item="partidos" />
</x-dynamic-layout>
```

**Datos Esperados:**
- `$matchesByDate`: Array[date => Array[matches]] - Partidos agrupados por fecha
- `$competitions`: Array - Competiciones disponibles para filtrar
- `$statistics`: Object - Estadísticas (total, scheduled, live, finished)
- `$selectedCompetitionId`: int|null - Competición actualmente seleccionada

---

### 2. **Componente: `calendar-day.blade.php`**

Muestra los partidos de un día específico con:
- Badge "HOY" o "MAÑANA" para fechas especiales
- Nombre del día de la semana
- Lista de partidos del día

```blade
<div class="calendar-day-group">
    <div class="day-header">
        <!-- Fecha y día -->
    </div>
    <div class="matches-container">
        @foreach($matches as $match)
            <x-matches.match-card :match="$match" />
        @endforeach
    </div>
</div>
```

---

### 3. **Componente: `match-card.blade.php`**

Tarjeta individual de partido con:
- Competencia (badge superior)
- Hora de inicio
- Escudos de equipos
- Nombres de equipos
- Marcador o estado
- Botones de acción (Predecir/Detalles)

```blade
<div class="match-card">
    <!-- Header con competencia y hora -->
    <div class="match-header">
        <span class="competition-badge">{{ $competition['name'] }}</span>
        <span class="match-time">{{ $kickOffTime }}</span>
    </div>

    <!-- Equipos y marcador -->
    <div class="teams-container">
        <div class="team home-team">
            <img class="team-crest" src="{{ $homeTeam['crest_url'] }}" />
            <span class="team-name">{{ $homeTeam['name'] }}</span>
        </div>
        <div class="match-score">{{ $score }}</div>
        <div class="team away-team">
            <span class="team-name">{{ $awayTeam['name'] }}</span>
            <img class="team-crest" src="{{ $awayTeam['crest_url'] }}" />
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="action-buttons">
        <button onclick="openPredictModal(...)">Predecir</button>
        <button onclick="openMatchDetails(...)">Detalles</button>
    </div>
</div>
```

---

### 4. **Componente: `calendar-filters.blade.php`**

Filtros y opciones de visualización:
- Selector horizontal de competencias
- Filtro "Todas las competencias"
- Opciones de rango de fechas (Esta semana / Este mes)

```blade
<div class="calendar-filters">
    <label>Filtrar por Liga</label>
    <div class="filter-chips">
        <button onclick="filterByCompetition(null)" class="filter-chip active">
            Todas
        </button>
        @foreach($competitions as $comp)
            <button onclick="filterByCompetition({{ $comp['id'] }})" class="filter-chip">
                {{ $comp['name'] }}
            </button>
        @endforeach
    </div>

    <label>Período</label>
    <div class="period-chips">
        <button onclick="setDateRange('week')">Esta Semana</button>
        <button onclick="setDateRange('month')">Este Mes</button>
    </div>
</div>
```

---

### 5. **Componente: `calendar-stats.blade.php`**

Muestra estadísticas del período:
- Total de partidos
- Partidos programados
- Partidos en vivo
- Partidos finalizados

```blade
<div class="calendar-stats-section">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Partidos</div>
        </div>
        <!-- Más stats... -->
    </div>
</div>
```

---

## 🎨 Temas y Estilos

### Variables de Tema (Light/Dark)

```javascript
const isDark = themeMode === 'dark';

const bgColor = isDark ? '#1a524e' : '#f9f9f9';
const borderColor = isDark ? '#2d7a77' : '#e0e0e0';
const textColor = isDark ? '#f1fff8' : '#333333';
const secondaryText = isDark ? '#a0d5d0' : '#666666';
const accentColor = '#00deb0';
```

### Colores Principales

- **Acento Principal:** `#00deb0` (verde agua)
- **Acento Oscuro:** `#17b796`
- **En Vivo:** `#ff6b6b` (rojo)
- **Próximo:** `#ffd93d` (amarillo)
- **Finalizados:** `#64c8c8` (cian)

### Estados de Partidos

```javascript
const status = match['status']; // 'SCHEDULED', 'LIVE', 'FINISHED'

if (status === 'FINISHED') {
    // Mostrar marcador: "2 - 1"
} else if (status === 'LIVE') {
    // Mostrar "EN VIVO" con badge rojo pulsante
} else {
    // Mostrar "vs"
}
```

---

## 📱 Responsive Design

### Mobile (< 768px)
- Stack vertical de componentes
- Scroll horizontal en filtros
- Tarjetas de partido con ancho completo
- Botones de acción en dos columnas

### Tablet/Desktop
- Layout similar, con márgenes aumentados
- Scroll horizontal en filtros de competencias
- Tarjetas organizadas en grid (opcional)

---

## 🔌 Integración con API

### Endpoint Principal: `GET /api/matches/calendar`

```javascript
await fetch('/api/matches/calendar?from_date=2024-02-05&to_date=2024-02-12&competition_id=1', {
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }
});
```

**Response:**
```json
{
    "success": true,
    "data": {
        "2024-02-05": [
            {
                "id": 1,
                "status": "SCHEDULED",
                "kick_off_time": "20:00",
                "competition": { "id": 1, "name": "Premier League" },
                "home_team": { "id": 1, "name": "Man United", "crest_url": "..." },
                "away_team": { "id": 2, "name": "Liverpool", "crest_url": "..." },
                "score": { "home": null, "away": null }
            }
        ]
    }
}
```

### Endpoint de Competiciones: `GET /api/matches/competitions`

Obtiene la lista de competiciones disponibles para filtrar.

### Endpoint de Estadísticas: `GET /api/matches/statistics`

Obtiene conteos: total, scheduled, live, finished.

---

## 📂 Archivo JavaScript: `public/js/matches/calendar.js`

### Funciones Principales

#### `loadInitialMatches()`
Carga los partidos iniciales (próximos 7 días).

#### `fetchMatchesFromAPI(fromDate, toDate, competitionId)`
Obtiene partidos de la API y actualiza la interfaz.

#### `filterByCompetition(competitionId)`
Filtra partidos por competencia seleccionada.

#### `setDateRange(range)`
Cambia el rango de fechas (week/month).

#### `openPredictModal(matchId)`
Abre modal para hacer predicción de resultado.

#### `openMatchDetails(matchId)`
Muestra detalles completos del partido.

---

## ✅ Checklist de Implementación

- [x] Vista principal `matches/calendar.blade.php`
- [x] Componente `calendar-day.blade.php`
- [x] Componente `match-card.blade.php`
- [x] Componente `calendar-filters.blade.php`
- [x] Componente `calendar-stats.blade.php`
- [x] Método `view()` en MatchesController
- [x] Ruta web `/matches/calendar`
- [x] JavaScript para interacción
- [ ] Modal de predicción
- [ ] Modal de detalles del partido
- [ ] WebSocket para actualizaciones en vivo
- [ ] Persistencia de preferencias de filtro

---

## 🚀 Próximos Pasos

1. **Modal de Predicción:** Crear modal para que usuarios hagan predicciones de resultado
2. **Modal de Detalles:** Mostrar información completa del partido (formaciones, estadísticas, etc.)
3. **Actualizaciones en Vivo:** Usar WebSocket para actualizar marcadores en tiempo real
4. **Persiste Preferencias:** Guardar filtros seleccionados en localStorage
5. **Mejoras de UX:** Animaciones, swipe para cambiar fechas, etc.

---

## 🔍 Testing

### Probar Carga de Datos

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost/api/matches/calendar?from_date=2024-02-05&to_date=2024-02-12"
```

### Acceder a la Vista

```
http://localhost/matches/calendar
```

---

## 📝 Notas Adicionales

- **Tema Automático:** El tema se detecta automáticamente de `auth()->user()->theme_mode`
- **Competencias Dinámicas:** Los filtros se cargan de la BD (solo competiciones con partidos)
- **Escudos de Equipos:** Si no hay imagen, se muestra placeholder con icono
- **Horarios:** Se muestran en la zona horaria del usuario (implementado en `/js/timezone-sync.js`)

