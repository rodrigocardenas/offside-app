<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckMatchesData extends Command
{
    protected $signature = 'app:check-matches-data {--limit=5}';
    protected $description = 'Verifica si eventos y estadísticas se están guardando en partidos';

    public function handle()
    {
        $limit = $this->option('limit');

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🔍 VERIFICANDO DATOS DE PARTIDOS (últimos {$limit})");
        $this->info("═══════════════════════════════════════════════════════════");

        $matches = FootballMatch::where('status', '!=', 'Not Started')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($matches->isEmpty()) {
            $this->warn("No hay partidos con status diferente a 'Not Started'");
            return;
        }

        foreach ($matches as $match) {
            $this->info("\n📊 Partido: {$match->id} | {$match->home_team} vs {$match->away_team}");
            $this->info("   Fecha: {$match->date} | Liga: {$match->league}");
            $this->info("   Status: {$match->status} | Score: {$match->score}");
            $this->info("   External ID: {$match->external_id}");

            // Verificar eventos
            if ($match->events) {
                $eventosArray = json_decode($match->events, true);
                if (is_array($eventosArray)) {
                    $this->line("   ✅ Eventos: " . count($eventosArray) . " eventos guardados");
                    foreach (array_slice($eventosArray, 0, 3) as $evt) {
                        $this->line("      • {$evt['time']}' {$evt['type']} - {$evt['team']} ({$evt['player']})");
                    }
                    if (count($eventosArray) > 3) {
                        $this->line("      ... y " . (count($eventosArray) - 3) . " más");
                    }
                } else {
                    $this->error("   ❌ Eventos: JSON inválido");
                }
            } else {
                $this->error("   ❌ Eventos: NULL o vacío");
            }

            // Verificar estadísticas
            if ($match->statistics) {
                $statsArray = json_decode($match->statistics, true);
                if (is_array($statsArray)) {
                    $this->line("   ✅ Estadísticas guardadas:");
                    foreach ($statsArray as $key => $value) {
                        $this->line("      • {$key}: {$value}");
                    }
                } else {
                    $this->error("   ❌ Estadísticas: JSON inválido");
                }
            } else {
                $this->error("   ❌ Estadísticas: NULL o vacío");
            }
        }

        // Resumen general
        $this->info("\n═══════════════════════════════════════════════════════════");
        $this->info("📈 RESUMEN GENERAL");
        $this->info("═══════════════════════════════════════════════════════════");

        $withEvents = FootballMatch::whereNotNull('events')
            ->where('events', '!=', '')
            ->where('events', '!=', 'null')
            ->count();

        $withStats = FootballMatch::whereNotNull('statistics')
            ->where('statistics', '!=', '')
            ->where('statistics', '!=', 'null')
            ->count();

        $total = FootballMatch::count();

        $this->line("Total de partidos: {$total}");
        $this->line("Con eventos guardados: {$withEvents} (" . round(($withEvents / $total) * 100, 1) . "%)");
        $this->line("Con estadísticas guardadas: {$withStats} (" . round(($withStats / $total) * 100, 1) . "%)");

        $this->info("\n✅ Verificación completada");
    }
}
