<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\FootballMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugMatch2003 extends Command
{
    protected $signature = 'debug:match-2003';
    protected $description = 'Inspecciona los datos de Match 2003 y sus preguntas';

    public function handle()
    {
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("DEBUG: MATCH 2003 - DATOS COMPLETOS");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        // Obtener el match
        $match = FootballMatch::find(2003);
        if (!$match) {
            $this->error("Match 2003 no encontrado");
            return;
        }

        // Mostrar datos del match
        $this->info("📊 DATOS DEL MATCH:");
        $this->line("  ID: {$match->id}");
        $this->line("  Equipos: {$match->home_team} vs {$match->away_team}");
        $this->line("  Resultado: {$match->home_team_score} - {$match->away_team_score}");
        $this->line("  Fecha: {$match->date}");
        $this->line("  Status: {$match->status}");
        
        if ($match->statistics) {
            $stats = is_array($match->statistics) ? $match->statistics : json_decode($match->statistics, true);
            $this->line("\n📈 ESTADÍSTICAS DISPONIBLES:");
            if (isset($stats['possession'])) {
                $this->line("  Posesión:");
                $this->line("    - Home: " . ($stats['possession']['home_percentage'] ?? 'N/A') . "%");
                $this->line("    - Away: " . ($stats['possession']['away_percentage'] ?? 'N/A') . "%");
            }
        }

        // Obtener todas las preguntas para este match
        $questions = Question::where('match_id', 2003)->with('options', 'group')->get();
        
        $this->info("\n\n📝 PREGUNTAS ASOCIADAS AL MATCH ({$questions->count()} total):");
        $this->line(str_repeat("─", 130));

        foreach ($questions as $question) {
            $this->line("\n  ID: {$question->id} | Grupo: {$question->group->id} ({$question->group->name})");
            $this->line("  Pregunta: " . substr($question->title, 0, 100));
            $this->line("  Verificada: " . ($question->result_verified_at ? 'SÍ (' . $question->result_verified_at->format('Y-m-d H:i') . ')' : 'NO'));
            
            $this->line("\n  OPCIONES:");
            foreach ($question->options as $option) {
                $isCorrect = $option->is_correct ? '✅' : '  ';
                $answerCount = DB::table('answers')
                    ->where('question_id', $question->id)
                    ->where('question_option_id', $option->id)
                    ->count();
                $this->line("    {$isCorrect} [{$option->id}] {$option->text} ({$answerCount} respuestas)");
            }
        }

        $this->info("\n" . str_repeat("═", 130));
        $this->info("Debug completado\n");

        // Preguntar si quiere resincronizar
        if ($this->confirm("¿Deseas que sintetizemos estas preguntas con el evaluador?")) {
            $this->call('app:evaluate-match-questions', ['--match-id' => 2003, '--force' => true]);
        }
    }
}
