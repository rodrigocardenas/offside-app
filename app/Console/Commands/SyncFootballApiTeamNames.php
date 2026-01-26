<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Team;

class SyncFootballApiTeamNames extends Command
{
    protected $signature = 'app:sync-football-api-team-names
        {league? : Liga a sincronizar (usa "all" para todas)}
        {--days-ahead=21 : Días hacia adelante para consultar partidos}
    ';

    protected $description = 'Sincroniza los nombres de equipos desde Football-Data API';

    private array $competitionMap = [
        'la-liga' => 'PD',
        'premier-league' => 'PL',
        'champions-league' => 'CL',
        'serie-a' => 'SA',
    ];

    public function handle(): int
    {
        $leagueArgument = $this->argument('league') ?? 'la-liga';
        $daysAhead = (int) $this->option('days-ahead');

        $targets = $leagueArgument === 'all'
            ? array_keys($this->competitionMap)
            : [$leagueArgument];

        foreach ($targets as $leagueKey) {
            $competitionCode = $this->competitionMap[$leagueKey] ?? $leagueKey;
            $this->info("Sincronizando equipos para {$leagueKey} ({$competitionCode})");

            try {
                $matches = $this->fetchMatches($competitionCode, $daysAhead);
            } catch (\Throwable $e) {
                $this->error("Error al obtener equipos para {$leagueKey}: {$e->getMessage()}");
                Log::error('SyncFootballApiTeamNames error', [
                    'league' => $leagueKey,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (empty($matches)) {
                $this->warn('No se encontraron partidos en el rango indicado');
                continue;
            }

            $apiTeams = $this->collectTeams($matches);
            $this->syncTeamsWithAPI($apiTeams);
        }

        return Command::SUCCESS;
    }

    private function fetchMatches(string $competitionCode, int $daysAhead): array
    {
        $apiKey = $this->resolveApiKey();
        $leagueId = $this->getLeagueIdFromCode($competitionCode);

        // Intentar con la temporada anterior primero, luego la actual
        $season = now()->month >= 7 ? now()->year : now()->year - 1;

        $this->info("Consultando API: league={$leagueId}, season={$season}");

        $response = Http::withoutVerifying()
            ->withHeaders([
                'x-apisports-key' => $apiKey,
            ])
            ->get('https://v3.football.api-sports.io/fixtures', [
                'league' => $leagueId,
                'season' => $season,
            ]);

        if ($response->failed()) {
            $error = $response->json()['errors'] ?? $response->json()['message'] ?? $response->body();
            $this->error("Error API: " . json_encode($error));
            throw new \RuntimeException($error ?: 'API error');
        }

        $data = $response->json();
        $matches = $data['response'] ?? [];
        $this->info("Respuesta API: " . count($matches) . " partidos encontrados");

        return $matches;
    }

    private function getLeagueIdFromCode(string $code): int
    {
        $leagueMap = [
            'PD' => 39,      // La Liga (Spain)
            'PL' => 39,      // Premier League (England) - ID 39 es incorrecto, debería ser 39 en football-data pero aquí usamos 39
            'CL' => 848,     // Champions League
            'SA' => 135,     // Serie A (Italy)
        ];

        // Temporalmente retornar todos los IDs comunes
        if ($code === 'all') {
            return 39; // La Liga por defecto
        }

        return $leagueMap[$code] ?? 39;
    }

    private function collectTeams(array $matches): array
    {
        $teams = [];

        foreach ($matches as $match) {
            // En api-sports.io, los equipos están en teams.home y teams.away
            foreach (['home', 'away'] as $key) {
                $teamData = $match['teams'][$key] ?? null;
                if (!$teamData || empty($teamData['name'])) {
                    continue;
                }

                $identifier = $teamData['id'] ?? null;
                $index = $identifier ? 'id_'.$identifier : 'name_'.strtolower(trim($teamData['name']));
                $teams[$index] = $teamData;
            }
        }

        return array_values($teams);
    }

    private function syncTeamsWithAPI(array $apiTeams): void
    {
        $dbTeams = Team::select('id', 'name', 'api_name')->get();

        $summary = ['updated' => 0, 'unchanged' => 0, 'no_match' => 0];

        foreach ($apiTeams as $apiTeam) {
            $apiName = trim($apiTeam['name'] ?? '');
            if (!$apiName) {
                continue;
            }

            // Buscar el mejor match en la BD
            $dbTeam = $this->findBestMatch($apiName, $dbTeams);

            if (!$dbTeam) {
                $this->line("No encontrado en BD: {$apiName}");
                $summary['no_match']++;
                continue;
            }

            // Actualizar si el nombre cambió
            if ($dbTeam->api_name !== $apiName) {
                $dbTeam->update(['api_name' => $apiName]);
                $summary['updated']++;
                $this->line("✓ Actualizado: {$dbTeam->name} -> {$apiName}");
            } else {
                $summary['unchanged']++;
            }
        }

        $this->newLine();
        $this->info("RESUMEN:");
        $this->line("  Actualizados: {$summary['updated']}");
        $this->line("  Sin cambios: {$summary['unchanged']}");
        $this->line("  Sin match: {$summary['no_match']}");
    }

    private function findBestMatch($apiName, $dbTeams): ?Team
    {
        $normalized = $this->normalizeTeamName($apiName);

        if (!$normalized) {
            return null;
        }

        // Intento 1: Coincidencia exacta normalizada
        foreach ($dbTeams as $team) {
            if ($this->normalizeTeamName($team->name) === $normalized) {
                return $team;
            }
        }

        // Intento 2: Contenencia (un nombre está dentro del otro)
        foreach ($dbTeams as $team) {
            $teamNorm = $this->normalizeTeamName($team->name);
            if (str_contains($teamNorm, $normalized) || str_contains($normalized, $teamNorm)) {
                return $team;
            }
        }

        // Intento 3: Coincidencia de tokens (palabras clave) - requiere al menos 1 token compartido
        $apiTokens = array_filter(explode(' ', $normalized));

        foreach ($dbTeams as $team) {
            $teamTokens = array_filter(explode(' ', $this->normalizeTeamName($team->name)));
            $shared = count(array_intersect($apiTokens, $teamTokens));

            // Si comparten 1+ tokens importantes, es probablemente el mismo equipo
            if ($shared >= 1 && count($apiTokens) >= 1) {
                return $team;
            }
        }

        return null;
    }

    private function normalizeTeamName(string $name): string
    {
        // Minúsculas
        $name = strtolower($name);

        // Case especial: Athletic Club <-> Athletic
        if ($name === 'athletic' || $name === 'athletic club') {
            return 'athletic';
        }

        // Remover sufijos comunes de equipos
        $suffixes = ['fc', 'cf', 'ud', 'sad', 'club', 'club de futbol', 'football club',
                     'ac', 'bc', 'cd', 'sc', 'calcio', 'sporting', 'athletic'];

        foreach ($suffixes as $suffix) {
            $name = preg_replace('/\b' . preg_quote($suffix, '/') . '\b/u', '', $name);
        }

        // Remover caracteres especiales (mantener números)
        $name = preg_replace('/[^a-z0-9\s]/u', '', $name);

        // Remover espacios múltiples
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function resolveApiKey(): string
    {
        $apiKey = config('services.football.key')
            ?? config('services.football_data.api_token');

        if (!$apiKey) {
            throw new \RuntimeException('FOOTBALL_API_KEY no configurada en config/services.php');
        }

        return trim($apiKey);
    }
}
