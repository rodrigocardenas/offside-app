<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TemplateQuestion;
use Illuminate\Database\Seeder;

class CreateMWCQuizInstancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea instancias de preguntas reales de tipo 'quiz' en el grupo MWC
     * basadas en las templates de quiz creadas.
     */
    public function run(): void
    {
        // Obtener el grupo MWC
        $group = Group::where('code', 'MWC-2026-QUIZ')->first();

        if (!$group) {
            $this->command->error('❌ Grupo MWC Quiz no encontrado. Por favor ejecuta CreateMWCQuizGroupSeeder primero.');
            return;
        }

        // Obtener todas las preguntas template de tipo 'quiz'
        $templateQuestions = TemplateQuestion::where('type', 'quiz')
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get();

        if ($templateQuestions->isEmpty()) {
            $this->command->error('❌ No hay preguntas template de tipo quiz. Por favor ejecuta CreateMWCQuizQuestionsSeeder primero.');
            return;
        }

        $questionsCreated = 0;

        foreach ($templateQuestions as $template) {
            // Crear pregunta relacionada con el template
            $question = Question::create([
                'title' => $template->text,
                'description' => 'Pregunta del quiz MWC 2026 - Conocimiento de Fútbol',
                'type' => 'quiz',
                'category' => 'quiz',
                'points' => 100, // 100 puntos por respuesta correcta
                'group_id' => $group->id,
                'template_question_id' => $template->id,
                'is_featured' => false,
                'available_until' => now()->addYear(), // Disponible por un año
            ]);

            // Crear opciones a partir del template
            foreach ($template->options as $index => $option) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'text' => $option['text'],
                    'is_correct' => $option['is_correct'] ?? false,
                ]);
            }

            $questionsCreated++;
        }

        $this->command->info("✅ {$questionsCreated} preguntas quiz creadas en el grupo MWC");
    }
}
