<?php

namespace App\Console\Commands;

use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

class ScrapeTeamsCommand extends Command
{
    protected $signature = 'scrape:teams {--competition=} {--all}';

    protected $description = 'Scrape team URLs from different competitions';

    protected $competitions = [
        'laliga' => [
            'name' => 'La Liga',
            'url' => 'https://www.transfermarkt.es/laliga/startseite/wettbewerb/ES1',
            'selector' => '.items tbody tr .no-border-links a',
            'is_champions' => false
        ],
        'premier' => [
            'name' => 'Premier League',
            'url' => 'https://www.transfermarkt.es/premier-league/startseite/wettbewerb/GB1',
            'selector' => '.items tbody tr .no-border-links a',
            'is_champions' => false
        ],
        'champions' => [
            'name' => 'Champions League',
            'url' => 'https://www.transfermarkt.es/uefa-champions-league/gesamtspielplan/pokalwettbewerb/CL/saison_id/2024',
            'selector' => '.items tbody tr .no-border-links a',
            'is_champions' => true
        ],
        'club_world_cup' => [
            'name' => 'Mundial de Clubes',
            'url' => 'https://www.transfermarkt.es/fifa-klub-wm/startseite/pokalwettbewerb/KLUB',
            'selector' => '.items tbody tr .no-border-links a',
            'is_champions' => false,
            'competition_id' => 4
        ],
        'liga-colombia' => [
            'name' => 'Liga Colombia',
            'url' => 'https://www.transfermarkt.es/liga-colombia/startseite/wettbewerb/CO1',
            'selector' => '.items tbody tr .no-border-links a',
            'is_champions' => false
        ],
        'chile-campeonato-nacional' => [
            'name' => 'Campeonato Nacional de Chile',
            'url' => 'https://www.transfermarkt.es/chile-campeonato-nacional/startseite/wettbewerb/CL1',
            'selector' => '.items tbody tr .no-border-links a',
            'is_champions' => false
        ]
    ];

    public function handle()
    {
        $client = new Client(SymfonyHttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]));

        $competition = $this->option('competition');
        $all = $this->option('all');

        if ($all) {
            foreach ($this->competitions as $key => $comp) {
                $this->processCompetition($client, $key, $comp);
            }
        } elseif ($competition && isset($this->competitions[$competition])) {
            $this->processCompetition($client, $competition, $this->competitions[$competition]);
        } else {
            $this->error('Please specify a valid competition or use --all');
            $this->line('Available competitions: ' . implode(', ', array_keys($this->competitions)));
            return 1;
        }
    }

    protected function processCompetition($client, $key, $competition)
    {
        $this->info("Processing {$competition['name']}...");

        try {
            $crawler = $client->request('GET', $competition['url']);

            $teams = [];
            $crawler->filter($competition['is_champions'] ? '.items tbody tr' : $competition['selector'])->each(function ($node) use (&$teams, $competition) {
                if ($competition['is_champions'] ?? false) {
                    $link = $node->filter('.no-border-links a');
                    if ($link->count() === 0) return;

                    $href = $link->attr('href');
                    if (!str_contains($href, '/verein/')) return;

                    $teamId = $this->extractTeamId($href);
                    $teams[$teamId] = [
                        'name' => trim($link->text()),
                        'url' => 'https://www.transfermarkt.es' . $href,
                        'external_id' => $teamId,
                    ];
                } else {
                    $href = $node->attr('href');
                    if (str_contains($href, '/verein/')) {
                        $teamId = $this->extractTeamId($href);
                        $teams[$teamId] = [
                            'name' => trim($node->text()),
                            'url' => 'https://www.transfermarkt.es' . $href,
                            'external_id' => $teamId,
                        ];
                    }
                }
            });

            $this->info("Found " . count($teams) . " teams in {$competition['name']}:");

            $this->table(
                ['ID', 'Name', 'URL'],
                array_map(function($team) {
                    return [
                        $team['external_id'],
                        $team['name'],
                        $team['url'],
                    ];
                }, $teams)
            );

            // Save to file
            $filename = storage_path("teams_{$key}_" . now()->format('Y-m-d') . '.json');
            file_put_contents($filename, json_encode(array_values($teams), JSON_PRETTY_PRINT));
            $this->info("Team data saved to: " . $filename);

            // Ask if user wants to import these teams
            if ($this->confirm('Do you want to import these teams to the database?', true)) {
                $this->importTeams($teams, $competition);
            }

        } catch (\Exception $e) {
            $this->error("Error processing {$competition['name']}: " . $e->getMessage());
        }
    }

    protected function extractTeamId($url)
    {
        if (preg_match('/verein\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function importTeams($teams, $competition)
    {
        $teamModel = app(\App\Models\Team::class);
        $imported = 0;

        foreach ($teams as $team) {
            try {
                $createdTeam = $teamModel->updateOrCreate(
                    ['external_id' => $team['external_id']],
                    [
                        'name' => $team['name'],
                        'short_name' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $team['name']), 0, 3)),
                        'country' => 'Spain', // You might want to extract this from the data
                        'type' => 'club'
                    ]
                );

                // Asociar el equipo a la competencia si se especifica
                if (isset($competition['competition_id'])) {
                    $createdTeam->competitions()->syncWithoutDetaching([$competition['competition_id']]);
                }

                $imported++;
            } catch (\Exception $e) {
                $this->error("Error importing team {$team['name']}: " . $e->getMessage());
            }
        }

        $this->info("Successfully imported/updated {$imported} teams.");
    }
}
