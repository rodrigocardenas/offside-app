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
        $apiKey = config('services.football.key')
            ?? config('services.football_data.api_token');

        $apiKey = $apiKey ? trim($apiKey) : null;

        if (!$apiKey) {
            throw new \Exception('FOOTBALL_API_KEY no configurada');
        }

        Log::info('UpdateFootballData using api-sports.io', [
            'apiKey' => substr($apiKey, 0, 10) . '...',
            'length' => strlen($apiKey),
        ]);

        // Mapear competitionCode a leagueId de api-sports.io
        $leagueMap = [
            'PD' => 140,     // La Liga
            'PL' => 39,      // Premier League
            'CL' => 2,       // Champions League
            'SA' => 135,     // Serie A
        ];

        $leagueId = $leagueMap[$competitionCode] ?? 39;

        // Determinar season (2025 para enero 2026)
        $season = now()->month >= 7 ? now()->year : now()->year - 1;

        $this->line("   Liga: {$competitionCode} (ID: {$leagueId})");
        $this->line("   Temporada: {$season}");
        $this->newLine();

        // Obtener fixtures de api-sports.io
        $response = Http::withoutVerifying()
            ->withHeaders(['x-apisports-key' => $apiKey])
            ->get('https://v3.football.api-sports.io/fixtures', [
                'league' => $leagueId,
                'season' => $season,
            ]);

        if ($response->failed()) {
            Log::error('API Response Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $error = $response->json()['errors'] ?? $response->json()['message'] ?? $response->body();
            throw new \Exception("API Error: " . json_encode($error));
        }

        $data = $response->json();
        $matches = $data['response'] ?? [];

        $this->line("🔍 Encontrados " . count($matches) . " partidos");
        $this->newLine();

        $saved = 0;

        foreach ($matches as $match) {
            try {
                // En api-sports.io, los equipos están en teams.home y teams.away
                $homeTeamData = $match['teams']['home'] ?? [];
                $awayTeamData = $match['teams']['away'] ?? [];
                $homeTeamNameFromApi = $homeTeamData['name'] ?? null;
                $awayTeamNameFromApi = $awayTeamData['name'] ?? null;

                // Convertir fixture.date a Carbon
                $date = Carbon::parse($match['fixture']['date'])->utc();

                if (!$homeTeamNameFromApi || !$awayTeamNameFromApi) {
                    $this->line("⚠ Partido sin equipos válidos");
                    continue;
                }

                $homeTeam = $this->resolveTeamFromApi($homeTeamData);
                $awayTeam = $this->resolveTeamFromApi($awayTeamData);

                // Crear o actualizar partido
                $status = $this->normalizeMatchStatus($match['fixture']['status']['short'] ?? 'NS');

                $footballMatch = FootballMatch::updateOrCreate(
                    ['external_id' => $match['fixture']['id']],
                    [
                        'home_team' => $homeTeam->api_name ?? $homeTeam->name,
                        'away_team' => $awayTeam->api_name ?? $awayTeam->name,
                        'date' => $date,
                        'status' => $status,
                        'home_team_score' => $match['goals']['home'] ?? null,
                        'away_team_score' => $match['goals']['away'] ?? null,
                        'matchday' => $match['league']['round'] ? preg_replace('/\D/', '', $match['league']['round']) : null,
                        'league' => $competitionCode,
                    ]
                );

                $homeName = $homeTeam->api_name ?? $homeTeam->name;
                $awayName = $awayTeam->api_name ?? $awayTeam->name;

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

        // Mapear códigos de api-sports.io a estados internos
        $statusMap = [
            'NS'  => 'Not Started',  // Not Started
            'TBD' => 'Not Started',  // To Be Defined
            'PST' => 'Postponed',    // Postponed
            '1H'  => 'In Play',      // First Half
            'HT'  => 'Halftime',     // Halftime
            '2H'  => 'In Play',      // Second Half
            'ET'  => 'Extra Time',   // Extra Time
            'BT'  => 'Penalty',      // Breaktime (before penalties)
            'P'   => 'Penalty',      // Penalty
            'FT'  => 'Finished',     // Finished
            'AET' => 'Finished',     // After Extra Time
            'PEN' => 'Finished',     // Penalty
            'ABD' => 'Abandoned',    // Abandoned
            'AWD' => 'Awarded',      // Awarded
            'WO'  => 'Walkover',     // Walkover
        ];

        $status = strtoupper($apiStatus);
        return $statusMap[$status] ?? $apiStatus;
    }
}
