<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\FootballMatch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportWorldCupMatches extends Command
{
    protected $signature = 'worldcup:import-matches
                            {--stage=GROUP_STAGE : Stage filter (GROUP_STAGE, ROUND_OF_16, etc.)}
                            {--force : Re-import even if match already exists}
                            {--dry-run : Show what would be imported without saving}';

    protected $description = 'Importa los partidos del Mundial 2026 desde football-data.org y los guarda en football_matches';

    /** Football-data.org competition code for World Cup */
    protected const WC_CODE   = 'WC';
    protected const WC_SEASON = 2026;

    /** Status mapping from football-data.org to our system */
    protected const STATUS_MAP = [
        'TIMED'     => 'Not Started',
        'SCHEDULED' => 'Not Started',
        'IN_PLAY'   => 'In Play',
        'PAUSED'    => 'In Play',
        'FINISHED'  => 'Match Finished',
        'POSTPONED' => 'Postponed',
        'CANCELLED' => 'Cancelled',
        'AWARDED'   => 'Match Finished',
    ];

    public function handle(): int
    {
        $stage   = $this->option('stage');
        $force   = $this->option('force');
        $dryRun  = $this->option('dry-run');

        $this->info("╔══════════════════════════════════════════╗");
        $this->info("║  Importar Partidos Mundial 2026          ║");
        $this->info("╚══════════════════════════════════════════╝");
        $this->line("Stage: {$stage}");
        if ($dryRun) {
            $this->warn("⚠  DRY RUN – no se guardarán cambios");
        }

        // 1. Ensure WC competition exists in our DB
        $competition = $this->ensureCompetition($dryRun);

        // 2. Fetch matches from football-data.org
        $this->line("\nConsultando football-data.org...");
        $matches = $this->fetchMatches($stage);

        if (empty($matches)) {
            $this->error("No se encontraron partidos. Verifica el API key o el stage solicitado.");
            return Command::FAILURE;
        }

        $this->info("Partidos encontrados en API: " . count($matches));

        // 3. Import each match
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($matches as $match) {
            $externalId = (string) $match['id'];
            $homeTeam   = $match['homeTeam']['name'] ?? 'TBD';
            $awayTeam   = $match['awayTeam']['name'] ?? 'TBD';
            $utcDate    = $match['utcDate'] ?? null;
            $matchStage = $match['stage'] ?? null;
            $matchGroup = $match['group'] ?? null;
            $matchday   = (string) ($match['matchday'] ?? '');
            $apiStatus  = $match['status'] ?? 'TIMED';
            $status     = self::STATUS_MAP[$apiStatus] ?? 'Not Started';

            if (!$utcDate) {
                $this->line("  ⏭  Partido sin fecha: {$homeTeam} vs {$awayTeam} – ignorado");
                $skipped++;
                continue;
            }

            $date = Carbon::parse($utcDate)->utc();

            $exists = FootballMatch::where('external_id', $externalId)->first();

            if ($exists && !$force) {
                $this->line("  ↩  Ya existe: {$homeTeam} vs {$awayTeam} ({$date->format('Y-m-d')})");
                $skipped++;
                continue;
            }

            $data = [
                'external_id'    => $externalId,
                'home_team'      => $homeTeam,
                'away_team'      => $awayTeam,
                'date'           => $date,
                'status'         => $status,
                'league'         => self::WC_CODE,
                'matchday'       => $matchday,
                'stage'          => $matchStage,
                'group'          => $matchGroup,
                'season'         => self::WC_SEASON,
                'competition_id' => $competition?->id,
                'is_featured'    => true,   // todos los partidos del Mundial son destacados
                'verification_priority' => 1,
            ];

            if (!$dryRun) {
                if ($exists) {
                    $exists->update($data);
                    $updated++;
                } else {
                    FootballMatch::create($data);
                    $created++;
                }
            } else {
                $action = $exists ? 'UPDATE' : 'CREATE';
                $this->line("  [{$action}] {$homeTeam} vs {$awayTeam} – {$date->format('Y-m-d H:i')} UTC – {$matchStage} ({$matchGroup})");
                $created++;
            }
        }

        $this->newLine();
        $this->info("✅ Resultado:");
        $this->line("   Creados : {$created}");
        $this->line("   Actualizados: {$updated}");
        $this->line("   Ignorados: {$skipped}");

        Log::info("ImportWorldCupMatches completado", [
            'stage'   => $stage,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Ensure a Competition record for the World Cup exists and return it.
     */
    protected function ensureCompetition(bool $dryRun): ?Competition
    {
        $competition = Competition::firstOrNew(['type' => self::WC_CODE]);

        if (!$competition->exists) {
            $this->line("Creando competition 'WC' en BD...");
            if (!$dryRun) {
                $competition->fill([
                    'name'    => 'FIFA World Cup 2026',
                    'type'    => self::WC_CODE,
                    'country' => 'World',
                ]);
                $competition->save();
            }
        } else {
            $this->line("Competition WC ya existe (id: {$competition->id})");
        }

        return $dryRun ? null : $competition;
    }

    /**
     * Fetch matches for the World Cup from football-data.org
     */
    protected function fetchMatches(string $stage): array
    {
        $apiKey = config('services.football_data.api_key');

        try {
            $params = ['season' => self::WC_SEASON];
            if ($stage !== 'ALL') {
                $params['stage'] = $stage;
            }

            $response = Http::withoutVerifying()
                ->withHeaders(['X-Auth-Token' => $apiKey])
                ->timeout(30)
                ->get("https://api.football-data.org/v4/competitions/" . self::WC_CODE . "/matches", $params);

            if ($response->status() === 403) {
                $this->error("❌ Error 403 – Acceso denegado. El plan de tu API key puede no incluir el Mundial.");
                $this->line("   Verifica tu plan en https://www.football-data.org/client/tier");
                Log::warning("ImportWorldCupMatches: 403 Forbidden al consultar API", ['apiKey' => substr($apiKey, 0, 6) . '...']);
                return [];
            }

            if (!$response->successful()) {
                $this->error("❌ Error HTTP {$response->status()}: {$response->body()}");
                Log::error("ImportWorldCupMatches: Error HTTP", ['status' => $response->status(), 'body' => $response->body()]);
                return [];
            }

            $data = $response->json();
            $matches = $data['matches'] ?? [];

            Log::info("ImportWorldCupMatches: API respondió con " . count($matches) . " partidos", [
                'stage' => $stage,
                'season' => self::WC_SEASON,
            ]);

            return $matches;
        } catch (\Exception $e) {
            $this->error("❌ Excepción al llamar la API: " . $e->getMessage());
            Log::error("ImportWorldCupMatches: Excepción", ['error' => $e->getMessage()]);
            return [];
        }
    }
}
