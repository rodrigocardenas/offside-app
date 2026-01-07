<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LaLigaRealFixturesSeeder extends Seeder
{
    /**
     * Run the database seeds - usando Football-Data.org API
     */
    public function run(): void
    {
        echo "🔄 Obteniendo fixtures REALES de Football-Data.org...\n\n";

        $apiKey = '0b23cb843ac746dab2dc3d66604f54e8'; // Tu clave actual
        
        try {
            // Obtener partidos de La Liga para enero 2026
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'X-Auth-Token' => $apiKey
                ])->get('https://api.football-data.org/v4/competitions/PD/matches', [
                    'status' => 'SCHEDULED,LIVE,FINISHED',
                    'dateFrom' => '2026-01-01',
                    'dateTo' => '2026-01-31',
                ]);

            if ($response->failed()) {
                echo "❌ Error de API (HTTP " . $response->status() . "): ";
                $body = $response->json();
                echo $body['message'] ?? $response->body() . "\n";
                return;
            }

            $data = $response->json();
            $matches = $data['matches'] ?? [];

            echo "✅ Obtenidos " . count($matches) . " partidos de Football-Data.org\n\n";

            if (empty($matches)) {
                echo "⚠️  No hay partidos en la API para enero 2026\n";
                return;
            }

            echo "📥 IMPORTANDO PARTIDOS A BASE DE DATOS:\n";
            echo "═══════════════════════════════════════════════════════════════\n\n";

            $created = 0;
            $updated = 0;

            foreach ($matches as $match) {
                try {
                    $home_team = $match['homeTeam']['name'] ?? null;
                    $away_team = $match['awayTeam']['name'] ?? null;
                    $date = Carbon::parse($match['utcDate']);
                    
                    if (!$home_team || !$away_team) {
                        continue;
                    }

                    // Crear o obtener equipos
                    $home = Team::firstOrCreate(
                        ['name' => $home_team],
                        [
                            'external_id' => $match['homeTeam']['id'] ?? md5($home_team),
                            'type' => 'club',
                            'short_name' => substr($home_team, 0, 3),
                            'country' => 'Spain',
                        ]
                    );

                    $away = Team::firstOrCreate(
                        ['name' => $away_team],
                        [
                            'external_id' => $match['awayTeam']['id'] ?? md5($away_team),
                            'type' => 'club',
                            'short_name' => substr($away_team, 0, 3),
                            'country' => 'Spain',
                        ]
                    );

                    // Buscar si ya existe
                    $existing = FootballMatch::where('external_id', $match['id'])
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'home_team_id' => $home->id,
                            'away_team_id' => $away->id,
                            'home_team' => $home_team,
                            'away_team' => $away_team,
                            'date' => $date,
                            'league' => 'La Liga',
                            'matchday' => $match['season']['currentMatchday'] ?? null,
                            'stadium' => $match['venue'] ?? null,
                            'status' => strtolower($match['status']),
                        ]);
                        $updated++;
                    } else {
                        FootballMatch::create([
                            'external_id' => $match['id'],
                            'home_team_id' => $home->id,
                            'away_team_id' => $away->id,
                            'home_team' => $home_team,
                            'away_team' => $away_team,
                            'date' => $date,
                            'league' => 'La Liga',
                            'matchday' => $match['season']['currentMatchday'] ?? null,
                            'stadium' => $match['venue'] ?? null,
                            'status' => strtolower($match['status']),
                        ]);
                        $created++;
                    }

                    echo "✓ " . $home_team . " vs " . $away_team . " (" . $date->format('d/m H:i') . ")\n";

                } catch (\Exception $e) {
                    echo "⚠️  Error procesando partido: " . $e->getMessage() . "\n";
                    continue;
                }
            }

            echo "\n";
            echo "════════════════════════════════════════════════════════════════\n";
            echo "✅ IMPORTACIÓN COMPLETADA:\n";
            echo "════════════════════════════════════════════════════════════════\n";
            echo "Partidos creados:  " . $created . "\n";
            echo "Partidos actualizados: " . $updated . "\n";
            echo "Total procesado:  " . ($created + $updated) . "\n\n";

        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
}
