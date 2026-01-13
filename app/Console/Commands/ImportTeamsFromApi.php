<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Team;

class ImportTeamsFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-teams-from-api {--league= : Specific league ID to import} {--all : Import from all major leagues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import promoted teams for 2025-2026 season in major European leagues';

    /**
     * Promoted teams for 2025-2026 season
     */
    protected $promotedTeams = [
        'laliga' => [
            [
                'name' => 'Real Valladolid',
                'country' => 'Spain',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/366.png?lm=1405510374'
            ],
            [
                'name' => 'CD Leganés',
                'country' => 'Spain',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/1244.png?lm=1455100489'
            ],
            [
                'name' => 'Racing Santander',
                'country' => 'Spain',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/1509.png?lm=1405510374'
            ],
            // deportivo de la coruña
            [
                'name' => 'Deportivo La Coruña',
                'country' => 'Spain',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/897.png?lm=1457723227'
            ],
        ],
        'premier' => [
            [
                'name' => 'Leicester City',
                'country' => 'England',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/1003.png?lm=1457723227'
            ],
            [
                'name' => 'Ipswich Town',
                'country' => 'England',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/677.png?lm=1457723227'
            ],
            [
                'name' => 'Southampton FC',
                'country' => 'England',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/180.png?lm=1457723227'
            ]
        ],
        'bundesliga' => [
            [
                'name' => 'FC St. Pauli',
                'country' => 'Germany',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/14.png?lm=1396275280'
            ],
            [
                'name' => 'Holstein Kiel',
                'country' => 'Germany',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/269.png?lm=1457723227'
            ],
            [
                'name' => 'Fortuna Düsseldorf',
                'country' => 'Germany',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/38.png?lm=1457723227'
            ],
            // werder bremen:
            [
                'name' => 'Werder Bremen',
                'country' => 'Germany',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/86.png?lm=1457723227'
            ]
        ],
        'seriea' => [
            [
                'name' => 'Parma',
                'country' => 'Italy',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/130.png?lm=1457723227'
            ],
            [
                'name' => 'Como',
                'country' => 'Italy',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/1005.png?lm=1457723227'
            ],
            [
                'name' => 'Venezia',
                'country' => 'Italy',
                'crest_url' => 'https://tmssl.akamaized.net//images/wappen/medium/607.png?lm=1457723227'
            ],

        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificLeague = $this->option('league');
        $allLeagues = $this->option('all');

        if ($specificLeague) {
            if (!isset($this->promotedTeams[$specificLeague])) {
                $this->error("League '{$specificLeague}' not found. Available: " . implode(', ', array_keys($this->promotedTeams)));
                return 1;
            }
            $leaguesToProcess = [$specificLeague => $this->leagues[$specificLeague] ?? ['name' => ucfirst($specificLeague), 'country' => 'Unknown']];
        } elseif ($allLeagues) {
            $leaguesToProcess = [];
            foreach (array_keys($this->promotedTeams) as $key) {
                $leaguesToProcess[$key] = $this->leagues[$key] ?? ['name' => ucfirst($key), 'country' => 'Unknown'];
            }
        } else {
            $this->info('No league specified. Use --league= or --all');
            $this->info('Available leagues: ' . implode(', ', array_keys($this->promotedTeams)));
            return 1;
        }

        $totalImported = 0;

        foreach ($leaguesToProcess as $key => $league) {
            $this->info("Processing {$league['name']}...");
            $imported = $this->importTeamsForLeague($key, $league);
            $totalImported += $imported;
            $this->info("Imported {$imported} teams for {$league['name']}");

            // Add delay between requests to avoid rate limiting
            if (next($leaguesToProcess)) {
                $this->info("Waiting 2 seconds before next league...");
                sleep(2);
            }
        }

        $this->info("Total teams imported: {$totalImported}");
    }

    /**
     * Import teams for a specific league
     */
    protected function importTeamsForLeague(string $leagueKey, array $league): int
    {
        $teamsData = $this->promotedTeams[$leagueKey] ?? [];

        if (empty($teamsData)) {
            $this->warn("No promoted teams defined for {$league['name']}");
            return 0;
        }

        $imported = 0;

        foreach ($teamsData as $teamData) {
            // Check if team already exists
            $existingTeam = Team::where('name', $teamData['name'])->first();

            if ($existingTeam) {
                $this->line("Team '{$teamData['name']}' already exists, skipping...");
                continue;
            }

            // Create new team
            Team::create([
                'name' => $teamData['name'],
                'short_name' => $teamData['name'],
                'tla' => null,
                'external_id' => 'promoted-2025-' . $leagueKey . '-' . uniqid(),
                'country' => $teamData['country'],
                'crest_url' => $teamData['crest_url'],
                'website' => null,
                'founded_year' => null,
                'club_colors' => null,
                'venue' => null,
            ]);

            $imported++;
            $this->line("Imported: {$teamData['name']}");
        }

        return $imported;
    }
}
