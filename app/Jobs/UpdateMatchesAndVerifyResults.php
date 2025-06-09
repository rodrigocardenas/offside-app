<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FootballService;
use App\Services\OpenAIService;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\Features\FeaturedMatchService;
use Illuminate\Support\Facades\Log;

class UpdateMatchesAndVerifyResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(FootballService $footballService, OpenAIService $openAIService, FeaturedMatchService $featuredMatchService): void
    {
        Log::info('Iniciando proceso de actualización de partidos');

        // 1. Obtener los 20 mejores partidos de las 20 principales ligas
        $leagues = [
            'champions-league', // Champions League
            'premier-league', // Premier League
            'la-liga',       // La Liga
            // Mundial de Clubes:
            'world-club-championship', // Mundial de Clubes
            // 'serie-a',       // Serie A
            // 'bundesliga',    // Bundesliga
            // 'ligue-1'        // Ligue 1
        ];

        foreach ($leagues as $league) {
            try {
                Log::info('Obteniendo partidos para la liga ' . $league);
                // dump('Obteniendo partidos para la liga ' . $league);
                $matches = $footballService->getNextMatches($league, 20);
                Log::info('Se obtuvieron ' . count($matches) . ' partidos para la liga ' . $league);

                // Guardar los partidos en la base de datos
                foreach ($matches as $match) {
                    Log::info('Guardando partido: ' . json_encode($match));
                    $match = FootballMatch::updateOrCreate(
                        ['external_id' => $match['local'] . '_' . $match['visitante'] . '_' . $match['fecha']],
                        [
                            'home_team' => $match['local'],
                            'away_team' => $match['visitante'],
                            'date' => $match['fecha'],
                            'status' => $match['estado'],
                            'stadium' => $match['estadio'],
                            'league' => $league
                        ]
                    );
                }

                // Actualizar partidos destacados para esta liga
                $featuredMatchService->updateFeaturedMatches();

            } catch (\Exception $e) {
                Log::error('Error al obtener partidos para la liga ' . $league, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                dump($e);
                continue;
            }
        }
        // 1. Obtener los 20 mejores partidos de las 20 principales ligas
        $leagues = [
            'champions-league', // Champions League
            'premier-league', // Premier League
            'la-liga',       // La Liga
            'world-club-championship',
            // 'serie-a',       // Serie A
            // 'bundesliga',    // Bundesliga
            // 'ligue-1'        // Ligue 1
        ];

        foreach ($leagues as $league) {
            try {
                $matches = $footballService->getNextMatches($league, 20);

                // Guardar los partidos en la base de datos
                foreach ($matches as $match) {
                    FootballMatch::updateOrCreate(
                        ['external_id' => $match['id'] ?? null],
                        [
                            'home_team' => $match['local'],
                            'away_team' => $match['visitante'],
                            'date' => $match['fecha'],
                            'status' => $match['estado'],
                            'stadium' => $match['estadio'],
                            'league' => $league
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error al obtener partidos para la liga ' . $league, [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // 2. Verificar resultados de las preguntas
        $pendingQuestions = Question::whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->where('status', '=', 'FINISHED');
            })
            ->get();

        foreach ($pendingQuestions as $question) {
            try {
                $match = $question->football_match;
                $answers = $question->answers;

                // Verificar resultados usando OpenAI
                $correctAnswers = $openAIService->verifyMatchResults(
                    [
                        'homeTeam' => $match->home_team,
                        'awayTeam' => $match->away_team,
                        'score' => $match->score,
                        'events' => $match->events
                    ],
                    [
                        [
                            'title' => $question->title,
                            'options' => $question->options->pluck('text')->toArray()
                        ]
                    ]
                );

                // Actualizar las respuestas correctas
                foreach ($answers as $answer) {
                    $answer->is_correct = in_array($answer->option_id, $correctAnswers);
                    $answer->points_earned = $answer->is_correct ? 10 : 0;
                    $answer->save();
                }

                // Marcar la pregunta como verificada
                $question->result_verified_at = now();
                $question->save();

            } catch (\Exception $e) {
                Log::error('Error al verificar resultados para la pregunta ' . $question->id, [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
    }
}
