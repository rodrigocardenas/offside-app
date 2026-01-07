<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Competition;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchFixturesWithGemini extends Command
{
    protected $signature = 'gemini:fetch-fixtures {league?} {--force : Forzar actualización sin caché}';

    protected $description = 'Obtener fixtures de una liga usando Gemini con búsqueda en internet (grounding)';

    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    public function handle()
    {
        $league = $this->argument('league') ?? 'La Liga';
        $force = $this->option('force');

        $this->info("📥 Obteniendo fixtures para: {$league}");
        $this->newLine();

        try {
            // Obtener fixtures de Gemini
            $this->info("🔍 Buscando fixtures en internet con Gemini...");
            $fixtures = $this->geminiService->getFixtures($league, $force);

            if (!$fixtures) {
                $this->error("No se obtuvieron datos de Gemini");
                return Command::FAILURE;
            }

            $this->info("✅ Datos obtenidos de Gemini");
            $this->newLine();

            // Procesar y guardar fixtures
            $saved = $this->saveFixtures($fixtures, $league);

            $this->info("✅ Proceso completado");
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Fixtures procesados', count($fixtures['matches'] ?? $fixtures['fixtures'] ?? [])],
                    ['Fixtures guardados', $saved],
                    ['Estado', $saved > 0 ? '✅ Éxito' : '⚠️ Sin cambios'],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error en comando FetchFixturesWithGemini: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Guardar fixtures en la base de datos
     */
    private function saveFixtures($data, $league): int
    {
        $saved = 0;

        // Parsear estructura de datos según lo que retorne Gemini
        $matches = $data['matches'] ?? $data['fixtures'] ?? $data;

        if (!is_array($matches)) {
            $this->warn("Estructura de datos no reconocida");
            return 0;
        }

        // Obtener o crear competición
        $competition = Competition::firstOrCreate(
            ['name' => $league],
            ['name' => $league, 'code' => strtoupper(str_replace(' ', '', $league))]
        );

        $this->info("📋 Procesando " . count($matches) . " partidos...");
        $bar = $this->output->createProgressBar(count($matches));
        $bar->start();

        foreach ($matches as $match) {
            try {
                $bar->advance();

                // Validar datos mínimos
                if (!isset($match['home_team']) || !isset($match['away_team']) || !isset($match['date'])) {
                    continue;
                }

                // Obtener o crear equipos
                $homeTeam = Team::firstOrCreate(
                    ['name' => $match['home_team']],
                    ['name' => $match['home_team']]
                );

                $awayTeam = Team::firstOrCreate(
                    ['name' => $match['away_team']],
                    ['name' => $match['away_team']]
                );

                // Parsear fecha
                $matchDate = $this->parseDate($match['date']);

                // Crear o actualizar fixture
                // Buscar por home_team, away_team y rango de fecha (mismo día aproximadamente)
                $dateRangeStart = $matchDate->copy()->startOfDay();
                $dateRangeEnd = $matchDate->copy()->endOfDay();

                $footballMatch = FootballMatch::whereBetween('date', [$dateRangeStart, $dateRangeEnd])
                    ->where('home_team', $match['home_team'])
                    ->where('away_team', $match['away_team'])
                    ->first();

                if (!$footballMatch) {
                    // Crear nuevo
                    $footballMatch = FootballMatch::create([
                        'home_team' => $match['home_team'],
                        'away_team' => $match['away_team'],
                        'date' => $matchDate,
                        'status' => $match['status'] ?? 'scheduled',
                        'competition_id' => $competition->id,
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'league' => $league,
                        'stadium' => $match['stadium'] ?? null,
                    ]);
                    $saved++;
                } else {
                    // Actualizar existente
                    $footballMatch->update([
                        'status' => $match['status'] ?? 'scheduled',
                        'stadium' => $match['stadium'] ?? null,
                    ]);
                    $saved++;
                }

            } catch (\Exception $e) {
                Log::warning("Error al guardar fixture: " . $e->getMessage(), [
                    'match' => $match,
                ]);
                continue;
            }
        }

        $bar->finish();
        $this->newLine();

        return $saved;
    }

    /**
     * Parsear fecha en diferentes formatos
     */
    private function parseDate($dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        // Si ya es Carbon, retornar
        if ($dateString instanceof Carbon) {
            return $dateString;
        }

        // Intentar parsear diferentes formatos
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'd-m-Y H:i',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($dateString));
            } catch (\Exception $e) {
                continue;
            }
        }

        // Si todo falla, intentar parseo automático
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning("No se pudo parsear fecha: " . $dateString);
            return null;
        }
    }
}

