<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TemplateQuestion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateWorldCupQuizGroup extends Command
{
    protected $signature = 'worldcup:create-quiz-group
                            {--admin-id= : User ID to set as group creator/admin (defaults to first user)}
                            {--force : Re-create group and template questions even if they already exist}';

    protected $description = 'Crea el grupo Quiz del Mundial 2026 con 10 preguntas de dificultad progresiva';

    /** Unique slug used to tag WC2026 quiz template questions */
    private const TAG = '[[WC2026_QUIZ]]';

    /** WC Final date — questions expire after this */
    private const AVAILABLE_UNTIL = '2026-07-20 23:59:59';

    /** 10 questions: 4 easy (100pts), 4 intermediate (200pts), 2 hard (300pts) */
    private function questions(): array
    {
        return [
            // ── FÁCIL ────────────────────────────────────────────────────────
            [
                'points' => 100,
                'text' => '¿En qué países se celebrará la Copa del Mundo 2026? ' . self::TAG,
                'options' => [
                    ['text' => 'México, Brasil y Argentina',      'is_correct' => false],
                    ['text' => 'Estados Unidos, México y Canadá', 'is_correct' => true],
                    ['text' => 'Canadá, Argentina y Chile',       'is_correct' => false],
                    ['text' => 'EE.UU., Colombia y México',       'is_correct' => false],
                ],
            ],
            [
                'points' => 100,
                'text' => '¿Cuántos equipos participarán en el Mundial 2026, una cifra récord? ' . self::TAG,
                'options' => [
                    ['text' => '32 equipos', 'is_correct' => false],
                    ['text' => '40 equipos', 'is_correct' => false],
                    ['text' => '48 equipos', 'is_correct' => true],
                    ['text' => '64 equipos', 'is_correct' => false],
                ],
            ],
            [
                'points' => 100,
                'text' => '¿Cuál selección ganó el Mundial de Qatar 2022? ' . self::TAG,
                'options' => [
                    ['text' => 'Francia',   'is_correct' => false],
                    ['text' => 'Brasil',    'is_correct' => false],
                    ['text' => 'Argentina', 'is_correct' => true],
                    ['text' => 'Croacia',   'is_correct' => false],
                ],
            ],
            [
                'points' => 100,
                'text' => '¿Quién es el máximo goleador en la historia de los Mundiales de Fútbol? ' . self::TAG,
                'options' => [
                    ['text' => 'Ronaldo (Brasil)',           'is_correct' => false],
                    ['text' => 'Miroslav Klose — 16 goles', 'is_correct' => true],
                    ['text' => 'Pelé',                      'is_correct' => false],
                    ['text' => 'Gerd Müller',                'is_correct' => false],
                ],
            ],

            // ── INTERMEDIO ───────────────────────────────────────────────────
            [
                'points' => 200,
                'text' => '¿Cuántas veces ha ganado Brasil la Copa del Mundo? ' . self::TAG,
                'options' => [
                    ['text' => '3 veces', 'is_correct' => false],
                    ['text' => '4 veces', 'is_correct' => false],
                    ['text' => '5 veces', 'is_correct' => true],
                    ['text' => '6 veces', 'is_correct' => false],
                ],
            ],
            [
                'points' => 200,
                'text' => '¿En qué estadio se jugará la final del Mundial 2026? ' . self::TAG,
                'options' => [
                    ['text' => 'SoFi Stadium — Los Ángeles',                 'is_correct' => false],
                    ['text' => 'Estadio Azteca — Ciudad de México',           'is_correct' => false],
                    ['text' => 'MetLife Stadium — Nueva York/Nueva Jersey',   'is_correct' => true],
                    ['text' => 'Rogers Centre — Toronto',                     'is_correct' => false],
                ],
            ],
            [
                'points' => 200,
                'text' => '¿Cuántos goles marcó Kylian Mbappé en el Mundial de Qatar 2022? ' . self::TAG,
                'options' => [
                    ['text' => '5 goles', 'is_correct' => false],
                    ['text' => '6 goles', 'is_correct' => false],
                    ['text' => '7 goles', 'is_correct' => false],
                    ['text' => '8 goles', 'is_correct' => true],
                ],
            ],
            [
                'points' => 200,
                'text' => '¿Cuál fue la primera selección africana en alcanzar una semifinal del Mundial? ' . self::TAG,
                'options' => [
                    ['text' => 'Nigeria',                'is_correct' => false],
                    ['text' => 'Senegal',                'is_correct' => false],
                    ['text' => 'Marruecos (Qatar 2022)', 'is_correct' => true],
                    ['text' => 'Camerún',                'is_correct' => false],
                ],
            ],

            // ── DIFÍCIL ──────────────────────────────────────────────────────
            [
                'points' => 300,
                'text' => '¿En qué año se disputó el primer Mundial de Fútbol y qué país fue campeón? ' . self::TAG,
                'options' => [
                    ['text' => '1934 — Italia',    'is_correct' => false],
                    ['text' => '1930 — Uruguay',   'is_correct' => true],
                    ['text' => '1928 — Argentina', 'is_correct' => false],
                    ['text' => '1930 — Brasil',    'is_correct' => false],
                ],
            ],
            [
                'points' => 300,
                'text' => '¿Qué jugador marcó un hat-trick en la final del Mundial 2022 pero terminó en el equipo perdedor? ' . self::TAG,
                'options' => [
                    ['text' => 'Antoine Griezmann', 'is_correct' => false],
                    ['text' => 'Olivier Giroud',    'is_correct' => false],
                    ['text' => 'Kylian Mbappé',     'is_correct' => true],
                    ['text' => 'Ousmane Dembélé',   'is_correct' => false],
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $force = $this->option('force');

        // ── 1. Check existing group ──────────────────────────────────────────
        $existing = Group::where('code', 'WC2026-QUIZ')->first();

        if ($existing && !$force) {
            $this->info("ℹ  El grupo quiz del Mundial ya existe: \"{$existing->name}\" (id: {$existing->id})");
            return Command::SUCCESS;
        }

        if ($existing && $force) {
            $this->warn("--force: eliminando grupo quiz existente id {$existing->id} y sus preguntas...");
            DB::table('question_options')
                ->whereIn('question_id', $existing->questions()->pluck('id'))
                ->delete();
            $existing->questions()->delete();
            $existing->delete();
        }

        // ── 2. Resolve admin user ────────────────────────────────────────────
        $adminId = $this->option('admin-id');
        $admin   = $adminId ? User::find($adminId) : User::orderBy('id')->first();

        if (!$admin) {
            $this->error('❌ No se encontró un usuario para asignar como creador.');
            return Command::FAILURE;
        }

        // ── 3. Create the quiz group ─────────────────────────────────────────
        $group = Group::create([
            'name'       => '🏆 Quiz: Copa del Mundo 2026',
            'code'       => 'WC2026-QUIZ',
            'created_by' => $admin->id,
            'category'   => 'quiz',
        ]);

        $group->users()->syncWithoutDetaching([
            $admin->id => ['is_admin' => true],
        ]);

        $this->info("✅ Grupo creado: \"{$group->name}\" (id: {$group->id})");

        // ── 4. Create template questions + question instances ────────────────
        $created = 0;
        foreach ($this->questions() as $idx => $data) {
            // Template question (reusable across groups)
            $template = TemplateQuestion::firstOrCreate(
                ['text' => $data['text']],
                ['type' => 'quiz', 'options' => $data['options']]
            );

            // Question instance tied to this group
            $question = Question::create([
                'title'               => $data['text'],
                'description'         => 'Quiz • Copa del Mundo 2026',
                'type'                => 'quiz',
                'category'            => 'quiz',
                'points'              => $data['points'],
                'group_id'            => $group->id,
                'template_question_id'=> $template->id,
                'is_featured'         => false,
                'available_until'     => self::AVAILABLE_UNTIL,
            ]);

            foreach ($data['options'] as $opt) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'text'        => $opt['text'],
                    'is_correct'  => $opt['is_correct'],
                ]);
            }

            $label = match(true) {
                $data['points'] === 100 => 'fácil',
                $data['points'] === 200 => 'intermedio',
                default                 => 'difícil',
            };

            $this->line("   Q" . ($idx + 1) . " ({$label}, {$data['points']} pts) — {$template->id}");
            $created++;
        }

        $this->info("✅ {$created} preguntas creadas (4 fácil × 100pts, 4 intermedio × 200pts, 2 difícil × 300pts)");
        $this->line("   Código de acceso: <comment>WC2026-QUIZ</comment>");

        Log::info('Grupo Quiz Copa del Mundo 2026 creado', [
            'group_id'        => $group->id,
            'questions_count' => $created,
            'created_by'      => $admin->id,
        ]);

        return Command::SUCCESS;
    }
}
