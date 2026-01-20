<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Competition;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Support\TeamResolver;

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
        $apiKey = config('services.football_data.api_key')
            ?? env('FOOTBALL_DATA_API_KEY')
            ?? env('FOOTBALL_DATA_API_TOKEN')
            ?? config('services.football_data.api_token')
            ?? config('services.football.key');

        $apiKey = $apiKey ? trim($apiKey) : null;

        if (!$apiKey) {
            throw new \Exception('FOOTBALL_DATA_API_KEY no configurada');
        }

        Log::info('UpdateFootballData Debug', [
            'apiKey' => substr($apiKey, 0, 10) . '...',
            'length' => strlen($apiKey),
            'env_check' => env('FOOTBALL_DATA_API_KEY') ? 'exists' : 'missing',
            'config_key' => config('services.football_data.api_key') ? 'exists' : 'missing',
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
                $homeTeamData = $match['homeTeam'] ?? [];
                $awayTeamData = $match['awayTeam'] ?? [];
                $homeTeamNameFromApi = $homeTeamData['name'] ?? null;
                $awayTeamNameFromApi = $awayTeamData['name'] ?? null;
                $date = Carbon::parse($match['utcDate'])->utc();

                if (!$homeTeamNameFromApi || !$awayTeamNameFromApi) {
                    $this->line("⚠ Partido sin equipos válidos");
                    continue;
                }

                $homeTeam = $this->resolveTeamFromApi($homeTeamData);
                $awayTeam = $this->resolveTeamFromApi($awayTeamData);

                // Crear o actualizar partido
                $status = $this->normalizeMatchStatus($match['status'] ?? 'TIMED');

                $footballMatch = FootballMatch::updateOrCreate(
                    ['external_id' => $match['id']],
                    [
                        'home_team' => $homeTeam->name,
                        'away_team' => $awayTeam->name,
                        'date' => $date,
                        'status' => $status,
                        'home_team_score' => $match['score']['fullTime']['home'] ?? null,
                        'away_team_score' => $match['score']['fullTime']['away'] ?? null,
                        'matchday' => $match['matchday'] ?? null,
                        'league' => $competitionCode,
                    ]
                );

                $homeName = $homeTeam->name;
                $awayName = $awayTeam->name;

                if ($footballMatch->wasRecentlyCreated) {
                    $saved++;
                    $this->line("✓ NUEVO: {$homeName} vs {$awayName} ({$date->format('d/m H:i')})");
                } else {
                    $this->line("↻ UPDATE: {$homeName} vs {$awayName} ({$date->format('d/m H:i')})");
                }

            } catch (\Exception $e) {
                $this->error("✗ Error: " . $e->getMessage());
                Log::error("Error procesando partido", ['error' => $e->getMessage(), 'match' => $match['id']]);
                continue;
            }
        }

        return $saved;
    }

    private function resolveTeamFromApi(array $teamData): Team
    {
        $apiName = trim($teamData['name'] ?? '');
        $externalId = $teamData['id'] ?? null;

        if ($apiName === '') {
            throw new \InvalidArgumentException('Nombre de equipo no disponible en la respuesta de la API');
        }

        $team = null;

        if ($externalId) {
            $team = Team::where('external_id', $externalId)->first();
        }

        if (!$team) {
            $team = Team::where(function ($query) use ($apiName) {
                $query->where('api_name', $apiName)
                    ->orWhere('name', $apiName);
            })->first();
        }

        if (!$team) {
            $team = TeamResolver::findByComparableName($apiName);
        }

        if ($team) {
            $updates = [
                'api_name' => $apiName,
            ];

            if ($externalId && !$team->external_id) {
                $updates['external_id'] = $externalId;
            }

            if (!$team->short_name) {
                $updates['short_name'] = TeamResolver::generateShortName($team->name ?? $apiName);
            }

            $team->fill(array_filter($updates, fn ($value) => !is_null($value)));

            if ($team->isDirty()) {
                $team->save();
                TeamResolver::rememberTeam($team);
            }

            return $team;
        }

        $team = Team::create([
            'name' => $apiName,
            'api_name' => $apiName,
            'external_id' => $externalId ?? TeamResolver::fallbackExternalId($apiName),
            'short_name' => TeamResolver::generateShortName($apiName),
        ]);

        TeamResolver::rememberTeam($team);

        return $team;
    }

    private function normalizeMatchStatus(?string $apiStatus): string
    {
        if (!$apiStatus) {
            return 'Not Started';
        }

        $status = strtoupper($apiStatus);

        if (in_array($status, ['TIMED', 'SCHEDULED'], true)) {
            return 'Not Started';
        }

        return $apiStatus;
    }
}
