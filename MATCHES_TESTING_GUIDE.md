# Guía de Testing - Matches Calendar API

## Requisitos Previos

1. Base de datos con datos de competencias, equipos y partidos
2. Servidor Laravel corriendo en `http://localhost:8000`
3. Postman o similar para hacer requests

---

## 1. Preparar Base de Datos

### Ejecutar migraciones
```bash
php artisan migrate --refresh
```

### Cargar datos de prueba (seeders)
```bash
php artisan db:seed
```

O para seeders específicos:
```bash
php artisan db:seed --class=CompetitionSeeder
php artisan db:seed --class=FootballMatchSeeder
```

---

## 2. Verificar Datos en BD

### Con artisan tinker
```bash
php artisan tinker

# Verificar competencias
> App\Models\Competition::all();

# Verificar equipos
> App\Models\Team::limit(10)->get();

# Verificar partidos
> App\Models\FootballMatch::with(['homeTeam', 'awayTeam', 'competition'])->limit(5)->get();

# Verificar cantidad de partidos
> App\Models\FootballMatch::count();
```

---

## 3. Test Manual con cURL

### Test 1: Obtener Calendario de Partidos
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?from_date=2026-02-01&to_date=2026-02-28" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 200
- `success`: true
- `data` contiene objeto con fechas como claves
- Cada fecha tiene array de partidos

### Test 2: Filtrar por Competencia
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?competition_id=1" \
  -H "Accept: application/json"
```

**Validar**:
- Solo se devuelven partidos de competencia_id = 1

### Test 3: Filtrar por Equipos
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?team_ids[]=1&team_ids[]=2" \
  -H "Accept: application/json"
```

**Validar**:
- Solo equipos con ID 1 o 2 están en los resultados

### Test 4: Obtener Partidos por Competencia
```bash
curl -X GET "http://localhost:8000/api/matches/by-competition/1" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 200
- Respuesta contiene solo partidos de competencia_id = 1

### Test 5: Obtener Partidos de Equipos
```bash
curl -X GET "http://localhost:8000/api/matches/by-teams?team_ids[]=1&team_ids[]=2&team_ids[]=3" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 200
- Teams en meta coincide con request

### Test 6: Obtener Competencias Disponibles
```bash
curl -X GET "http://localhost:8000/api/matches/competitions" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 200
- `data` es array con competencias
- Cada competencia tiene: id, name, type, country

### Test 7: Obtener Equipos Disponibles
```bash
curl -X GET "http://localhost:8000/api/matches/teams" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 200
- `data` es array con equipos
- Cada equipo tiene: id, name, crest_url

### Test 8: Obtener Estadísticas
```bash
curl -X GET "http://localhost:8000/api/matches/statistics?from_date=2026-02-01&to_date=2026-02-28" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 200
- `data` contiene: total, scheduled, live, finished
- total = scheduled + live + finished

---

## 4. Testing de Validaciones

### Test: Fecha Inválida
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?from_date=invalid" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 422
- `errors.from_date` contiene mensaje de validación

### Test: Competencia No Existe
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?competition_id=9999" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 422
- `errors.competition_id` contiene mensaje de validación

### Test: Fecha Final Anterior a Inicial
```bash
curl -X GET "http://localhost:8000/api/matches/calendar?from_date=2026-02-28&to_date=2026-02-01" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 422
- Error sobre fechas inválidas

### Test: Team IDs sin Array
```bash
curl -X GET "http://localhost:8000/api/matches/by-teams?team_ids=1,2,3" \
  -H "Accept: application/json"
```

**Validar**:
- Status code: 422
- Error indicando que team_ids debe ser array

---

## 5. Testing de Caché

### Test 1: Primera Llamada (Sin Caché)
```bash
time curl -X GET "http://localhost:8000/api/matches/calendar" \
  -H "Accept: application/json" \
  -w "\nTime: %{time_total}s\n"
```

Anotate el tiempo de respuesta.

### Test 2: Segunda Llamada (Con Caché)
```bash
# Ejecutar inmediatamente después
time curl -X GET "http://localhost:8000/api/matches/calendar" \
  -H "Accept: application/json" \
  -w "\nTime: %{time_total}s\n"
```

**Validar**:
- La segunda llamada es significativamente más rápida (caché funcionando)

---

## 6. Testing de Sincronización (Requiere Autenticación)

### Obtener Token de Autenticación
```bash
# En artisan tinker
php artisan tinker
> $user = App\Models\User::first();
> $token = $user->createToken('test')->plainTextToken;
> $token
```

### Test: Sincronizar Partidos
```bash
curl -X POST "http://localhost:8000/api/matches/sync" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "competition_id": 1,
    "league_id": 2014,
    "season": 2025
  }'
```

**Nota**: Requiere que `FOOTBALL_API_SPORTS_KEY` esté configurado en `.env`

**Validar**:
- Status code: 200
- `success`: true
- `synced` > 0

---

## 7. Testing con Postman

### Crear Colección
1. Abrir Postman
2. Crear nueva colección: "Matches Calendar API"
3. Agregar requests:

#### GET - Calendar
- URL: `{{base_url}}/api/matches/calendar`
- Params:
  - from_date: 2026-02-01
  - to_date: 2026-02-28
  - competition_id: 1

#### GET - By Competition
- URL: `{{base_url}}/api/matches/by-competition/1`
- Params:
  - from_date: 2026-02-01

#### GET - By Teams
- URL: `{{base_url}}/api/matches/by-teams`
- Params:
  - team_ids[]: [1, 2, 3]

#### GET - Competitions
- URL: `{{base_url}}/api/matches/competitions`

#### GET - Teams
- URL: `{{base_url}}/api/matches/teams`

#### GET - Statistics
- URL: `{{base_url}}/api/matches/statistics`
- Params:
  - from_date: 2026-02-01
  - to_date: 2026-02-28

#### POST - Sync
- URL: `{{base_url}}/api/matches/sync`
- Auth: Bearer Token ({{token}})
- Body (JSON):
  ```json
  {
    "competition_id": 1,
    "league_id": 2014,
    "season": 2025
  }
  ```

### Agregar Variables de Entorno
1. En Postman, crear environment "Local"
2. Variables:
   - `base_url`: http://localhost:8000
   - `token`: {tu_token_aqui}

---

## 8. Tests Unitarios

### Crear Test
```bash
php artisan make:test MatchesCalendarServiceTest --unit
php artisan make:test MatchesControllerTest
```

### Ejemplo de Test Unitario

```php
<?php

namespace Tests\Unit;

use App\Services\MatchesCalendarService;
use App\Models\FootballMatch;
use App\Models\Competition;
use Tests\TestCase;
use Carbon\Carbon;

class MatchesCalendarServiceTest extends TestCase
{
    protected MatchesCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MatchesCalendarService::class);
    }

    public function test_get_matches_by_date()
    {
        // Crear datos de prueba
        $competition = Competition::factory()->create();
        FootballMatch::factory()->create([
            'competition_id' => $competition->id,
            'match_date' => Carbon::now(),
        ]);

        // Llamar servicio
        $matches = $this->service->getMatchesByDate(
            fromDate: Carbon::today()->toDateString(),
            toDate: Carbon::today()->addDays(7)->toDateString()
        );

        // Validaciones
        $this->assertIsArray($matches);
        $this->assertNotEmpty($matches);
    }

    public function test_group_matches_by_date()
    {
        $matches = collect([
            (object)['match_date' => '2026-02-10 21:00'],
            (object)['match_date' => '2026-02-10 19:00'],
            (object)['match_date' => '2026-02-11 20:00'],
        ]);

        $grouped = $this->service->groupMatchesByDate($matches);

        $this->assertArrayHasKey('2026-02-10', $grouped);
        $this->assertArrayHasKey('2026-02-11', $grouped);
        $this->assertCount(2, $grouped['2026-02-10']);
    }
}
```

### Ejecutar Tests
```bash
php artisan test
php artisan test --filter=MatchesCalendarServiceTest
php artisan test --coverage
```

---

## 9. Checklist de Testing

- [ ] GET /api/matches/calendar retorna datos correctos
- [ ] GET /api/matches/by-competition/{id} filtra correctamente
- [ ] GET /api/matches/by-teams filtra por múltiples equipos
- [ ] GET /api/matches/competitions retorna lista completa
- [ ] GET /api/matches/teams retorna lista completa
- [ ] GET /api/matches/statistics retorna números correctos
- [ ] POST /api/matches/sync requiere autenticación
- [ ] Validaciones de fecha funcionan
- [ ] Validaciones de IDs funcionan
- [ ] Caché funciona (2da llamada es más rápida)
- [ ] Manejo de errores es correcto (422, 500)
- [ ] Respuestas tienen estructura correcta
- [ ] Paginación funciona (si aplica)
- [ ] Rate limiting no se activa (si está configurado)

---

## 10. Performance Testing

### Generar muchos datos
```bash
php artisan tinker

# Generar 1000 partidos
> App\Models\FootballMatch::factory(1000)->create();

# Generar 500 equipos
> App\Models\Team::factory(500)->create();
```

### Benchmarking
```bash
# Instalar Apache Bench
sudo apt-get install apache2-utils

# Test con 100 requests, 10 concurrentes
ab -n 100 -c 10 http://localhost:8000/api/matches/calendar
```

---

## Troubleshooting

### Error 500 en Sync
- Verificar que `FOOTBALL_API_SPORTS_KEY` esté en `.env`
- Revisar logs: `storage/logs/laravel.log`

### Validación Fallida (422)
- Revisar formato de parámetros
- Asegurar que IDs existan en BD

### Caché no funciona
```bash
php artisan cache:clear
php artisan config:cache
```

### Datos desactualizados
```bash
php artisan cache:flush
```

