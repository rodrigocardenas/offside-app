<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\Competition;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FillTeamsCompetitionId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:fill-competition-id {--season=2025} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Llena la columna competition_id en la tabla teams usando la API de Football';

    /**
     * Mapeo de competition_id local a API Football league ID
     */
    protected array $competitionMap = [
        1 => 140,  // La Liga => La Liga (Spain)
        2 => 39,   // Premier League => Premier League (England)
        3 => 2,    // Champions League => UEFA Champions League
        4 => 135,  // Serie A => Serie A (Italy)
        5 => null, // Amistosos de Selecciones (no aplica API)
    ];

    /**
     * API Key and base URL
     */
    protected ?string $apiKey;
    protected string $apiBaseUrl = 'https://v3.football.api-sports.io';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = config('services.football.key')
            ?? env('FOOTBALL_API_KEY')
            ?? env('APISPORTS_API_KEY')
            ?? env('API_SPORTS_KEY');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->apiKey) {
            $this->error('❌ FOOTBALL_API_KEY no está configurada en .env');
            return Command::FAILURE;
        }

        $this->info('🚀 Iniciando llenado de competition_id en teams...');
        $this->info("API Key configurada: " . substr($this->apiKey, 0, 5) . '...');

        $season = $this->option('season');
        $force = $this->option('force');

        $this->info("\n📅 Temporada: $season");
        $this->info('🔄 Procesando competiciones...\n');

        $totalUpdated = 0;

        foreach ($this->competitionMap as $localId => $apiLeagueId) {
            if ($apiLeagueId === null) {
                $this->line("⏭️  Competición ID $localId: Sin mapeo en API (se salta)");
                continue;
            }

            $competition = Competition::find($localId);
            if (!$competition) {
                $this->warn("⚠️  Competición ID $localId no existe en BD");
                continue;
            }

            $this->line("\n📊 Procesando: {$competition->name} (ID: {$localId}, API League: {$apiLeagueId})");

            // Validar si hay equipos sin competition_id
            $teamsWithoutCompetitionId = Team::whereNull('competition_id')->count();
            if ($teamsWithoutCompetitionId === 0 && !$force) {
                $this->info("✅ No hay equipos sin competition_id para llenar");
                continue;
            }

            // Obtener standings desde API Football
            $standings = $this->getStandingsFromApi($apiLeagueId, $season);
            if (empty($standings)) {
                $this->warn("   ⚠️  No se obtuvieron standings para {$competition->name}");
                continue;
            }

            // Procesar each team en standings
            $updated = $this->processStandings($standings, $localId, $competition->name);
            $totalUpdated += $updated;

            $this->line("   ✅ Actualizados: $updated equipos");
        }

        $this->info("\n" . str_repeat('=', 60));
        $this->info("✨ ¡Proceso completado!");
        $this->info("📈 Total equipos actualizados: $totalUpdated");
        $this->info(str_repeat('=', 60));

        return Command::SUCCESS;
    }

    /**
     * Obtiene los standings desde la API de Football
     */
    protected function getStandingsFromApi(int $leagueId, string $season): array
    {
        try {
            $this->line("   🌐 Consultando API Football...");

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders(['x-apisports-key' => $this->apiKey])
                ->get("{$this->apiBaseUrl}/standings", [
                    'league' => $leagueId,
                    'season' => $season,
                ]);

            if (!$response->successful()) {
                $this->warn("   ❌ Error HTTP {$response->status()}: {$response->body()}");
                return [];
            }

            $data = $response->json('response');
            if (empty($data)) {
                $this->warn("   ⚠️  Sin datos en la respuesta de API");
                return [];
            }

            // Los standings vienen en una estructura anidada
            // response[0]['league']['standings'][0] contiene los grupos/tablas
            $standings = [];
            if (isset($data[0]['league']['standings']) && is_array($data[0]['league']['standings'])) {
                foreach ($data[0]['league']['standings'] as $table) {
                    if (isset($table[0])) {
                        // table[0] es el primer equipo de la tabla
                        foreach ($table as $entry) {
                            if (isset($entry['team']['id'])) {
                                $standings[] = $entry['team'];
                            }
                        }
                    }
                }
            }

            $this->line("   📦 Equipos en standings: " . count($standings));
            return $standings;

        } catch (\Exception $e) {
            $this->error("   ❌ Excepción: {$e->getMessage()}");
            Log::error('Error en getStandingsFromApi', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Procesa los equipos obtenidos de los standings
     */
    protected function processStandings(array $standings, int $competitionId, string $competitionName): int
    {
        $updated = 0;

        foreach ($standings as $apiTeam) {
            if (!isset($apiTeam['id']) || !isset($apiTeam['name'])) {
                continue;
            }

            $externalId = $apiTeam['id'];
            $apiName = $apiTeam['name'];

            // Obtener todos los equipos sin competition_id UNA VEZ por competición
            $candidate_teams = Team::whereNull('competition_id')->get();

            $localTeam = $this->findBestMatch($apiName, $candidate_teams);

            if ($localTeam) {
                $result = Team::where('id', $localTeam->id)
                    ->update(['competition_id' => $competitionId]);

                if ($result) {
                    $updated++;
                    $this->line("   ✓ {$localTeam->name} → {$competitionName}");
                    Log::info("Team competition assigned", [
                        'team_id' => $localTeam->id,
                        'team_name' => $localTeam->name,
                        'api_team_name' => $apiName,
                        'api_team_id' => $externalId,
                        'competition_id' => $competitionId,
                    ]);
                }
            }
        }

        return $updated;
    }

    /**
     * Encuentra el mejor match para un nombre de equipo de la API
     * Nota: Los api_name en la BD están incompletos, usamos name como fuente principal
     */
    protected function findBestMatch(string $apiName, $candidate_teams)
    {
        $normalized_api = $this->normalizeName($apiName);

        // ESTRATEGIA 1: Match exacto en name normalizado (máxima prioridad)
        foreach ($candidate_teams as $team) {
            if ($this->normalizeName($team->name) === $normalized_api) {
                return $team;
            }
        }

        // ESTRATEGIA 2: Match exacto en api_name normalizado
        foreach ($candidate_teams as $team) {
            if ($team->api_name && $this->normalizeName($team->api_name) === $normalized_api) {
                return $team;
            }
        }

        // ESTRATEGIA 3: Fuzzy matching en name (segunda prioridad)
        $best_match = null;
        $best_score = 0;

        foreach ($candidate_teams as $team) {
            $normalized_name = $this->normalizeName($team->name);
            $score = $this->stringSimilarity($normalized_name, $normalized_api);

            if ($score > $best_score && $score > 0.75) {
                $best_score = $score;
                $best_match = $team;
            }
        }

        if ($best_match) {
            return $best_match;
        }

        // ESTRATEGIA 4: Fuzzy matching en api_name (tercera prioridad)
        foreach ($candidate_teams as $team) {
            if ($team->api_name) {
                $normalized_api_name = $this->normalizeName($team->api_name);
                $score = $this->stringSimilarity($normalized_api_name, $normalized_api);

                if ($score > $best_score && $score > 0.75) {
                    $best_score = $score;
                    $best_match = $team;
                }
            }
        }

        return $best_match;
    }

    /**
     * Intenta encontrar un equipo por match aproximado de nombre
     */
    protected function findTeamByFuzzyMatch(?string $apiName): ?Team
    {
        if (!$apiName) {
            return null;
        }

        // Normalizar el nombre
        $normalized = $this->normalizeName($apiName);

        // Buscar en la BD todos los equipos y compararlos
        $teams = Team::all();

        foreach ($teams as $team) {
            $teamNormalized = $this->normalizeName($team->name);

            // Si coinciden exactamente o parcialmente
            if ($this->stringSimilarity($normalized, $teamNormalized) > 0.75) {
                return $team;
            }

            // También comparar con api_name si existe
            if ($team->api_name) {
                $apiNameNormalized = $this->normalizeName($team->api_name);
                if ($apiNameNormalized === $normalized) {
                    return $team;
                }
            }
        }

        return null;
    }

    /**
     * Normaliza un nombre de equipo para comparación
     */
    protected function normalizeName(string $name): string
    {
        // Registrar para debugging
        $original = $name;

        // Remover acentos
        $normalized = $this->removeAccents($name);

        // Convertir a minúsculas
        $normalized = strtolower($normalized);

        // Remover caracteres especiales pero mantener espacios
        $normalized = preg_replace('/[^\w\s]/u', '', $normalized);

        // Remover sufijos comunes de equipos
        $suffixes_to_remove = [
            'fc', 'cf', 'club', 'de futbol', 'de football', 'sad', 'association',
            'balompie', 'afc', 'ac', 'ca', 'cd', 'ud', 'rc', 'as', 'us', 'de', 'los'
        ];
        foreach ($suffixes_to_remove as $suffix) {
            $normalized = preg_replace('/\b' . preg_quote($suffix) . '\b/', '', $normalized);
        }

        // Normalizar espacios (remover extras)
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        return $normalized;
    }

    /**
     * Remueve acentos de una cadena
     */
    protected function removeAccents(string $str): string
    {
        $accents = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ã' => 'a', 'õ' => 'o', 'ñ' => 'n',
            'ç' => 'c', 'Ç' => 'C',
        ];

        return strtr($str, $accents);
    }

    /**
     * Calcula similitud entre dos strings (0-1)
     */
    protected function stringSimilarity(string $str1, string $str2): float
    {
        $len = max(strlen($str1), strlen($str2));
        if ($len === 0) {
            return 1.0;
        }

        return 1 - (levenshtein($str1, $str2) / $len);
    }
}
