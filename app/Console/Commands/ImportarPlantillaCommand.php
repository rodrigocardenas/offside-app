<?php

namespace App\Console\Commands;

use Goutte\Client;
use Illuminate\Console\Command;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use App\Models\Team;
use App\Models\Player;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ImportarPlantillaCommand extends Command
{
    protected $signature = 'plantilla:import {equipoUrl?} {--all} {--team-id=}';
    protected $description = 'Importa plantilla de jugadores desde Transfermarkt';
    
    protected $client;

    public function handle()
    {
        $this->client = new Client(SymfonyHttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 30,
        ]));

        if ($this->option('all')) {
            return $this->importAllTeams();
        }
        
        $url = $this->argument('equipoUrl');
        $teamId = $this->option('team-id');
        
        if ($teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                $this->error('No se encontró el equipo con ID: ' . $teamId);
                return 1;
            }
            $url = $team->url;
        }
        
        if (!$url) {
            $this->error('Debe proporcionar una URL de equipo o usar la opción --all');
            return 1;
        }
        
        // Extraer el ID del equipo de la URL
        $externalTeamId = $this->extractTeamId($url);
        
        if (!$externalTeamId) {
            $this->error('URL de equipo no válida. Debe ser una URL de Transfermarkt con el formato: https://www.transfermarkt.es/equipo/startseite/verein/ID');
            return 1;
        }

        // Buscar o crear el equipo
        $team = Team::where('external_id', $externalTeamId)->first();
        
        if (!$team) {
            $teamName = $this->ask('Equipo no encontrado. Por favor ingrese el nombre del equipo:');
            $team = Team::create([
                'name' => $teamName,
                'external_id' => $externalTeamId,
                'short_name' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $teamName), 0, 3)),
            ]);
            $this->info("Nuevo equipo creado: {$team->name}");
        }

        return $this->importPlayers($team, $url);
    }
    
    protected function importAllTeams()
    {
        $teams = Team::all();
        $totalTeams = $teams->count();
        $this->info("Iniciando importación de jugadores para $totalTeams equipos...");
        
        $bar = $this->output->createProgressBar($totalTeams);
        $bar->start();
        
        $importedPlayers = 0;
        
        foreach ($teams as $team) {
            try {
                if (empty($team->external_id)) {
                    $this->warn("El equipo {$team->name} no tiene un external_id. Saltando...");
                    $bar->advance();
                    continue;
                }
                
                try {
                    // Construir la URL de Transfermarkt usando el external_id
                    $teamUrl = "https://www.transfermarkt.es/-/kader/verein/{$team->external_id}";
                    
                    $this->info("\nImportando jugadores para el equipo: {$team->name} (URL: $teamUrl)");
                    
                    $imported = $this->importPlayers($team, $teamUrl);
                    if ($imported > 0) {
                        $importedPlayers += $imported;
                        $this->info("✅ Se importaron $imported jugadores para {$team->name}");
                    } else {
                        $this->warn("⚠️  No se importaron jugadores para {$team->name}");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error al procesar {$team->name}: " . $e->getMessage());
                }
                
                $bar->advance();
                // Pequeña pausa para no saturar el servidor
                sleep(2);
            } catch (\Exception $e) {
                $this->error("Error importando jugadores de {$team->name}: " . $e->getMessage());
                $bar->advance();
                continue;
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("¡Importación completada! Se importaron $importedPlayers jugadores de $totalTeams equipos.");
        
        return 0;
    }
    
    protected function importPlayers($team, $teamUrl)
    {
        try {
            if (!is_string($teamUrl) || empty($teamUrl)) {
                throw new \Exception('URL del equipo no válida');
            }
            
            $this->info("Obteniendo datos de: $teamUrl");
            
            try {
                $crawler = $this->client->request('GET', $teamUrl);
                
                // Verificar si la página se cargó correctamente
                $statusCode = $this->client->getResponse()->getStatusCode();
                if ($statusCode !== 200) {
                    throw new \Exception("Error HTTP $statusCode al cargar la página del equipo");
                }
            } catch (\Exception $e) {
                throw new \Exception("No se pudo cargar la página del equipo: " . $e->getMessage());
            }
            
            // Verificar si estamos en la página correcta
            try {
                $teamNameNode = $crawler->filter('h1.data-header__headline-wrapper')->first();
                if ($teamNameNode->count() === 0) {
                    throw new \Exception('No se pudo encontrar el encabezado del equipo');
                }
                $teamName = $teamNameNode->text();
                $this->info("Equipo verificado: " . trim($teamName));
            } catch (\Exception $e) {
                $this->warn('No se pudo verificar el nombre del equipo: ' . $e->getMessage());
                $teamName = $team->name; // Usar el nombre del equipo de la base de datos
            }
            
            // Intentar con el selector principal de jugadores
            $playerRows = $crawler->filter('table.items tbody tr:not([class^="items"])');
            
            // Si no encontramos filas, intentar con otro selector
            if ($playerRows->count() === 0) {
                $playerRows = $crawler->filter('table.items tbody tr');
            }
            
            if ($playerRows->count() === 0) {
                $this->warn('No se encontraron jugadores en la página. Verifica la estructura HTML.');
                return 0;
            }
            
            $this->info("Procesando " . $playerRows->count() . " filas de jugadores...");
            
            $bar = $this->output->createProgressBar($playerRows->count());
            $bar->start();
            
            $players = [];
            $importedCount = 0;
            
            // Procesar cada fila
            for ($i = 0; $i < $playerRows->count(); $i++) {
                $row = $playerRows->eq($i);
                
                if ($row->count() === 0) {
                    $this->warn('Fila vacía encontrada');
                    $bar->advance();
                    continue;
                }
                
                // Intentar extraer el nombre del jugador
                $nameNode = null;
                $selectors = ['.hauptlink a', 'td.hauptlink a', 'a.spielprofil_tooltip'];
                
                foreach ($selectors as $selector) {
                    $nameNode = $row->filter($selector);
                    if ($nameNode->count() > 0) {
                        break;
                    }
                }
                
                if (!$nameNode || $nameNode->count() === 0) {
                    $this->warn('No se pudo encontrar el nombre del jugador en la fila ' . ($i + 1));
                    $bar->advance();
                    continue;
                }
                
                $playerUrl = $nameNode->attr('href');
                $externalId = $this->extractPlayerId($playerUrl);
                $name = trim($nameNode->text());
                
                // Extraer posición
                $position = 'Sin posición';
                try {
                    $positionNode = $row->filter('td:nth-child(2)');
                    if ($positionNode->count() > 0) {
                        $position = trim($positionNode->text());
                    }
                } catch (\Exception $e) {
                    $this->warn('Error al extraer la posición: ' . $e->getMessage());
                }
                
                // Extraer fecha de nacimiento
                $birthDate = null;
                try {
                    $birthNode = $row->filter('td.zentriert');
                    if ($birthNode->count() > 1) {
                        $birthText = $birthNode->eq(1)->text();
                        if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $birthText, $matches)) {
                            $birthDate = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn('Error al extraer la fecha de nacimiento: ' . $e->getMessage());
                }
                
                // Extraer nacionalidad
                $nationality = 'Desconocida';
                try {
                    $flagNode = $row->filter('img.flaggenrahmen, img.flagge, img.flag');
                    if ($flagNode->count() > 0) {
                        $nationality = $flagNode->attr('title') ?? 'Desconocida';
                    }
                } catch (\Exception $e) {
                    $this->warn('Error al extraer la nacionalidad: ' . $e->getMessage());
                }
                
                // Extraer número de camiseta
                $shirtNumber = null;
                try {
                    $numberNode = $row->filter('div.rn_nummer, td.posrela div, div.dataDress, div.shirt-number');
                    if ($numberNode->count() > 0) {
                        $shirtNumberText = trim($numberNode->text());
                        if (is_numeric($shirtNumberText)) {
                            $shirtNumber = (int)$shirtNumberText;
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn('Error al extraer el número de camiseta: ' . $e->getMessage());
                }
                
                // Extraer valor de mercado
                $marketValue = null;
                $valueNode = $row->filter('td.rechts.hauptlink a');
                if ($valueNode->count() > 0) {
                    $valueText = $valueNode->text();
                    if (preg_match('/([\d,]+)/', $valueText, $matches)) {
                        $marketValue = (float) str_replace([',', '.'], ['', '.'], $matches[1]);
                    }
                }
                
                $playerData = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'first_name' => '',
                    'last_name' => $name,
                    'position' => trim($position),
                    'date_of_birth' => $birthDate,
                    'nationality' => $nationality,
                    'shirt_number' => $shirtNumber,
                    'market_value' => $marketValue,
                ];
                
                try {
                    $player = $this->createOrUpdatePlayer($team, $playerData);
                    if ($player) {
                        $importedCount++;
                        $this->info("\n✅ Jugador importado: {$playerData['name']} ({$playerData['position']})");
                    }
                } catch (\Exception $e) {
                    $this->error("\n❌ Error al importar jugador: " . $e->getMessage());
                }
                
                $bar->advance();
                
                // Pequeña pausa entre jugadores
                usleep(500000); // 0.5 segundos
            }
            
            $bar->finish();
            $this->info("\n✅ Importación completada para el equipo: " . $team->name . " ($importedCount jugadores)");
            
            return $importedCount;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
    
    protected function createOrUpdatePlayer($team, $playerData)
    {
        return Player::updateOrCreate(
            [
                'external_id' => $playerData['external_id'],
                'team_id' => $team->id,
            ],
            [
                'name' => $playerData['name'],
                'first_name' => $playerData['first_name'],
                'last_name' => $playerData['last_name'],
                'position' => $playerData['position'],
                'date_of_birth' => $playerData['date_of_birth'],
                'nationality' => $playerData['nationality'],
                'shirt_number' => $playerData['shirt_number'],
                'market_value' => $playerData['market_value'],
            ]
        );
    }
    
    /**
     * Mapea la posición del jugador a un formato estandarizado
     */
    protected function mapPosition($position)
    {
        $position = strtolower($position);
        
        $mapping = [
            'portero' => 'GK',
            'defensa' => 'DF',
            'defensa central' => 'CB',
            'lateral izquierdo' => 'LB',
            'lateral derecho' => 'RB',
            'carrilero' => 'WB',
            'centrocampista' => 'MF',
            'centrocampista defensivo' => 'DM',
            'centrocampista ofensivo' => 'AM',
            'extremo izquierdo' => 'LW',
            'extremo derecho' => 'RW',
            'delantero centro' => 'ST',
        ];
        
        return $mapping[$position] ?? $position;
    }
    
    protected function extractPlayerId($url)
    {
        if (preg_match('/spieler\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
