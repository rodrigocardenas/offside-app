# Configuración de Variables de Entorno para Matches Calendar API

## Variables Necesarias en `.env`

Agregar o actualizar las siguientes variables en el archivo `.env`:

```env
# ============================================================================
# Football-Data.org API (si usas esta API)
# ============================================================================
FOOTBALL_DATA_API_KEY=tu_api_key_aqui

# ============================================================================
# Football-Data.org API Sports (v3.football.api-sports.io)
# ============================================================================
FOOTBALL_API_SPORTS_KEY=tu_rapid_api_key_aqui
FOOTBALL_API_SPORTS_HOST=v3.football.api-sports.io

# ============================================================================
# Caché (usa redis para mejor performance)
# ============================================================================
CACHE_DRIVER=redis
# O alternativamente:
# CACHE_DRIVER=database
# CACHE_DRIVER=file
```

---

## Cómo Obtener las API Keys

### 1. Football-Data.org API
1. Ir a: https://www.football-data.org/
2. Registrarse / Iniciar sesión
3. En "Account" → "API Token"
4. Copiar el token
5. Pegar en `FOOTBALL_DATA_API_KEY`

**Nota**: La versión gratuita tiene límites de requests

---

### 2. Football-Data.org API Sports (RapidAPI)
1. Ir a: https://rapidapi.com/api-sports/api/api-football
2. Registrarse / Iniciar sesión
3. Hacer clic en "Subscribe to Test"
4. Copiar tu API Key de las opciones mostradas
5. Pegar en `FOOTBALL_API_SPORTS_KEY`

**Plan Gratuito**: 100 requests/mes

---

## Configuración en `config/services.php`

El archivo de configuración debería tener (si no existe, crearlo):

```php
<?php

return [
    // ... otras configuraciones ...

    'football_data_api_key' => env('FOOTBALL_DATA_API_KEY'),
    
    'football_api_sports_key' => env('FOOTBALL_API_SPORTS_KEY'),
    'football_api_sports_host' => env('FOOTBALL_API_SPORTS_HOST'),

    // ... más configuraciones ...
];
```

---

## Testing sin API Externa

Si quieres probar la API sin hacer calls a servicios externos:

### Opción 1: Datos Mock
En `MatchesCalendarService`, comentar el método `fetchFromAPIFootballSports()` y usar datos mockados:

```php
protected function fetchFromAPIFootballSports(...): ?array
{
    // Descomentar para usar datos reales
    // try {
    //     $response = Http::timeout(30)
    //         ->withHeaders([...])
    //         ->get(...);
    // ...
    
    // Retornar datos mockados para testing
    return [
        [
            'fixture' => [
                'id' => 1,
                'date' => now()->toIso8601String(),
                'status' => 'SCHEDULED',
                'round' => 'Matchday 1'
            ],
            'teams' => [
                'home' => ['name' => 'Real Madrid'],
                'away' => ['name' => 'Barcelona']
            ],
            'goals' => [
                'home' => null,
                'away' => null
            ],
            'score' => [
                'penalty' => [
                    'home' => null,
                    'away' => null
                ]
            ]
        ]
    ];
}
```

### Opción 2: Environment Variables para Testing
En `.env.testing`:

```env
FOOTBALL_API_SPORTS_KEY=dummy_key_for_testing
```

---

## Verificar Configuración

### Con Artisan Tinker
```bash
php artisan tinker

# Verificar si las variables se cargan
> config('services.football_api_sports_key')

# Debería retornar tu API key
```

### Verificar Conexión a API
```bash
php artisan tinker

> $service = app(App\Services\MatchesCalendarService::class);
> $result = $service->fetchFromAPIFootballSports(
    '2026-02-01',
    '2026-02-07',
    2014,
    2025
  );
> $result // Ver si retorna datos
```

---

## Troubleshooting

### Error: "FOOTBALL_API_SPORTS_KEY not found"
1. Verificar que `.env` contenga la variable
2. Ejecutar: `php artisan config:cache`
3. Ejecutar: `php artisan cache:clear`

### Error: "Unauthorized" (401) desde API
1. Verificar que el API key sea correcto
2. Verificar que no haya expirado
3. Verificar plan activo en RapidAPI

### Error: "Rate limit exceeded"
1. Plan está limitado a X requests
2. Esperar a que se resetee el limit
3. Considerar upgrade de plan

### Error: "Service Unavailable" (503)
1. API externa puede estar caída
2. Intentar de nuevo en unos minutos
3. Revisar status de API en https://status.rapidapi.com/

---

## Rate Limiting

Para proteger tu API, considera agregar rate limiting en Laravel:

### En `.env`
```env
API_RATE_LIMIT=60 # 60 requests por minuto
```

### En `routes/api.php`
```php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/matches/calendar', [MatchesController::class, 'calendar']);
    // ... otros endpoints ...
});
```

---

## Caché Recomendado

### Para Production: Redis
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Para Development: File
```env
CACHE_DRIVER=file
```

### Para Testing: Array
```env
CACHE_DRIVER=array
```

---

## Variables Adicionales (Opcionales)

```env
# Timeout para requests a API externa (segundos)
FOOTBALL_API_TIMEOUT=30

# Habilitar logging de requests a API
FOOTBALL_API_LOG=true

# Cache duration (minutos)
MATCHES_CACHE_DURATION=10

# Máximo de días a futuro para obtener partidos
MATCHES_MAX_DAYS_AHEAD=90
```

---

## Verificar Setup Completo

```bash
# Archivo .env correcto
cat .env | grep FOOTBALL

# Base de datos up-to-date
php artisan migrate:status

# Caché funcionando
php artisan cache:clear

# Verificar modelos
php artisan tinker
> App\Models\Competition::count()
> App\Models\Team::count()
> App\Models\FootballMatch::count()

# Test de endpoints
curl http://localhost:8000/api/matches/competitions
```

---

## Notas de Seguridad

1. **Nunca commit `.env`** - usar `.env.example`
2. **Proteger API keys** - usar variables de entorno
3. **Rate limiting** - prevenir abuse
4. **Logs** - revisar errores regularmente
5. **HTTPS** - siempre en production
6. **Validación** - validar todos los inputs

