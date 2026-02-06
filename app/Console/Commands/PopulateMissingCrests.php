<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PopulateMissingCrests extends Command
{
    protected $signature = 'teams:populate-crests {--limit=50} {--fetch-all}';
    protected $description = 'Link local team crests to teams that are missing crest_url';

    public function handle()
    {
        $limit = $this->option('limit');
        $fetchAll = $this->option('fetch-all');
        
        $query = Team::whereNull('crest_url');
        if (!$fetchAll) {
            $query->limit($limit);
        }
        
        $teams = $query->get();

        $this->info("Procesando " . $teams->count() . " equipos sin logos...\n");

        // Cargar lista de logos disponibles
        $availableLogos = $this->getAvailableLogos();
        $this->info("Logos disponibles: " . count($availableLogos));

        $updated = 0;
        $notFound = 0;

        foreach ($teams as $team) {
            $this->output->write("Buscando logo para: {$team->api_name} ({$team->name})... ");

            // Buscar logo coincidente
            $logoPath = $this->findMatchingLogo($team, $availableLogos);

            if ($logoPath) {
                $team->update(['crest_url' => '/storage/' . $logoPath]);
                $this->info("✓");
                $updated++;
            } else {
                $this->error("✗");
                $notFound++;
            }
        }

        $this->newLine();
        $this->info("=== RESUMEN ===");
        $this->info("Actualizados: {$updated}");
        $this->error("No encontrados: {$notFound}");
        $this->info("Total procesados: " . $teams->count());
    }

    /**
     * Obtener lista de logos disponibles en storage
     */
    private function getAvailableLogos(): array
    {
        try {
            $logos = Storage::disk('public')->listContents('logos', false);
            $available = [];
            
            foreach ($logos as $logo) {
                if ($logo['type'] === 'file') {
                    $available[] = [
                        'path' => $logo['path'],
                        'name' => pathinfo($logo['path'], PATHINFO_FILENAME),
                    ];
                }
            }
            
            return $available;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Buscar un logo coincidente para el equipo
     */
    private function findMatchingLogo(Team $team, array $logos): ?string
    {
        $searchNames = [
            strtolower($team->api_name),
            strtolower(str_replace(' ', '_', $team->api_name)),
            strtolower(str_replace(' ', '', $team->api_name)),
            strtolower(str_replace([' ', '-'], '_', $team->name)),
            strtolower(str_replace([' ', '-'], '', $team->name)),
        ];

        foreach ($logos as $logo) {
            $logoNameLower = strtolower($logo['name']);
            
            // Búsqueda exacta
            if (in_array($logoNameLower, $searchNames)) {
                return $logo['path'];
            }
            
            // Búsqueda parcial - si coinciden palabras importantes
            foreach ($searchNames as $searchName) {
                // Coincidencia parcial si uno contiene al otro y ambos son suficientemente largos
                if (strlen($searchName) > 3 && strlen($logoNameLower) > 3) {
                    if (stripos($logoNameLower, $searchName) !== false || 
                        stripos($searchName, $logoNameLower) !== false) {
                        return $logo['path'];
                    }
                }
            }
        }

        return null;
    }
}
