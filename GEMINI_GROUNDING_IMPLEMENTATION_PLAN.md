# Google Gemini Grounding Integration Plan

**Objetivo:** Integrar Google Gemini con búsqueda web (grounding) para enriquecer fixtures y resultados de partidos de fútbol con análisis en tiempo real.

**Estado Proyecto:** ✅ Listo - Infraestructura solida con OpenAI ya integrado y patrones API establecidos.

---

## 📋 Tabla de Contenidos

1. [Análisis del Proyecto](#análisis-del-proyecto)
2. [Fase 1: Setup Inicial](#fase-1-setup-inicial)
3. [Fase 2: Servicio Gemini](#fase-2-servicio-gemini)
4. [Fase 3: Jobs Asincronos](#fase-3-jobs-asincronos)
5. [Fase 4: Almacenamiento BD](#fase-4-almacenamiento-bd)
6. [Fase 5: Controladores y Rutas API](#fase-5-controladores-y-rutas-api)
7. [Fase 6: Pruebas y Optimizaciones](#fase-6-pruebas-y-optimizaciones)
8. [Consideraciones de Producción](#consideraciones-de-producción)

---

## 🔍 Análisis del Proyecto

### ✅ Fortalezas Existentes

| Aspecto | Estado | Detalle |
|--------|--------|---------|
| **API Integration** | ✅ Maduro | OpenAIService + RapidAPI football integradas |
| **Modelos BD** | ✅ Completo | FootballMatch, Competition, Team, Player, Stadium |
| **Queue System** | ✅ Configurado | Laravel Horizon + 8 jobs existentes |
| **Scheduler** | ✅ Activo | Procesa partidos finalizados cada hora |
| **Logging** | ✅ Completo | Logs en storage/logs/ para debugging |
| **Auth API** | ✅ Sanctum | Sistema de tokens para API endpoints |
| **Error Handling** | ✅ Robusto | Retry logic y excepciones personalizadas |

### 📊 Datos Disponibles para Grounding

- **Pre-match:** Equipos, historial H2H, forma reciente
- **Live:** Eventos, posesión, tiros, tarjetas
- **Post-match:** Estadísticas completas, desempeño de jugadores
- **Context:** Valor de mercado, lesiones (expandible)

### ⚠️ Consideraciones Identificadas

1. **Queue Worker** - Actualmente `sync`, cambiar a `database`/`redis` para jobs efectivos
2. **Paquete Exacto** - Validar `hosseinhezami/laravel-gemini` vs fallback HTTP directo
3. **Rate Limiting** - Gemini API tiene cuotas; implementar throttling
4. **Costo** - API es de pago; caché agresivo necesario
5. **Token Budget** - Limitar datos enviados a grounding (últimos 7 días resultados + próximos 7 fixtures)

---

## 🚀 Fase 1: Setup Inicial

### 1.1 Instalar Paquete Gemini

```bash
# Opción principal (actualizado a 2025)
composer require hosseinhezami/laravel-gemini

# Si falla, fallback a paquete oficial
composer require google-gemini-php/laravel
```

**Expected:**
- Paquete instalado en `vendor/`
- ServiceProvider auto-descoberto por Laravel 5.5+
- Config archivo puede requerir publicación manual

### 1.2 Publicar Configuración

```bash
# Intenta primera opción
php artisan vendor:publish --provider="HosseinHezami\LaravelGemini\LaravelGeminiServiceProvider" --tag="config"

# O para google-gemini-php
php artisan vendor:publish --provider="Gemini\Laravel\GeminiServiceProvider" --tag="config"
```

**Resultado esperado:**
- Nuevo archivo `config/gemini.php` creado
- Contiene configuración para API key, modelo por defecto, etc.

### 1.3 Configurar Variables de Entorno

**Archivo:** `.env`

```env
# Google Gemini API
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXX
GEMINI_MODEL=gemini-2.5-flash
GEMINI_GROUNDING_ENABLED=true
```

**Archivo:** `.env.example` (agregar para documentación)

```env
# Google Gemini API Configuration
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
GEMINI_GROUNDING_ENABLED=true
```

### 1.4 Verificar Instalación

```bash
php artisan tinker
# En tinker:
> Gemini::generateContent('Hola, ¿qué es un fixture en fútbol?')
```

**Resultado esperado:** Respuesta JSON con contenido de Gemini.

---

## 🤖 Fase 2: Servicio Gemini

### 2.1 Crear Estructura de Carpetas

```bash
# Ya existe app/Services/, crearemos dentro
# app/Services/GeminiService.php
```

### 2.2 Implementar GeminiService

**Archivo:** `app/Services/GeminiService.php`

Características:
- Métodos: `getFixtures($league)`, `getResults($league, $date)`, `callGemini($prompt, $tools = [])`
- Habilitar grounding con `['google_search' => []]` en tools
- Parsear respuestas JSON
- Extraer metadata de citas (groundingSupports)
- Manejo de errores con retry logic
- Logging de llamadas

Patrón a seguir: Usar estructura similar a `app/Services/OpenAIService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    protected $model = 'gemini-2.5-flash';
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected $maxRetries = 3;
    protected $retryDelay = 2; // segundos

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Obtener fixtures (calendario) de una liga con grounding
     */
    public function getFixtures($league, $forceRefresh = false)
    {
        $cacheKey = "gemini_fixtures_{$league}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $prompt = $this->buildFixturesPrompt($league);
        $result = $this->callGemini($prompt, ['google_search' => []]);

        if ($result) {
            Cache::put($cacheKey, $result, now()->addHours(24));
        }

        return $result;
    }

    /**
     * Obtener resultados de una liga para una fecha específica
     */
    public function getResults($league, $date = null, $forceRefresh = false)
    {
        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "gemini_results_{$league}_{$date}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $prompt = $this->buildResultsPrompt($league, $date);
        $result = $this->callGemini($prompt, ['google_search' => []]);

        if ($result) {
            Cache::put($cacheKey, $result, now()->addHours(48));
        }

        return $result;
    }

    /**
     * Llamada principal a Gemini con grounding
     */
    private function callGemini($prompt, $tools = [])
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post(
                    "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'tools' => $tools ? [['type' => 'google_search']] : [],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'topK' => 40,
                            'topP' => 0.95,
                            'maxOutputTokens' => 2048,
                        ]
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Extraer texto de respuesta
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    
                    // Extraer metadata de grounding (citas)
                    $groundingMetadata = $data['candidates'][0]['groundingMetadata'] ?? [];

                    Log::info('Gemini API call successful', [
                        'prompt_length' => strlen($prompt),
                        'response_length' => strlen($text),
                        'grounding_citations' => count($groundingMetadata['groundingSupports'] ?? [])
                    ]);

                    // Intentar parsear JSON de respuesta
                    $parsedJson = json_decode($text, true);
                    
                    return [
                        'success' => true,
                        'data' => $parsedJson ?? $text,
                        'raw_text' => $text,
                        'grounding_citations' => $groundingMetadata['groundingSupports'] ?? [],
                        'grounding_metadata' => $groundingMetadata
                    ];
                }

                // Retry con exponential backoff
                $attempt++;
                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay * $attempt);
                    continue;
                }

                Log::error('Gemini API failed after retries', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);

                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'API Error',
                    'status' => $response->status()
                ];

            } catch (\Exception $e) {
                Log::error('Gemini Service Exception', [
                    'message' => $e->getMessage(),
                    'attempt' => $attempt + 1
                ]);

                $attempt++;
                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay * $attempt);
                    continue;
                }

                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Max retries exceeded'
        ];
    }

    /**
     * Construir prompt para fixtures
     */
    private function buildFixturesPrompt($league)
    {
        return "Busca en la web el calendario (fixtures) de partidos de {$league} para la próxima semana. "
            . "Estructura la respuesta en JSON válido con este formato: "
            . "[{\"fecha\": \"YYYY-MM-DD\", \"partido\": \"Equipo1 vs Equipo2\", \"hora\": \"HH:MM UTC\", \"estado\": \"scheduled\"}]. "
            . "Solo incluye datos verificados de fuentes confiables como ESPN, sitios oficiales de ligas, o Flashscore. "
            . "Asegúrate de que sea JSON valido y parseable.";
    }

    /**
     * Construir prompt para resultados
     */
    private function buildResultsPrompt($league, $date)
    {
        return "Busca en la web los resultados de todos los partidos de {$league} del {$date}. "
            . "Estructura la respuesta en JSON válido con este formato: "
            . "[{\"fecha\": \"YYYY-MM-DD\", \"partido\": \"Equipo1 vs Equipo2\", \"resultado\": \"2-1\", \"estado\": \"finished\"}]. "
            . "Usa fuentes actualizadas como ESPN, la web oficial de la liga, o Flashscore. "
            . "Asegúrate de que sea JSON valido y parseable.";
    }
}
```

### 2.3 Integración con Laravel Service Container

**Archivo:** `app/Providers/AppServiceProvider.php`

Agregar en método `register()`:

```php
// Ya existe el archivo, solo agregar el binding si es necesario
// Generalmente Laravel auto-descubre el servicio
```

O crear Facade (opcional, pero recomendado):

**Archivo:** `app/Services/Facades/Gemini.php`

```php
<?php

namespace App\Services\Facades;

use Illuminate\Support\Facades\Facade;

class Gemini extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\GeminiService::class;
    }
}
```

---

## 📦 Fase 3: Jobs Asincronos

### 3.1 Crear Jobs

```bash
php artisan make:job GenerateFixtureGroundingJob
php artisan make:job GenerateResultAnalysisJob
```

### 3.2 Implementar GenerateFixtureGroundingJob

**Archivo:** `app/Jobs/GenerateFixtureGroundingJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Competition;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateFixtureGroundingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $competition;
    public $tries = 3;
    public $timeout = 300; // 5 minutos

    public function __construct(Competition $competition)
    {
        $this->competition = $competition;
    }

    public function handle()
    {
        try {
            $service = new GeminiService();
            
            Log::info("Generating fixture grounding for: {$this->competition->name}");
            
            $result = $service->getFixtures($this->competition->name, $forceRefresh = true);
            
            if ($result['success']) {
                // Guardar en BD (ver Fase 4)
                // $this->saveToDatabase($result);
                
                Log::info("Fixture grounding generated successfully", [
                    'competition' => $this->competition->name,
                    'fixtures_count' => count($result['data'] ?? [])
                ]);
            } else {
                Log::warning("Fixture grounding failed", [
                    'competition' => $this->competition->name,
                    'error' => $result['error']
                ]);
            }
        } catch (\Exception $e) {
            Log::error("GenerateFixtureGroundingJob exception", [
                'message' => $e->getMessage(),
                'competition' => $this->competition->name
            ]);
            
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("GenerateFixtureGroundingJob FAILED", [
            'message' => $exception->getMessage(),
            'competition' => $this->competition->name
        ]);
    }
}
```

### 3.3 Implementar GenerateResultAnalysisJob

**Archivo:** `app/Jobs/GenerateResultAnalysisJob.php`

Similar a GenerateFixtureGroundingJob, pero:

```php
public function handle()
{
    try {
        $service = new GeminiService();
        $yesterday = now()->subDay()->format('Y-m-d');
        
        Log::info("Generating result analysis for: {$this->competition->name} on {$yesterday}");
        
        $result = $service->getResults($this->competition->name, $yesterday, $forceRefresh = true);
        
        // ... guardar en BD
    }
}
```

### 3.4 Registrar en Scheduler

**Archivo:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Existing schedule...
    $schedule->command('matches:process-recently-finished')->hourly();
    
    // NUEVOS: Gemini Grounding
    // Ejecutar para cada competencia activa
    $schedule->call(function () {
        $competitions = \App\Models\Competition::active()->get();
        foreach ($competitions as $competition) {
            \App\Jobs\GenerateFixtureGroundingJob::dispatch($competition);
        }
    })->weekly(); // Ejecutar weekly (ej: lunes a las 00:00)
    
    // Resultados: daily a las 02:00 UTC
    $schedule->call(function () {
        $competitions = \App\Models\Competition::active()->get();
        foreach ($competitions as $competition) {
            \App\Jobs\GenerateResultAnalysisJob::dispatch($competition);
        }
    })->dailyAt('02:00');
    
    // Cleanup de cache antiguo (opcional)
    $schedule->call(function () {
        \Illuminate\Support\Facades\Cache::flush();
    })->monthly();
}
```

---

## 💾 Fase 4: Almacenamiento en BD

### 4.1 Crear Modelo y Migración

```bash
php artisan make:model GroundingResult -m
```

### 4.2 Migración

**Archivo:** `database/migrations/YYYY_MM_DD_XXXXXX_create_grounding_results_table.php`

```php
Schema::create('grounding_results', function (Blueprint $table) {
    $table->id();
    $table->foreignIdFor(\App\Models\Competition::class)->nullable();
    $table->string('league_name');
    $table->enum('type', ['fixtures', 'results']); // fixtures | results
    $table->date('for_date')->nullable(); // Para resultados específicos
    $table->text('prompt');
    $table->longText('response_json');
    $table->longText('raw_response');
    $table->json('grounding_citations')->nullable(); // Citas del grounding
    $table->json('grounding_metadata')->nullable();
    $table->integer('api_calls_made')->default(1);
    $table->integer('response_time_ms')->nullable();
    $table->boolean('is_cached')->default(false);
    $table->timestamps();
    
    $table->index(['league_name', 'type', 'for_date']);
});
```

### 4.3 Modelo

**Archivo:** `app/Models/GroundingResult.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroundingResult extends Model
{
    protected $fillable = [
        'competition_id',
        'league_name',
        'type',
        'for_date',
        'prompt',
        'response_json',
        'raw_response',
        'grounding_citations',
        'grounding_metadata',
        'api_calls_made',
        'response_time_ms',
        'is_cached',
    ];

    protected $casts = [
        'grounding_citations' => 'json',
        'grounding_metadata' => 'json',
        'for_date' => 'date',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function getParsedDataAttribute()
    {
        return json_decode($this->response_json, true);
    }
}
```

### 4.4 Actualizar GeminiService para guardar en BD

En el método `getFixtures()` y `getResults()`, después de llamar a `callGemini()`:

```php
private function saveToDatabase($result, $league, $type, $prompt, $date = null)
{
    \App\Models\GroundingResult::create([
        'league_name' => $league,
        'type' => $type,
        'for_date' => $date,
        'prompt' => $prompt,
        'response_json' => json_encode($result['data'] ?? []),
        'raw_response' => $result['raw_text'] ?? '',
        'grounding_citations' => $result['grounding_citations'] ?? [],
        'grounding_metadata' => $result['grounding_metadata'] ?? [],
        'response_time_ms' => $result['response_time_ms'] ?? 0,
        'is_cached' => false,
    ]);
}
```

---

## 🌐 Fase 5: Controladores y Rutas API

### 5.1 Crear Controlador

```bash
php artisan make:controller GeminiController
```

### 5.2 Implementar GeminiController

**Archivo:** `app/Http/Controllers/GeminiController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Models\GroundingResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiController extends Controller
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * GET /api/gemini/fixtures?league=Premier+League
     */
    public function fixtures(Request $request)
    {
        try {
            $league = $request->input('league', 'Premier League');
            $forceRefresh = $request->boolean('refresh', false);

            $result = $this->gemini->getFixtures($league, $forceRefresh);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'],
                    'status' => $result['status'] ?? 500
                ], $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'league' => $league,
                'data' => $result['data'],
                'citations' => $result['grounding_citations'] ?? [],
                'cached' => Cache::has("gemini_fixtures_{$league}")
            ]);

        } catch (\Exception $e) {
            Log::error('GeminiController::fixtures error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * GET /api/gemini/results?league=La+Liga&date=2025-12-15
     */
    public function results(Request $request)
    {
        try {
            $league = $request->input('league', 'La Liga');
            $date = $request->input('date', now()->format('Y-m-d'));
            $forceRefresh = $request->boolean('refresh', false);

            $result = $this->gemini->getResults($league, $date, $forceRefresh);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'],
                    'status' => $result['status'] ?? 500
                ], $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'league' => $league,
                'date' => $date,
                'data' => $result['data'],
                'citations' => $result['grounding_citations'] ?? [],
                'cached' => Cache::has("gemini_results_{$league}_{$date}")
            ]);

        } catch (\Exception $e) {
            Log::error('GeminiController::results error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * GET /api/gemini/history
     */
    public function history(Request $request)
    {
        $league = $request->input('league');
        $type = $request->input('type'); // fixtures | results
        $limit = $request->input('limit', 10);

        $query = GroundingResult::query();

        if ($league) {
            $query->where('league_name', $league);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $results = $query->latest()->limit($limit)->get();

        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'data' => $results
        ]);
    }

    /**
     * GET /api/gemini/test
     * Endpoint de prueba básica
     */
    public function test()
    {
        try {
            $result = $this->gemini->callGemini('¿Qué es Gemini?', []);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['data'] ?? $result['error'],
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### 5.3 Registrar Rutas

**Archivo:** `routes/api.php`

```php
// Gemini Grounding API endpoints
Route::get('/gemini/test', [GeminiController::class, 'test']);
Route::get('/gemini/fixtures', [GeminiController::class, 'fixtures']);
Route::get('/gemini/results', [GeminiController::class, 'results']);
Route::get('/gemini/history', [GeminiController::class, 'history']);
```

---

## ✅ Fase 6: Pruebas y Optimizaciones

### 6.1 Tests Unitarios

```bash
php artisan make:test GeminiServiceTest --unit
php artisan make:test GeminiControllerTest --feature
```

**Archivo:** `tests/Unit/GeminiServiceTest.php`

```php
<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeminiService();
    }

    /** @test */
    public function it_can_build_fixtures_prompt()
    {
        $prompt = $this->service->buildFixturesPrompt('Premier League');
        $this->assertStringContainsString('fixtures', $prompt);
        $this->assertStringContainsString('Premier League', $prompt);
    }

    /** @test */
    public function it_can_build_results_prompt()
    {
        $prompt = $this->service->buildResultsPrompt('La Liga', '2025-12-15');
        $this->assertStringContainsString('resultados', $prompt);
        $this->assertStringContainsString('La Liga', $prompt);
    }

    /** @test */
    public function test_gemini_api_call_with_api_key()
    {
        if (!env('GEMINI_API_KEY')) {
            $this->markTestSkipped('GEMINI_API_KEY not set');
        }

        // Integración real (requiere API key válida)
        $result = $this->service->getFixtures('Premier League');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
```

### 6.2 Ejecutar Pruebas

```bash
php artisan test
php artisan test --filter=GeminiServiceTest
```

### 6.3 Validación Manual

```bash
# 1. Test endpoint básico
curl http://localhost:8000/api/gemini/test

# 2. Test fixtures con parámetros
curl "http://localhost:8000/api/gemini/fixtures?league=Premier+League"

# 3. Test resultados
curl "http://localhost:8000/api/gemini/results?league=La+Liga&date=2025-12-15"

# 4. Ver historial
curl "http://localhost:8000/api/gemini/history?league=Premier+League"
```

### 6.4 Optimizaciones

1. **Cache Strategy**
   - Fixtures: Cache 24h (próximos 7 días de cambios lentos)
   - Results: Cache 48h (datos históricos no cambian)
   - Flush manual con: `php artisan cache:clear`

2. **Rate Limiting**
   - Max 1 req/min por liga (implementar en middleware)
   - Max 10 leagues/día para evitar cuota excedida

3. **Monitoreo**
   - Ver logs: `tail -f storage/logs/laravel.log`
   - Verificar API usage en Google Cloud Console
   - Alertas si respuesta > 5s

4. **JSON Validation**
   - Usar JSON schema validator antes de guardar
   - Fallback a raw_text si parsing falla

---

## 🔐 Consideraciones de Producción

### Queue Configuration

**Archivo:** `.env`

```env
# Cambiar de sync a database o redis
QUEUE_CONNECTION=database
# O para Redis:
QUEUE_CONNECTION=redis
REDIS_URL=redis://localhost:6379
```

### API Rate Limiting

**Archivo:** `app/Http/Middleware/ThrottleRequests.php` (o crear custom)

```php
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/gemini/fixtures', [GeminiController::class, 'fixtures']);
    Route::get('/gemini/results', [GeminiController::class, 'results']);
});
```

### Error Handling & Monitoring

1. **Logging niveles:**
   - INFO: Llamadas exitosas
   - WARNING: Retries, fallbacks a cache
   - ERROR: Fallos finales, excepciones

2. **Alertas recomendadas:**
   - Si API falla 3x consecutivas → Notificación admin
   - Si response > 10s → Log warning
   - Si cost/day > threshold → Email alert

3. **Fallback Strategy:**
   - Si Gemini falla → Usar RapidAPI football data existente
   - Si grounding falla → Usar última respuesta cacheada
   - Si BD cae → Usar file cache en storage/

### Cost Management

**Estimación Gemini API (2025):**
- Input: $0.075 por 1M tokens
- Output: $0.30 por 1M tokens
- Grounding calls: Similar a generación

**Estrategia de costos:**
- Ejecutar grounding 1x semana (fixtures)
- Ejecutar 1x diaria (resultados ayer)
- Cache agresivo (48h mínimo)
- Limitar 10 ligas máximo
- Estimado: ~$50-100/mes

---

## 📝 Checklist de Implementación

- [ ] Fase 1: Setup inicial (Composer + .env + validación)
- [ ] Fase 2: GeminiService (método `callGemini`, prompts)
- [ ] Fase 3: Jobs (GenerateFixtureGroundingJob + GenerateResultAnalysisJob)
- [ ] Fase 4: BD (migración GroundingResult + modelo + guardado)
- [ ] Fase 5: API (GeminiController + rutas)
- [ ] Fase 6: Pruebas (tests unitarios + integration)
- [ ] Validación manual (curls, logs, Google Console)
- [ ] Producción (Queue connection change, rate limiting, monitoring setup)

---

## 📚 Referencias

- [Google Gemini API Docs](https://ai.google.dev/)
- [Laravel HTTP Client](https://laravel.com/docs/11.x/http-client)
- [Laravel Queue Jobs](https://laravel.com/docs/11.x/queues)
- [Laravel Scheduler](https://laravel.com/docs/11.x/scheduling)
- [Hosseinhezami Laravel Gemini Package](https://github.com/hosseinhezami/laravel-gemini)

---

**Estado:** 📋 Plan completado. Listo para Fase 1: Setup Inicial.

**Próximos pasos:** Ejecutar `composer require hosseinhezami/laravel-gemini` en terminal.
