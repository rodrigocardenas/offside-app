<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class DebugEvents extends Command
{
    protected $signature = 'app:debug-events {id}';
    protected $description = 'Ver el formato exacto de eventos guardados';

    public function handle()
    {
        $id = $this->argument('id');
        $match = FootballMatch::find($id);

        if (!$match) {
            $this->error("Partido no encontrado");
            return;
        }

        $this->info("Partido: {$match->home_team} vs {$match->away_team}");
        $this->info("Status: {$match->status} | Score: {$match->score}");

        $this->info("\n━━━ RAW EVENTS (sin decodificar) ━━━");
        $this->line($match->events);

        $this->info("\n━━━ DECODIFICADO ━━━");
        if ($match->events) {
            $decoded = json_decode($match->events, true);
            foreach ($decoded as $idx => $evt) {
                $this->line("\nEvento $idx:");
                foreach ($evt as $key => $value) {
                    $this->line("  [$key] => $value");
                }
            }
        }

        $this->info("\n━━━ STATISTICS (sin decodificar) ━━━");
        $this->line($match->statistics);

        $this->info("\n━━━ STATISTICS DECODIFICADO ━━━");
        if ($match->statistics) {
            $decoded = json_decode($match->statistics, true);
            foreach ($decoded as $key => $value) {
                if (is_array($value)) {
                    $this->line("  [{$key}]:");
                    foreach ($value as $subkey => $subvalue) {
                        if (is_array($subvalue)) {
                            $this->line("    [{$subkey}]: (nested array)");
                        } else {
                            $this->line("      [{$subkey}] => $subvalue");
                        }
                    }
                } else {
                    $this->line("  [{$key}] => $value");
                }
            }
        }
    }
}
