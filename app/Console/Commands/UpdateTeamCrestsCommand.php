<?php

namespace App\Console\Commands;

use Goutte\Client;
use Illuminate\Console\Command;
use App\Models\Team;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

class UpdateTeamCrestsCommand extends Command
{
    protected $signature = 'teams:update-crests {--competition=} {--all}';

    protected $description = 'Actualiza las URLs de los escudos de los equipos';

    protected $competitions = [
        'laliga' => [
            'name' => 'La Liga',
            'url' => 'https://www.transfermarkt.es/laliga/startseite/wettbewerb/ES1',
            'selector' => '.items tbody tr',
        ],
        'premier' => [
            'name' => 'Premier League',
            'url' => 'https://www.transfermarkt.es/premier-league/startseite/wettbewerb/GB1',
            'selector' => '.items tbody tr',
        ],
        'champions' => [
            'name' => 'Champions League',
            'url' => 'https://www.transfermarkt.es/uefa-champions-league/gesamtspielplan/pokalwettbewerb/CL/saison_id/2024',
            'selector' => '.items tbody tr',
        ]
    ];

    public function handle()
    {
        $client = new Client(SymfonyHttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 30,
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
            $this->error('Por favor especifica una competencia válida o usa --all');
            $this->line('Competencias disponibles: ' . implode(', ', array_keys($this->competitions)));
            return 1;
        }
    }

    protected function processCompetition($client, $key, $competition)
    {
        $this->info("Procesando {$competition['name']}...");

        try {
            $crawler = $client->request('GET', $competition['url']);

            $teams = [];
            $crawler->filter($competition['selector'])->each(function ($node) use (&$teams) {
                try {
                    // Obtener el enlace del equipo
                    $teamLink = $node->filter('.hauptlink a')->first();
                    if ($teamLink->count() === 0) return;

                    $href = $teamLink->attr('href');
                    if (!str_contains($href, '/verein/')) return;

                    $teamId = $this->extractTeamId($href);
                    $teamName = trim($teamLink->text());

                    // Obtener el escudo
                    $crestImg = $node->filter('.tiny_wappen')->first();
                    if ($crestImg->count() === 0) {
                        $this->warn("No se encontró el escudo para {$teamName}");
                        return;
                    }

                    $crestUrl = $crestImg->attr('src');

                    $teams[$teamId] = [
                        'name' => $teamName,
                        'external_id' => $teamId,
                        'crest_url' => $crestUrl,
                    ];

                    $this->info("Encontrado escudo para: {$teamName}");
                } catch (\Exception $e) {
                    $this->error("Error procesando equipo: " . $e->getMessage());
                }
            });

            $this->info("Encontrados " . count($teams) . " equipos en {$competition['name']}");

            // Actualizar los equipos en la base de datos
            $updated = 0;
            foreach ($teams as $team) {
                $dbTeam = Team::where('external_id', $team['external_id'])->first();
                if ($dbTeam) {
                    $dbTeam->crest_url = $team['crest_url'];
                    $dbTeam->save();
                    $updated++;
                    $this->info("Actualizado escudo para {$team['name']}");
                }
            }

            $this->info("Se actualizaron {$updated} equipos en {$competition['name']}");

        } catch (\Exception $e) {
            $this->error("Error procesando {$competition['name']}: " . $e->getMessage());
        }
    }

    protected function extractTeamId($url)
    {
        if (preg_match('/verein\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
