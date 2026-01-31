<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;
use Illuminate\Support\Facades\Log;

class DiagnoseMatchesUpdate extends Command
{
    protected $signature = 'app:diagnose-matches-update {--limit=5 : Number of matches to diagnose}';
    protected $description = 'Diagnose why matches are not being updated correctly';

    public function handle()
    {
        $this->info("\n╔════════════════════════════════════════════════════════════╗");
        $this->info("║ DIAGNÓSTICO: Actualización de Partidos                     ║");
        $this->info("╚════════════════════════════════════════════════════════════╝\n");

        $limit = (int) $this->option('limit');

        // Obtener partidos sin external_id
        $this->line("\n📋 REVISANDO PARTIDOS SIN EXTERNAL_ID:");
        $this->line(str_repeat('-', 80));

        $withoutExternalId = FootballMatch::whereNull('external_id')
            ->limit($limit)
            ->get();

        if ($withoutExternalId->isNotEmpty()) {
            $this->error("❌ {$withoutExternalId->count()} partidos SIN external_id:");
            foreach ($withoutExternalId as $match) {
                $this->line("   • ID: {$match->id} | {$match->home_team} vs {$match->away_team} | Fecha: {$match->date}");
            }
        } else {
            $this->info("✅ Todos los partidos tienen external_id");
        }

        // Obtener partidos con external_id
        $this->line("\n📋 ANALIZANDO PARTIDOS CON EXTERNAL_ID:");
        $this->line(str_repeat('-', 80));

        $withExternalId = FootballMatch::whereNotNull('external_id')
            ->where('status', '!=', 'Match Finished')
            ->limit($limit)
            ->get();

        if ($withExternalId->isEmpty()) {
            $this->warn("ℹ️  No hay partidos sin terminar con external_id para diagnosticar");

            // Buscar partidos terminados para diagnosticar
            $this->line("\n📋 USANDO PARTIDOS TERMINADOS PARA DIAGNÓSTICO:");
            $withExternalId = FootballMatch::whereNotNull('external_id')
                ->where('status', 'Match Finished')
                ->limit($limit)
                ->get();
        }

        if ($withExternalId->isEmpty()) {
            $this->warn("No se encontraron partidos para diagnosticar");
            return;
        }

        $footballService = app(FootballService::class);
        $results = [];

        foreach ($withExternalId as $match) {
            $this->line("\n🔍 Analizando Match ID: {$match->id}");
            $this->line("   Datos: {$match->home_team} vs {$match->away_team}");
            $this->line("   Fecha: {$match->date}");
            $this->line("   Liga: {$match->league}");
            $this->line("   Status: {$match->status}");
            $this->line("   External ID: {$match->external_id}");
            $this->line("   Score: {$match->score}");

            // Paso 1: Verificar si external_id es numérico (fixture ID válido)
            if (is_numeric($match->external_id)) {
                $this->line("   ✅ External ID es numérico (fixture ID API Football)");

                // Paso 2: Intentar obtener el fixture usando el ID directo
                $this->line("   🔄 Intentando obtener fixture del ID directo...");
                try {
                    $fixture = $footballService->obtenerFixtureDirecto($match->external_id);

                    if ($fixture) {
                        $this->line("   ✅ Fixture obtenido:");
                        $this->line("      Home: {$fixture['teams']['home']['name']} {$fixture['goals']['home']}");
                        $this->line("      Away: {$fixture['teams']['away']['name']} {$fixture['goals']['away']}");
                        $this->line("      Status: {$fixture['fixture']['status']}");

                        // Verificar si coincide con los datos guardados
                        $homeName = $fixture['teams']['home']['name'];
                        $awayName = $fixture['teams']['away']['name'];

                        if ($homeName === $match->home_team && $awayName === $match->away_team) {
                            $this->info("      ✅ Nombres de equipos coinciden exactamente");
                        } else {
                            $this->warn("      ⚠️  Nombres NO coinciden:");
                            $this->line("         BD Home: {$match->home_team} vs API: {$homeName}");
                            $this->line("         BD Away: {$match->away_team} vs API: {$awayName}");
                        }

                        // Verificar scores
                        $apiScore = "{$fixture['goals']['home']} - {$fixture['goals']['away']}";
                        if ($apiScore !== $match->score) {
                            $this->info("      📊 Score en API: {$apiScore}");
                            $this->info("      📊 Score en BD: {$match->score}");
                        }

                        $results[] = [
                            'match_id' => $match->id,
                            'status' => 'SUCCESS',
                            'fixture_found' => true,
                            'fixture_id' => $match->external_id,
                        ];
                    } else {
                        $this->error("   ❌ No se pudo obtener fixture con ID: {$match->external_id}");
                        $results[] = [
                            'match_id' => $match->id,
                            'status' => 'FAILED',
                            'reason' => 'Fixture not found in API',
                        ];
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Error al obtener fixture: {$e->getMessage()}");
                    $results[] = [
                        'match_id' => $match->id,
                        'status' => 'ERROR',
                        'error' => $e->getMessage(),
                    ];
                }
            } else {
                $this->warn("   ⚠️  External ID NO es numérico (formato antiguo o inválido): {$match->external_id}");
                $results[] = [
                    'match_id' => $match->id,
                    'status' => 'INVALID_ID',
                    'reason' => 'External ID is not numeric',
                ];
            }
        }

        // Resumen
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("RESUMEN DIAGNÓSTICO");
        $this->info("═══════════════════════════════════════════════════════════════");

        $successful = collect($results)->where('status', 'SUCCESS')->count();
        $failed = collect($results)->where('status', 'FAILED')->count();
        $errors = collect($results)->where('status', 'ERROR')->count();
        $invalid = collect($results)->where('status', 'INVALID_ID')->count();

        $this->line("Exitosos:    {$successful}");
        $this->line("Fallidos:    {$failed}");
        $this->line("Errores:     {$errors}");
        $this->line("IDs inválidos: {$invalid}");

        if ($invalid > 0) {
            $this->newLine();
            $this->warn("⚠️  Se encontraron external_ids inválidos. Podría ser:");
            $this->line("   1. Partidos creados con API antigua (Football-Data.org)");
            $this->line("   2. External_id no fue sincronizado correctamente");
            $this->line("   3. Cambio de formato no procesado");
        }

        if ($failed > 0) {
            $this->newLine();
            $this->warn("❌ Algunos fixtures no se encontraron en la API.");
            $this->line("   Posibles razones:");
            $this->line("   1. La competición no es válida en API Football");
            $this->line("   2. El fixture fue eliminado de la API");
            $this->line("   3. IDs desincronizados entre BD y API");
        }

        $this->newLine();
    }
}
