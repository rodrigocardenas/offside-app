<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class CleanupAndSyncTeamNames extends Command
{
    protected $signature = 'app:cleanup-sync-team-names
        {--cleanup : Solo limpiar duplicados sin sincronizar}
        {--sync : Solo sincronizar nombres desde API sin limpiar}
    ';

    protected $description = 'Limpiar equipos duplicados y sincronizar api_name desde API v3.football.api-sports.io';

    public function handle(): int
    {
        $cleanup = $this->option('cleanup');
        $sync = $this->option('sync');

        if (!$cleanup && !$sync) {
            // Hacer ambos por defecto
            $cleanup = true;
            $sync = true;
        }

        if ($cleanup) {
            $this->cleanupDuplicates();
        }

        if ($sync) {
            $this->syncApiNames();
        }

        return Command::SUCCESS;
    }

    private function cleanupDuplicates(): void
    {
        $this->info('═════════════════════════════════════════');
        $this->info('🧹 LIMPIANDO EQUIPOS DUPLICADOS');
        $this->info('═════════════════════════════════════════');
        $this->newLine();

        // Obtener todos los equipos
        $teams = Team::all();
        $normalized = [];
        $duplicates = [];

        // Agrupar por nombre normalizado
        foreach ($teams as $team) {
            $key = $this->normalizeTeamName($team->name);
            if (!isset($normalized[$key])) {
                $normalized[$key] = [];
            }
            $normalized[$key][] = $team;
        }

        // Encontrar duplicados
        foreach ($normalized as $key => $group) {
            if (count($group) > 1) {
                $duplicates[$key] = $group;
            }
        }

        if (empty($duplicates)) {
            $this->info('✅ No se encontraron equipos duplicados');
            $this->newLine();
            return;
        }

        $this->warn("⚠ Se encontraron " . count($duplicates) . " grupos de equipos duplicados:");
        $this->newLine();

        $totalMerged = 0;

        foreach ($duplicates as $normalized => $group) {
            $this->line("Grupo: {$normalized}");

            // Mostrar variantes
            foreach ($group as $idx => $team) {
                $this->line("  [{$idx}] ID: {$team->id} | Nombre: {$team->name} | api_name: {$team->api_name}");
            }

            // Usar el primero como canónico (preferir el más corto)
            usort($group, fn($a, $b) => strlen($a->name) - strlen($b->name));
            $canonical = $group[0];

            $this->line("  ✓ Manteniendo: {$canonical->name} (ID: {$canonical->id})");

            // Reasignar referencias de otros registros
            for ($i = 1; $i < count($group); $i++) {
                $duplicate = $group[$i];

                // Actualizar referencias en otras tablas
                DB::table('football_matches')
                    ->where('home_team', $duplicate->name)
                    ->update(['home_team' => $canonical->name]);

                DB::table('football_matches')
                    ->where('away_team', $duplicate->name)
                    ->update(['away_team' => $canonical->name]);

                // Eliminar duplicado
                $duplicate->delete();
                $this->line("  ✗ Eliminado: {$duplicate->name} (ID: {$duplicate->id})");
                $totalMerged++;
            }

            $this->newLine();
        }

        $this->info("═════════════════════════════════════════");
        $this->info("✅ {$totalMerged} equipos duplicados eliminados");
        $this->info("═════════════════════════════════════════");
        $this->newLine();
    }

    private function syncApiNames(): void
    {
        $this->info('═════════════════════════════════════════');
        $this->info('🔄 SINCRONIZANDO api_name DESDE API');
        $this->info('═════════════════════════════════════════');
        $this->newLine();

        $apiKey = config('services.football.key')
            ?? config('services.football_data.api_token');

        if (!$apiKey) {
            $this->error('❌ FOOTBALL_API_KEY no configurada');
            return;
        }

        $season = now()->month >= 7 ? now()->year : now()->year - 1;

        $leagues = [
            'La Liga' => 39,
            'Premier League' => 39,
            'Champions League' => 848,
            'Serie A' => 135,
        ];

        $totalUpdated = 0;

        foreach ($leagues as $leagueName => $leagueId) {
            $this->line("Obteniendo {$leagueName} (ID: {$leagueId})...");

            try {
                $response = Http::withoutVerifying()
                    ->withHeaders(['x-apisports-key' => $apiKey])
                    ->get('https://v3.football.api-sports.io/fixtures', [
                        'league' => $leagueId,
                        'season' => $season,
                    ]);

                if ($response->failed()) {
                    $this->warn("  ⚠ Error obteniendo {$leagueName}");
                    continue;
                }

                $matches = $response->json()['response'] ?? [];
                $apiTeams = [];

                // Extraer equipos únicos
                foreach ($matches as $match) {
                    $homeTeam = $match['teams']['home'] ?? null;
                    $awayTeam = $match['teams']['away'] ?? null;

                    if ($homeTeam && !isset($apiTeams[$homeTeam['name']])) {
                        $apiTeams[$homeTeam['name']] = $homeTeam['name'];
                    }
                    if ($awayTeam && !isset($apiTeams[$awayTeam['name']])) {
                        $apiTeams[$awayTeam['name']] = $awayTeam['name'];
                    }
                }

                // Actualizar equipos
                foreach ($apiTeams as $apiName) {
                    $team = $this->findTeamByName($apiName);

                    if ($team) {
                        if ($team->api_name !== $apiName) {
                            $team->update(['api_name' => $apiName]);
                            $this->line("  ✓ {$team->name} → {$apiName}");
                            $totalUpdated++;
                        }
                    } else {
                        $this->line("  ⚠ No encontrado en BD: {$apiName}");
                    }
                }

                $this->newLine();

            } catch (\Exception $e) {
                $this->warn("  ❌ Error: " . $e->getMessage());
                continue;
            }
        }

        $this->info("═════════════════════════════════════════");
        $this->info("✅ {$totalUpdated} equipos actualizados");
        $this->info("═════════════════════════════════════════");
        $this->newLine();
    }

    private function findTeamByName(string $apiName): ?Team
    {
        $normalized = $this->normalizeTeamName($apiName);

        // Intento 1: Coincidencia exacta normalizada
        foreach (Team::all() as $team) {
            if ($this->normalizeTeamName($team->name) === $normalized) {
                return $team;
            }
        }

        // Intento 2: Búsqueda por nombre similar
        return Team::where('name', 'like', '%' . $apiName . '%')->first()
            ?? Team::where('api_name', 'like', '%' . $apiName . '%')->first();
    }

    private function normalizeTeamName(string $name): string
    {
        $name = strtolower(trim($name));

        // Remover sufijos comunes
        $suffixes = ['fc', 'cf', 'ud', 'sad', 'club', 'ac', 'bc', 'cd', 'sc', 'calcio', 'sporting', 'athletic'];
        foreach ($suffixes as $suffix) {
            $name = preg_replace('/\b' . preg_quote($suffix, '/') . '\b/u', '', $name);
        }

        // Remover caracteres especiales
        $name = preg_replace('/[^a-z0-9\s]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}
