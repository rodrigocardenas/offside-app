<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckFictionalData extends Command
{
    protected $signature = 'check:fictional-data';
    protected $description = 'Verificar y limpiar datos ficticios generados por Fallback';

    public function handle()
    {
        $this->line("\n=== VERIFICACIÓN DE DATOS FICTICIOS ===\n");

        // Buscar partidos con "Fallback" en el evento
        $fictionalMatches = FootballMatch::where('events', 'LIKE', '%Fallback%')
            ->orWhere('events', 'LIKE', '%random%')
            ->get();

        if ($fictionalMatches->count() === 0) {
            $this->info("✅ No hay datos ficticios encontrados\n");
            return;
        }

        $this->error("❌ Encontrados " . $fictionalMatches->count() . " partidos con datos ficticios:\n");

        foreach ($fictionalMatches as $match) {
            $this->line("┌─ ID: {$match->id}");
            $this->line("├─ Partido: {$match->home_team} vs {$match->away_team}");
            $this->line("├─ Resultado: {$match->score}");
            $this->line("├─ Estado: {$match->status}");
            $this->line("├─ Fecha: {$match->date}");
            $this->line("└─ Evento: {$match->events}\n");
        }

        // Preguntar si limpiar
        if ($this->confirm("\n¿Deseas limpiar estos datos ficticios?")) {
            foreach ($fictionalMatches as $match) {
                $match->update([
                    'status' => 'Not Started',
                    'home_team_score' => null,
                    'away_team_score' => null,
                    'score' => null,
                    'events' => null,
                    'statistics' => json_encode([
                        'source' => 'CLEANED_FICTIONAL_DATA',
                        'cleaned_at' => now()->toIso8601String(),
                        'original_score' => $match->score,
                        'verified' => false
                    ])
                ]);
                $this->info("✓ Limpiado ID {$match->id}");
            }
            $this->info("\n✅ Datos ficticios limpiados correctamente");
        }
    }
}
