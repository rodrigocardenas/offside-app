# Documentación de API - Partidos / Calendario

## Endpoints Disponibles

### 1. Obtener Partidos Agrupados por Día
**Endpoint**: `GET /api/matches/calendar`

**Parámetros Query**:
```
from_date=2026-02-10&to_date=2026-02-17&competition_id=1&team_ids[]=1&team_ids[]=2&include_finished=true
```

**Parámetros Opcionales**:
- `from_date`: Fecha inicio (YYYY-MM-DD). Default: hoy
- `to_date`: Fecha fin (YYYY-MM-DD). Default: hoy + 7 días
- `competition_id`: ID de competencia (int)
- `team_ids[]`: Array de IDs de equipos
- `include_finished`: Incluir partidos finalizados (boolean). Default: true

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "2026-02-10": [
      {
        "id": 1,
        "external_id": "123456",
        "home_team": {
          "id": 1,
          "name": "Real Madrid CF",
          "crest_url": "https://crests.football-data.org/..."
        },
        "away_team": {
          "id": 2,
          "name": "FC Barcelona",
          "crest_url": "https://crests.football-data.org/..."
        },
        "kick_off_time": "21:00",
        "kick_off_timestamp": 1707548400,
        "status": "SCHEDULED",
        "score": {
          "home": null,
          "away": null
        },
        "penalties": {
          "home": null,
          "away": null
        },
        "competition": {
          "id": 1,
          "name": "La Liga"
        },
        "stage": "Matchday 20"
      },
      {
        "id": 2,
        "external_id": "123457",
        "home_team": {
          "id": 3,
          "name": "Atlético Madrid",
          "crest_url": "https://crests.football-data.org/..."
        },
        "away_team": {
          "id": 4,
          "name": "Valencia CF",
          "crest_url": "https://crests.football-data.org/..."
        },
        "kick_off_time": "19:30",
        "kick_off_timestamp": 1707544200,
        "status": "FINISHED",
        "score": {
          "home": 2,
          "away": 1
        },
        "penalties": {
          "home": null,
          "away": null
        },
        "competition": {
          "id": 1,
          "name": "La Liga"
        },
        "stage": "Matchday 20"
      }
    ],
    "2026-02-11": [...]
  },
  "meta": {
    "from_date": "2026-02-10",
    "to_date": "2026-02-17",
    "competition_id": 1,
    "teams_count": 2,
    "total_matches": 42
  }
}
```

**Ejemplo cURL**:
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?from_date=2026-02-10&to_date=2026-02-17"
```

---

### 2. Obtener Partidos por Competencia
**Endpoint**: `GET /api/matches/by-competition/{competitionId}`

**Parámetros**:
- `competitionId`: ID de la competencia (path parameter)
- `from_date`: Fecha inicio (opcional, query)
- `to_date`: Fecha fin (opcional, query)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "competition_id": 1,
  "data": {
    "2026-02-10": [...],
    "2026-02-11": [...]
  },
  "meta": {
    "total_matches": 10
  }
}
```

**Ejemplo cURL**:
```bash
curl -X GET "http://localhost:8000/api/matches/by-competition/1?from_date=2026-02-10&to_date=2026-02-17"
```

---

### 3. Obtener Partidos de Equipos Específicos
**Endpoint**: `GET /api/matches/by-teams`

**Parámetros Query**:
```
team_ids[]=1&team_ids[]=2&team_ids[]=3&from_date=2026-02-10&to_date=2026-02-17
```

**Parámetros**:
- `team_ids[]`: Array de IDs de equipos (requerido)
- `from_date`: Fecha inicio (opcional)
- `to_date`: Fecha fin (opcional)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "teams": [1, 2, 3],
  "data": {
    "2026-02-10": [...],
    "2026-02-11": [...]
  },
  "meta": {
    "teams_count": 3,
    "total_matches": 8
  }
}
```

**Ejemplo cURL**:
```bash
curl -X GET "http://localhost:8000/api/matches/by-teams?team_ids[]=1&team_ids[]=2&from_date=2026-02-10"
```

---

### 4. Obtener Competencias Disponibles
**Endpoint**: `GET /api/matches/competitions`

**Parámetros**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "La Liga",
      "type": "laliga",
      "country": "Spain",
      "created_at": "2025-04-22T10:00:00Z",
      "updated_at": "2025-04-22T10:00:00Z"
    },
    {
      "id": 2,
      "name": "Premier League",
      "type": "premier",
      "country": "England",
      "created_at": "2025-04-22T10:00:00Z",
      "updated_at": "2025-04-22T10:00:00Z"
    }
  ],
  "meta": {
    "total": 2
  }
}
```

**Ejemplo cURL**:
```bash
curl -X GET "http://localhost:8000/api/matches/competitions"
```

---

### 5. Obtener Equipos Disponibles
**Endpoint**: `GET /api/matches/teams`

**Parámetros**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Real Madrid CF",
      "crest_url": "https://crests.football-data.org/..."
    },
    {
      "id": 2,
      "name": "FC Barcelona",
      "crest_url": "https://crests.football-data.org/..."
    }
  ],
  "meta": {
    "total": 2
  }
}
```

**Ejemplo cURL**:
```bash
curl -X GET "http://localhost:8000/api/matches/teams"
```

---

### 6. Obtener Estadísticas de Partidos
**Endpoint**: `GET /api/matches/statistics`

**Parámetros Query**:
```
from_date=2026-02-10&to_date=2026-02-17&competition_id=1
```

**Parámetros**:
- `from_date`: Fecha inicio (opcional)
- `to_date`: Fecha fin (opcional)
- `competition_id`: ID de competencia (opcional)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "total": 42,
    "scheduled": 15,
    "live": 2,
    "finished": 25
  }
}
```

**Ejemplo cURL**:
```bash
curl -X GET "http://localhost:8000/api/matches/statistics?from_date=2026-02-10&to_date=2026-02-17"
```

---

### 7. Sincronizar Partidos desde API Externa (Requiere Autenticación)
**Endpoint**: `POST /api/matches/sync`

**Headers Requeridos**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body**:
```json
{
  "competition_id": 1,
  "league_id": 2014,
  "season": 2025
}
```

**Parámetros Body**:
- `competition_id`: ID de competencia en nuestra BD (int, requerido)
- `league_id`: ID de liga en API-Sports (int, requerido)
- `season`: Temporada (int, requerido)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Sincronizados 38 partidos",
  "synced": 38
}
```

**Respuesta Error (400)**:
```json
{
  "success": false,
  "message": "Competencia no encontrada",
  "synced": 0
}
```

**Ejemplo cURL**:
```bash
curl -X POST "http://localhost:8000/api/matches/sync" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "competition_id": 1,
    "league_id": 2014,
    "season": 2025
  }'
```

---

## Códigos de Estado HTTP

| Status | Descripción |
|--------|-------------|
| 200 | Exitoso |
| 400 | Error en sincronización |
| 422 | Validación fallida |
| 500 | Error interno del servidor |

---

## Ejemplos de Uso en Cliente

### JavaScript / Fetch API

```javascript
// Obtener partidos del calendario
async function getMatches() {
  const response = await fetch(
    '/api/matches/calendar?from_date=2026-02-10&to_date=2026-02-17'
  );
  const data = await response.json();
  
  if (data.success) {
    console.log('Partidos:', data.data);
    console.log('Total:', data.meta.total_matches);
  }
}

// Obtener partidos de equipos específicos
async function getTeamMatches(teamIds) {
  const params = new URLSearchParams();
  teamIds.forEach(id => params.append('team_ids[]', id));
  params.append('from_date', '2026-02-10');
  
  const response = await fetch(`/api/matches/by-teams?${params}`);
  return await response.json();
}

// Obtener estadísticas
async function getStats() {
  const response = await fetch('/api/matches/statistics');
  return await response.json();
}
```

### Vue.js / Axios

```javascript
import axios from 'axios';

// Servicio para partidos
const matchesService = {
  async getCalendar(fromDate, toDate, competitionId = null) {
    const response = await axios.get('/api/matches/calendar', {
      params: {
        from_date: fromDate,
        to_date: toDate,
        competition_id: competitionId
      }
    });
    return response.data;
  },

  async getByTeams(teamIds, fromDate, toDate) {
    const response = await axios.get('/api/matches/by-teams', {
      params: {
        team_ids: teamIds,
        from_date: fromDate,
        to_date: toDate
      }
    });
    return response.data;
  },

  async getCompetitions() {
    const response = await axios.get('/api/matches/competitions');
    return response.data;
  },

  async getTeams() {
    const response = await axios.get('/api/matches/teams');
    return response.data;
  }
};

export default matchesService;
```

---

## Notas Importantes

1. **Caché**: Las respuestas se cachean por 10 minutos por defecto
2. **Zona Horaria**: Las horas se devuelven en UTC. Se recomienda convertir en el cliente según la zona horaria del usuario
3. **Validación**: Todos los parámetros se validan en el servidor
4. **Rate Limiting**: Se recomienda implementar rate limiting en producción
5. **Autenticación**: La mayoría de endpoints son públicos excepto `/matches/sync` que requiere token

