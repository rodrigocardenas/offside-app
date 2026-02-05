# Plan: Vista de Partidos tipo One Football / 365

**Fecha**: Febrero 5, 2026  
**Estado**: Planificación  
**Objetivo**: Crear una vista de partidos agrupados por día, similar a One Football o 365

---

## 📋 Requisitos Funcionales

### 1. Listado de Partidos por Día
- [ ] Agrupar partidos por fecha (día)
- [ ] Mostrar hora del partido
- [ ] Mostrar equipos (nombre + escudo)
- [ ] Mostrar resultado si el partido ya finalizó
- [ ] Mostrar estado del partido (próximo, en vivo, finalizado)

### 2. Filtros
- [ ] Filtrar por competencia
- [ ] Filtrar por equipos en la base de datos
- [ ] Mostrar solo partidos de competencias y equipos que existen en BD

### 3. Rango de Fechas
- [ ] Mostrar partidos de los próximos 7-14 días
- [ ] Permitir navegar entre fechas

---

## 🏗️ Arquitectura Técnica

### Base de Datos
**Tablas existentes**:
- `competitions` - Competencias (La Liga, Premier League, Champions, etc)
- `teams` - Equipos
- `football_matches` - Partidos
- `team_competition` - Relación M:N equipos-competencias

### API Externa
**Endpoint**: `https://v3.football.api-sports.io/`
- Método: `GET /fixtures`
- Parámetros: `from`, `to`, `league`, `season`

---

## 📦 Componentes a Crear

### 1. Service: `FootballMatchesService`
**Ubicación**: `app/Services/FootballMatchesService.php`

**Métodos**:
```php
- getMatchesByDate(
    ?string $fromDate,
    ?string $toDate,
    ?int $competitionId,
    ?array $teamIds
  ): Collection
  
- groupMatchesByDate(
    Collection $matches
  ): array
  
- fetchFromAPIFootballSports(
    string $fromDate,
    string $toDate,
    int $league,
    int $season
  ): array
  
- syncMatchesWithDatabase(
    array $apiMatches,
    int $competitionId
  ): void
```

### 2. Controller: `MatchesController`
**Ubicación**: `app/Http/Controllers/Api/MatchesController.php`

**Endpoints**:
- `GET /api/matches/calendar` - Obtener partidos agrupados por día
- `GET /api/matches/by-competition/{competitionId}` - Partidos de una competencia
- `GET /api/matches/by-teams` - Partidos de equipos específicos

### 3. Transformers (API Response)
**Ubicación**: `app/Http/Resources/MatchCollection.php`

Estructurar respuesta:
```json
{
  "data": {
    "2026-02-10": [
      {
        "id": 1,
        "home_team": {
          "name": "Real Madrid",
          "crest_url": "..."
        },
        "away_team": {
          "name": "Barcelona",
          "crest_url": "..."
        },
        "time": "21:00",
        "status": "SCHEDULED|LIVE|FINISHED",
        "score": "2-1",
        "competition": "La Liga"
      }
    ]
  },
  "meta": {
    "from_date": "2026-02-10",
    "to_date": "2026-02-17"
  }
}
```

### 4. Vista (si se necesita Blade)
**Ubicación**: `resources/views/matches/calendar.blade.php`

Características:
- Diseño responsivo
- Scroll horizontal por días
- Tarjetas de partidos
- Indicadores de estado
- Logos de equipos

---

## 🔄 Flujo de Datos

```
1. Cliente solicita GET /api/matches/calendar
                        ↓
2. MatchesController valida parámetros
                        ↓
3. FootballMatchesService:
   a) Obtiene partidos de BD (filtrados)
   b) Agrupa por fecha
   c) Opcionalmente sincroniza con API externa
                        ↓
4. Transforma datos (MatchCollection)
                        ↓
5. Retorna JSON estructurado al cliente
```

---

## 📊 Estructura de Datos de Respuesta

### Entrada (Parámetros Query)
```
GET /api/matches/calendar?
  from_date=2026-02-10&
  to_date=2026-02-17&
  competition_id=1&
  team_ids[]=1,2,3
```

### Salida (JSON Response)
```json
{
  "success": true,
  "data": {
    "2026-02-10": [
      {
        "id": 1,
        "home_team": {
          "id": 1,
          "name": "Real Madrid CF",
          "crest_url": "https://..."
        },
        "away_team": {
          "id": 2,
          "name": "FC Barcelona",
          "crest_url": "https://..."
        },
        "kick_off_time": "21:00",
        "status": "SCHEDULED",
        "home_score": null,
        "away_score": null,
        "competition": {
          "id": 1,
          "name": "La Liga"
        }
      },
      {
        "id": 2,
        "home_team": {...},
        "away_team": {...},
        "kick_off_time": "19:30",
        "status": "FINISHED",
        "home_score": 2,
        "away_score": 1,
        "competition": {...}
      }
    ],
    "2026-02-11": [...]
  },
  "meta": {
    "from_date": "2026-02-10",
    "to_date": "2026-02-17",
    "total_matches": 42,
    "filtered_by_competition": 1,
    "filtered_by_teams": 3
  }
}
```

---

## 🛠️ Pasos de Implementación

### Fase 1: Backend Service
- [ ] Crear `FootballMatchesService`
- [ ] Implementar lógica de agrupación por fecha
- [ ] Implementar filtros por competencia y equipos
- [ ] Agregar soporte para sincronización con API-Sports

### Fase 2: API Endpoints
- [ ] Crear `MatchesController`
- [ ] Definir rutas en `routes/api.php`
- [ ] Crear `MatchCollection` transformer
- [ ] Validar parámetros de entrada

### Fase 3: Frontend (si aplica)
- [ ] Crear vista Blade o componente Vue/React
- [ ] Diseño responsive
- [ ] Filtros interactivos
- [ ] Manejo de estados (cargando, error)

### Fase 4: Testing
- [ ] Tests unitarios del Service
- [ ] Tests de API endpoints
- [ ] Tests de sincronización
- [ ] Manual testing con datos reales

---

## 🔐 Consideraciones de Seguridad

- [ ] Validar parámetros de entrada (fecha, IDs)
- [ ] Rate limiting en endpoint de API
- [ ] Cache de respuestas (5-10 minutos)
- [ ] Solo mostrar competencias y equipos autorizados
- [ ] Autenticación si es necesaria

---

## 🚀 Optimizaciones

### Caché
- Cachear respuesta agrupada por 5-10 minutos
- Invalidar cache cuando hay cambios en partidos

### Base de Datos
- Índices en `football_matches`: `(competition_id, match_date)`
- Índices en `team_competition`: `(competition_id, team_id)`
- Eager loading de relaciones (homeTeam, awayTeam, competition)

### API Externa
- Limitar llamadas a API-Sports a una vez por día (cron job)
- Caché de respuestas de API

---

## 📝 Notas Adicionales

- Considerar zona horaria del usuario
- Formatear fechas según locale
- Mostrar "HOY", "MAÑANA" en lugar de fechas
- Lazy loading de logos/crests
- Soporte para diferencias horarias (UTC, local)

---

## ✅ Checklist Final

- [ ] Código implementado
- [ ] Tests pasando
- [ ] Documentación actualizada
- [ ] PR creado y revisado
- [ ] Deployable a producción
