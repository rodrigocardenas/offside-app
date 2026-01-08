<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Competition;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateFootballData extends Command
{
    protected $signature = 'app:update-football-data {league?} {--days-ahead=7}';

    protected $description = 'Actualizar fixtures de una liga usando Football-Data.org API';

    public function handle()
    {
        $league = $this->argument('league') ?? 'la-liga';
        $daysAhead = $this->option('days-ahead');

        $this->info("📥 Obteniendo fixtures para: {$league}");
        $this->newLine();

        try {
            $competitionMap = [
                'la-liga' => 'PD',
                'premier-league' => 'PL',
                'champions-league' => 'CL',
                'serie-a' => 'SA'
            ];

            $competitionCode = $competitionMap[$league] ?? $league;
            $saved = $this->updateLeagueFixtures($competitionCode, $daysAhead);

            $this->info("✅ Se guardaron {$saved} partidos exitosamente");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('UpdateFootballData error', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function updateLeagueFixtures($competitionCode, $daysAhead)
    {
        $apiKey = env('FOOTBALL_DATA_API_KEY') 
            ?? config('services.football_data.api_token')
            ?? config('services.football.key');

        if (!$apiKey) {
            throw new \Exception('FOOTBALL_DATA_API_KEY no configurada');
        }

        Log::info('UpdateFootballData Debug', [
            'apiKey' => substr($apiKey, 0, 10) . '...',
            'length' => strlen($apiKey),
            'env_check' => env('FOOTBALL_DATA_API_KEY') ? 'exists' : 'missing',
        ]);

        // Calcular fechas
        $dateFrom = now()->format('Y-m-d');
        $dateTo = now()->addDays($daysAhead)->format('Y-m-d');

        $this->line("   Rango: {$dateFrom} a {$dateTo}");
        $this->line("   API Key: " . substr($apiKey, 0, 5) . "...");
        $this->newLine();

        // Obtener fixtures de Football-Data.org
        $response = Http::withoutVerifying()
            ->withHeaders(['X-Auth-Token' => $apiKey])
            ->get('https://api.football-data.org/v4/competitions/' . $competitionCode . '/matches', [
                'status' => 'SCHEDULED,LIVE,FINISHED',
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);

        if ($response->failed()) {
            Log::error('API Response Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $error = $response->json()['message'] ?? $response->body();
            throw new \Exception("API Error: {$error}");
        }

        $data = $response->json();
        $matches = $data['matches'] ?? [];

        $this->line("🔍 Encontrados " . count($matches) . " partidos");
        $this->newLine();

        $saved = 0;

        foreach ($matches as $match) {
            try {
                $home_team = $match['homeTeam']['name'] ?? null;
                $away_team = $match['awayTeam']['name'] ?? null;
                $date = Carbon::parse($match['utcDate']);

                if (!$home_team || !$away_team) {
                    $this->line("⚠ Partido sin equipos válidos");
                    continue;
                }

                // Crear o actualizar equipos
                $homeTeam = Team::firstOrCreate(
                    ['name' => $home_team],
                    [
                        'external_id' => $match['homeTeam']['id'] ?? md5($home_team),
                        'short_name' => substr($home_team, 0, 3),
                    ]
                );

                $awayTeam = Team::firstOrCreate(
                    ['name' => $away_team],
                    [
                        'external_id' => $match['awayTeam']['id'] ?? md5($away_team),
                        'short_name' => substr($away_team, 0, 3),
                    ]
                );

                // Crear o actualizar partido
                $footballMatch = FootballMatch::updateOrCreate(
                    ['external_id' => $match['id']],
                    [
                        'home_team' => $home_team,
                        'away_team' => $away_team,
                        'date' => $date,
                        'status' => $match['status'] ?? 'TIMED',
                        'home_team_score' => $match['score']['fullTime']['home'] ?? null,
                        'away_team_score' => $match['score']['fullTime']['away'] ?? null,
                        'matchday' => $match['matchday'] ?? null,
                        'league' => $competitionCode,
                    ]
                );

                if ($footballMatch->wasRecentlyCreated) {
                    $saved++;
                    $this->line("✓ NUEVO: {$home_team} vs {$away_team} ({$date->format('d/m H:i')})");
                } else {
                    $this->line("↻ UPDATE: {$home_team} vs {$away_team} ({$date->format('d/m H:i')})");
                }

            } catch (\Exception $e) {
                $this->error("✗ Error: " . $e->getMessage());
                Log::error("Error procesando partido", ['error' => $e->getMessage(), 'match' => $match['id']]);
                continue;
            }
        }

        return $saved;
    }
}
