<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\FootballMatch;
use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TemplateQuestion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SeedWorldCupInitialQuestions extends Command
{
    protected $signature = 'worldcup:seed-initial-questions
                            {--days=2 : Número de días desde el primer partido a pre-crear}
                            {--dry-run : Mostrar lo que se crearía sin guardar nada}';

    protected $description = 'Pre-crea las preguntas del grupo WC para los primeros N días de partidos (para que el grupo no esté vacío)';

    private const TEMPLATE_QUESTION_ID = 44;

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        // Grupo público del Mundial
        $wcGroup = Group::worldCup()->first();
        if (!$wcGroup) {
            $this->error('❌ Grupo del Mundial no encontrado. Ejecuta worldcup:create-group primero.');
            return Command::FAILURE;
        }

        // Template question
        $template = TemplateQuestion::find(self::TEMPLATE_QUESTION_ID);
        if (!$template) {
            $this->error('❌ TemplateQuestion id=' . self::TEMPLATE_QUESTION_ID . ' no encontrada.');
            return Command::FAILURE;
        }

        // Competición WC
        $wcCompetition = Competition::where('type', 'WC')->first();

        // Primer partido del Mundial
        $firstMatch = FootballMatch::when($wcCompetition, fn($q) => $q->where('competition_id', $wcCompetition->id))
            ->when(!$wcCompetition, fn($q) => $q->where('league', 'WC'))
            ->where('status', 'Not Started')
            ->orderBy('date', 'asc')
            ->first();

        if (!$firstMatch) {
            $this->warn('⚠️  No hay partidos WC con status "Not Started".');
            return Command::SUCCESS;
        }

        $startDate = Carbon::parse($firstMatch->date)->utc()->startOfDay();
        $endDate   = $startDate->copy()->addDays($days - 1)->endOfDay();

        $this->info("⚽ Pre-creando preguntas para partidos entre:");
        $this->line("   Desde: {$startDate->toDateString()}");
        $this->line("   Hasta: {$endDate->toDateString()} ({$days} día(s))");
        $this->line("   Grupo: {$wcGroup->name} (id: {$wcGroup->id})");
        $this->newLine();

        // Obtener todos los partidos en ese rango
        $matches = FootballMatch::when($wcCompetition, fn($q) => $q->where('competition_id', $wcCompetition->id))
            ->when(!$wcCompetition, fn($q) => $q->where('league', 'WC'))
            ->where('status', 'Not Started')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        if ($matches->isEmpty()) {
            $this->warn('⚠️  No hay partidos en ese rango.');
            return Command::SUCCESS;
        }

        $created  = 0;
        $skipped  = 0;

        foreach ($matches as $match) {
            $exists = Question::where('match_id', $match->id)
                ->where('group_id', $wcGroup->id)
                ->where('template_question_id', self::TEMPLATE_QUESTION_ID)
                ->exists();

            $label = Carbon::parse($match->date)->utc()->format('d M H:i') . ' UTC';

            if ($exists) {
                $this->line("   <comment>SKIP</comment>  {$match->home_team} vs {$match->away_team} ({$label}) — ya existe");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("   <info>[DRY]</info>  {$match->home_team} vs {$match->away_team} ({$label})");
                $created++;
                continue;
            }

            $question = Question::create([
                'match_id'             => $match->id,
                'group_id'             => $wcGroup->id,
                'template_question_id' => $template->id,
                'type'                 => 'predictive',
                'title'                => str_replace(
                    ['{{home_team}}', '{{away_team}}'],
                    [$match->home_team, $match->away_team],
                    $template->text
                ),
                'competition_id' => $match->competition_id,
                'available_until' => $match->date->utc()->format('Y-m-d H:i:s'),
                'points'         => 300,
                'is_featured'    => true,
            ]);

            $rawOptions = is_string($template->options)
                ? json_decode($template->options, true)
                : (array) $template->options;

            foreach ($rawOptions as $opt) {
                $optText = str_replace(
                    ['{{home_team}}', '{{away_team}}'],
                    [$match->home_team, $match->away_team],
                    $opt['text']
                );
                QuestionOption::firstOrCreate(
                    ['question_id' => $question->id, 'text' => $optText],
                    ['is_correct' => false]
                );
            }

            $this->line("   <info>CREATE</info> {$match->home_team} vs {$match->away_team} ({$label}) → question id: {$question->id}");
            $created++;

            Log::info('[WC] Pregunta pre-creada', [
                'question_id' => $question->id,
                'match'       => "{$match->home_team} vs {$match->away_team}",
                'date'        => $match->date,
            ]);
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("🔍 DRY RUN: se crearían {$created} preguntas ({$skipped} ya existen).");
        } else {
            $this->info("✅ {$created} preguntas creadas, {$skipped} omitidas (ya existían).");
        }

        return Command::SUCCESS;
    }
}
