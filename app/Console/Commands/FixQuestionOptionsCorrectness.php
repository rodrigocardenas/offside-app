<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\Log;

class FixQuestionOptionsCorrectness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:fix-options-correctness
                            {--question-id= : ID específico de pregunta a revisar}
                            {--dry-run : Solo mostrar qué se corregiría sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y corrige las opciones correctas en preguntas ya procesadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $questionId = $this->option('question-id');
        $dryRun = $this->option('dry-run');

        $this->info("=== VERIFICACIÓN Y CORRECCIÓN DE OPCIONES CORRECTAS ===");
        $this->info("Modo: " . ($dryRun ? 'DRY RUN (solo mostrar)' : 'EJECUTAR CAMBIOS'));
        $this->info("");

        // Construir la consulta
        $query = Question::whereNotNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->with(['options', 'answers.questionOption']);

        if ($questionId) {
            $query->where('id', $questionId);
        }

        $questions = $query->get();

        if ($questions->isEmpty()) {
            $this->warn("No se encontraron preguntas verificadas para revisar.");
            return;
        }

        $this->info("Preguntas encontradas: {$questions->count()}");
        $this->info("");

        $totalFixed = 0;
        $totalQuestions = 0;

        foreach ($questions as $question) {
            $totalQuestions++;
            $this->info("--- Pregunta ID: {$question->id} ---");
            $this->info("Título: {$question->title}");
            $this->info("Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
            $this->info("Score: {$question->football_match->score}");

            // Verificar si hay respuestas correctas
            $correctAnswers = $question->answers()->where('is_correct', true)->get();
            $this->info("Respuestas correctas encontradas: {$correctAnswers->count()}");

            if ($correctAnswers->isEmpty()) {
                $this->warn("  ⚠️ No hay respuestas correctas marcadas");
                continue;
            }

            // Obtener las opciones que deberían estar marcadas como correctas
            $correctOptionIds = $correctAnswers->pluck('option_id')->unique()->toArray();
            $this->info("Opciones que deberían ser correctas: " . implode(', ', $correctOptionIds));

            // Verificar las opciones actuales
            $currentCorrectOptions = $question->options()->where('is_correct', true)->get();
            $this->info("Opciones actualmente marcadas como correctas: " . $currentCorrectOptions->pluck('id')->implode(', '));

            // Encontrar discrepancias
            $optionsToFix = [];
            $currentCorrectIds = $currentCorrectOptions->pluck('id')->toArray();

            // Opciones que deberían ser correctas pero no lo están
            foreach ($correctOptionIds as $optionId) {
                if (!in_array($optionId, $currentCorrectIds)) {
                    $option = $question->options()->find($optionId);
                    if ($option) {
                        $optionsToFix[] = [
                            'id' => $optionId,
                            'text' => $option->text,
                            'action' => 'mark_correct'
                        ];
                    }
                }
            }

            // Opciones que están marcadas como correctas pero no deberían
            foreach ($currentCorrectIds as $optionId) {
                if (!in_array($optionId, $correctOptionIds)) {
                    $option = $question->options()->find($optionId);
                    if ($option) {
                        $optionsToFix[] = [
                            'id' => $optionId,
                            'text' => $option->text,
                            'action' => 'mark_incorrect'
                        ];
                    }
                }
            }

            if (empty($optionsToFix)) {
                $this->info("  ✅ No se encontraron discrepancias");
            } else {
                $this->warn("  🔧 Se encontraron discrepancias:");
                foreach ($optionsToFix as $fix) {
                    $action = $fix['action'] === 'mark_correct' ? '✅ Marcar como correcta' : '❌ Marcar como incorrecta';
                    $this->warn("    - ID {$fix['id']}: '{$fix['text']}' -> {$action}");
                }

                if (!$dryRun) {
                    // Aplicar las correcciones
                    foreach ($optionsToFix as $fix) {
                        $option = QuestionOption::find($fix['id']);
                        if ($option) {
                            $option->is_correct = ($fix['action'] === 'mark_correct');
                            $option->save();
                            $this->info("    ✅ Corregido: '{$option->text}' -> " . ($option->is_correct ? 'Correcta' : 'Incorrecta'));
                        }
                    }
                    $totalFixed += count($optionsToFix);
                }
            }

            $this->info("");
        }

        $this->info("=== RESUMEN ===");
        $this->info("Preguntas revisadas: {$totalQuestions}");
        if (!$dryRun) {
            $this->info("Opciones corregidas: {$totalFixed}");
        } else {
            $this->info("Opciones que se corregirían: {$totalFixed}");
        }
    }
}
