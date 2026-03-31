<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAvailableLeagues extends Command
{
    protected $signature = 'app:fetch-leagues {--season=2025}';

    protected $description = 'Fetch all available leagues/competitions from api-sports.io';

    public function handle()
    {
        $apiKey = config('services.football.key') ?? config('services.football_data.api_token');

        if (!$apiKey) {
            $this->error('FOOTBALL_API_KEY no configurada');
            return Command::FAILURE;
        }

        $season = $this->option('season');

        $this->info('Obteniendo ligas disponibles de api-sports.io...');
        $this->newLine();

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['x-apisports-key' => $apiKey])
                ->get('https://v3.football.api-sports.io/leagues', [
                    'season' => $season,
                ]);

            if ($response->failed()) {
                $this->error('API Error: ' . $response->body());
                return Command::FAILURE;
            }

            $data = $response->json();
            $leagues = $data['response'] ?? [];

            $this->info("Total de ligas/competiciones encontradas: " . count($leagues));
            $this->newLine();

            $this->table(
                ['ID', 'Nombre', 'País', 'Tipo', 'Logo'],
                array_map(fn($league) => [
                    $league['league']['id'] ?? 'N/A',
                    $league['league']['name'] ?? 'N/A',
                    $league['country']['name'] ?? 'N/A',
                    $league['league']['type'] ?? 'N/A',
                    $league['league']['logo'] ? '✓' : '✗',
                ], array_slice($leagues, 0, 50))
            );

            // Exportar como PHP array
            $this->newLine();
            $this->info('Competencias Internacionales Encontradas:');
            $this->newLine();

            $internationalLeagues = array_filter($leagues, fn($l) => 
                in_array($l['league']['id'] ?? null, [1, 2, 4, 7, 33, 45, 56, 131, 140, 135, 39])
            );

            foreach ($internationalLeagues as $league) {
                $id = $league['league']['id'];
                $name = $league['league']['name'];
                $this->line("'{$name}' => {$id},  // {$league['country']['name']}");
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
