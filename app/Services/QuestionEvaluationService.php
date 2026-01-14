<?php

namespace App\Services;

use App\Models\Question;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de evaluación de respuestas a preguntas de predicción.
 * Reemplaza OpenAI con lógica determinística basada en datos del partido.
 *
 * Tipos soportados:
 * - winner: ¿Quién ganará? (Victoria home, Victoria away, Empate)
 * - first_goal: ¿Quién anotará el primer gol?
 * - cards: Tarjetas (amarillas, rojas)
 * - fouls: Faltas
 * - possession: Posesión de balón
 * - own_goal: Autogoles
 * - penalty_goal: Goles de penal
 * - free_kick_goal: Goles de tiro libre
 * - corner_goal: Goles de córner
 * - last_goal: Último gol del partido
 * - both_score: ¿Ambos equipos anotarán?
 * - exact_score: Score exacto
 * - goals_over_under: Goles over/under
 */
class QuestionEvaluationService
{
    /**
     * Evalúa una pregunta y determina las opciones correctas.
     *
     * @param Question $question Pregunta a evaluar
     * @param FootballMatch $match Partido finalizado con datos
     * @return array IDs de opciones correctas
     */
    public function evaluateQuestion(Question $question, FootballMatch $match): array
    {
        if (!in_array($match->status, ['FINISHED', 'Match Finished'])) {
            Log::warning('Match not finished', [
                'match_id' => $match->id,
                'status' => $match->status
            ]);
            return [];
        }

        // ⚠️ CHECK: Detectar si el partido tiene datos verificados o ficticios/fallback
        $hasVerifiedData = $this->hasVerifiedMatchData($match);
        
        if (!$hasVerifiedData) {
            Log::warning('Match has unverified/fictional data - skipping detailed event verification', [
                'match_id' => $match->id,
                'home_team' => $match->home_team,
                'away_team' => $match->away_team,
                'score' => $match->score,
                'statistics' => $match->statistics
            ]);
        }

        try {
            $questionText = strtolower($question->title);
            $correctOptions = [];

            // Determinar tipo de pregunta y evaluar
            if ($this->isQuestionAbout($questionText, 'resultado|ganador|victoria|gana|ganará')) {
                // ✅ Score-based: Siempre se puede verificar
                $correctOptions = $this->evaluateWinner($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'primer gol|anotará.*primer')) {
                // ❌ Event-based: Solo si hay datos verificados
                $correctOptions = $this->evaluateFirstGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'ultimo gol|anotará.*último')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateLastGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'más.*faltas|faltas')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateFouls($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'tarjetas amarillas|amarillas')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateYellowCards($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'tarjetas rojas|rojas')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateRedCards($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'autogol|auto gol')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateOwnGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'penal|penalty')) {
                // ❌ Event-based
                $correctOptions = $this->evaluatePenaltyGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'tiro libre|free kick')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateFreeKickGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'córner|corner')) {
                // ❌ Event-based
                $correctOptions = $this->evaluateCornerGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'posesión|possession')) {
                // ❌ Event-based
                $correctOptions = $this->evaluatePossession($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'ambos.*anotan|both.*score')) {
                // ✅ Score-based: Siempre se puede verificar
                $correctOptions = $this->evaluateBothScore($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'score.*exacto|exact|marcador')) {
                // ✅ Score-based: Siempre se puede verificar
                $correctOptions = $this->evaluateExactScore($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'goles.*over|goles.*under|total.*goles')) {
                // ✅ Score-based: Siempre se puede verificar
                $correctOptions = $this->evaluateGoalsOverUnder($question, $match);
            } else {
                Log::warning('Unknown question type for evaluation', [
                    'question_id' => $question->id,
                    'question_text' => $question->title
                ]);
            }

            if (empty($correctOptions)) {
                Log::warning('No correct options found - cannot verify with available data', [
                    'question_id' => $question->id,
                    'match_id' => $match->id,
                    'has_verified_data' => $hasVerifiedData
                ]);
            }

            return $correctOptions;
        } catch (\Exception $e) {
            Log::error('Error evaluating question', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Verifica si un partido tiene datos verificados (no ficticios/fallback)
     * 
     * ✅ Datos verificados: API Football o Gemini con grounding
     * ❌ Datos ficticios: Fallback, random, sin verificación
     */
    private function hasVerifiedMatchData(FootballMatch $match): bool
    {
        // Verificar statistics JSON
        $statistics = is_string($match->statistics) 
            ? json_decode($match->statistics, true) 
            : $match->statistics;

        if (!is_array($statistics)) {
            return false;
        }

        // Si tiene source "Fallback" o "random" o "Simulated" → NO verificado
        $source = $statistics['source'] ?? '';
        if (stripos($source, 'fallback') !== false || 
            stripos($source, 'random') !== false || 
            stripos($source, 'simulated') !== false) {
            return false;
        }

        // Si tiene "verified" = false → NO verificado
        if (isset($statistics['verified']) && $statistics['verified'] === false) {
            return false;
        }

        // Si el source es API Football o Gemini → VERIFICADO
        if (stripos($source, 'api football') !== false || 
            stripos($source, 'gemini') !== false) {
            return true;
        }

        // Por defecto, si no hay información: NO es verificado
        return false;
    }

    /**
     * Verifica si una pregunta contiene ciertas palabras clave
     */
    private function isQuestionAbout(string $text, string $keywords): bool
    {
        $patterns = explode('|', $keywords);
        foreach ($patterns as $pattern) {
            if (strpos($text, strtolower(trim($pattern))) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * TIPO: RESULTADO (Victoria home, Victoria away, Empate)
     */
    private function evaluateWinner(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $homeScore = $match->home_team_score ?? 0;
        $awayScore = $match->away_team_score ?? 0;

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            // Victoria home
            if (($homeScore > $awayScore && strpos($optionText, strtolower($match->home_team)) !== false) ||
                (strpos($optionText, 'victoria') !== false && strpos($optionText, strtolower($match->home_team)) !== false)) {
                if ($homeScore > $awayScore) {
                    $correctOptionIds[] = $option->id;
                }
            }
            // Victoria away
            elseif (($awayScore > $homeScore && strpos($optionText, strtolower($match->away_team)) !== false) ||
                    (strpos($optionText, 'victoria') !== false && strpos($optionText, strtolower($match->away_team)) !== false)) {
                if ($awayScore > $homeScore) {
                    $correctOptionIds[] = $option->id;
                }
            }
            // Empate
            elseif ($homeScore === $awayScore && strpos($optionText, 'empate') !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: PRIMER GOL
     */
    private function evaluateFirstGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        // Encontrar primer gol
        $firstGoalTeam = null;
        foreach ($events as $event) {
            if ($event['type'] === 'GOAL' && $event['team'] !== 'substitution') {
                $firstGoalTeam = $event['team'];
                break;
            }
        }

        if (!$firstGoalTeam) {
            // No hubo goles - buscar "Ninguno"
            foreach ($question->options as $option) {
                if (strpos(strtolower($option->text), 'ninguno') !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
            return $correctOptionIds;
        }

        // Hay goles - determinar equipo
        $teamName = $firstGoalTeam === 'HOME' ? $match->home_team : $match->away_team;
        foreach ($question->options as $option) {
            if (strpos(strtolower($option->text), strtolower($teamName)) !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: ÚLTIMO GOL
     */
    private function evaluateLastGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        // Encontrar último gol
        $lastGoalTeam = null;
        foreach (array_reverse($events) as $event) {
            if ($event['type'] === 'GOAL' && $event['team'] !== 'substitution') {
                $lastGoalTeam = $event['team'];
                break;
            }
        }

        if (!$lastGoalTeam) {
            foreach ($question->options as $option) {
                if (strpos(strtolower($option->text), 'ninguno') !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
            return $correctOptionIds;
        }

        $teamName = $lastGoalTeam === 'HOME' ? $match->home_team : $match->away_team;
        foreach ($question->options as $option) {
            if (strpos(strtolower($option->text), strtolower($teamName)) !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: FALTAS
     */
    private function evaluateFouls(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $stats = $this->parseStatistics($match->statistics ?? []);

        $homeFouls = $stats['home']['fouls'] ?? 0;
        $awayFouls = $stats['away']['fouls'] ?? 0;

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            if ($homeFouls > $awayFouls && strpos($optionText, strtolower($match->home_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($awayFouls > $homeFouls && strpos($optionText, strtolower($match->away_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($homeFouls === $awayFouls && strpos($optionText, 'ninguno') !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: TARJETAS AMARILLAS
     */
    private function evaluateYellowCards(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        $homeYellow = count(array_filter($events, fn($e) => $e['type'] === 'CARD' && $e['card'] === 'YELLOW' && $e['team'] === 'HOME'));
        $awayYellow = count(array_filter($events, fn($e) => $e['type'] === 'CARD' && $e['card'] === 'YELLOW' && $e['team'] === 'AWAY'));

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            if ($homeYellow > $awayYellow && strpos($optionText, strtolower($match->home_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($awayYellow > $homeYellow && strpos($optionText, strtolower($match->away_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($homeYellow === $awayYellow && strpos($optionText, 'ninguno') !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: TARJETAS ROJAS
     */
    private function evaluateRedCards(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        $homeRed = count(array_filter($events, fn($e) => $e['type'] === 'CARD' && $e['card'] === 'RED' && $e['team'] === 'HOME'));
        $awayRed = count(array_filter($events, fn($e) => $e['type'] === 'CARD' && $e['card'] === 'RED' && $e['team'] === 'AWAY'));

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            if ($homeRed > $awayRed && strpos($optionText, strtolower($match->home_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($awayRed > $homeRed && strpos($optionText, strtolower($match->away_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($homeRed === $awayRed && strpos($optionText, 'ninguno') !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: AUTOGOL
     */
    private function evaluateOwnGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        $homeOwnGoals = count(array_filter($events, fn($e) => $e['type'] === 'OWN_GOAL' && $e['team'] === 'HOME'));
        $awayOwnGoals = count(array_filter($events, fn($e) => $e['type'] === 'OWN_GOAL' && $e['team'] === 'AWAY'));

        if ($homeOwnGoals > 0 || $awayOwnGoals > 0) {
            foreach ($question->options as $option) {
                $optionText = strtolower(trim($option->text));

                if ($homeOwnGoals > 0 && strpos($optionText, strtolower($match->home_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                } elseif ($awayOwnGoals > 0 && strpos($optionText, strtolower($match->away_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        } else {
            foreach ($question->options as $option) {
                if (strpos(strtolower($option->text), 'ninguno') !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOLES DE PENAL
     */
    private function evaluatePenaltyGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        $homePenalty = count(array_filter($events, fn($e) => $e['type'] === 'PENALTY' && $e['team'] === 'HOME'));
        $awayPenalty = count(array_filter($events, fn($e) => $e['type'] === 'PENALTY' && $e['team'] === 'AWAY'));

        if ($homePenalty > 0 || $awayPenalty > 0) {
            foreach ($question->options as $option) {
                $optionText = strtolower(trim($option->text));

                if ($homePenalty > 0 && strpos($optionText, strtolower($match->home_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                } elseif ($awayPenalty > 0 && strpos($optionText, strtolower($match->away_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        } else {
            foreach ($question->options as $option) {
                if (strpos(strtolower($option->text), 'ninguno') !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOLES DE TIRO LIBRE
     */
    private function evaluateFreeKickGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        $homeFreeKick = count(array_filter($events, fn($e) => $e['type'] === 'FREE_KICK' && $e['team'] === 'HOME'));
        $awayFreeKick = count(array_filter($events, fn($e) => $e['type'] === 'FREE_KICK' && $e['team'] === 'AWAY'));

        if ($homeFreeKick > 0 || $awayFreeKick > 0) {
            foreach ($question->options as $option) {
                $optionText = strtolower(trim($option->text));

                if ($homeFreeKick > 0 && strpos($optionText, strtolower($match->home_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                } elseif ($awayFreeKick > 0 && strpos($optionText, strtolower($match->away_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        } else {
            foreach ($question->options as $option) {
                if (strpos(strtolower($option->text), 'ninguno') !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOLES DE CÓRNER
     */
    private function evaluateCornerGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        $homeCorner = count(array_filter($events, fn($e) => $e['type'] === 'CORNER' && $e['team'] === 'HOME'));
        $awayCorner = count(array_filter($events, fn($e) => $e['type'] === 'CORNER' && $e['team'] === 'AWAY'));

        if ($homeCorner > 0 || $awayCorner > 0) {
            foreach ($question->options as $option) {
                $optionText = strtolower(trim($option->text));

                if ($homeCorner > 0 && strpos($optionText, strtolower($match->home_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                } elseif ($awayCorner > 0 && strpos($optionText, strtolower($match->away_team)) !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        } else {
            foreach ($question->options as $option) {
                if (strpos(strtolower($option->text), 'ninguno') !== false) {
                    $correctOptionIds[] = $option->id;
                }
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: POSESIÓN DE BALÓN
     */
    private function evaluatePossession(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $stats = $this->parseStatistics($match->statistics ?? []);

        $homePossession = $stats['home']['possession'] ?? 50;
        $awayPossession = $stats['away']['possession'] ?? 50;

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            if ($homePossession > $awayPossession && strpos($optionText, strtolower($match->home_team)) !== false) {
                $correctOptionIds[] = $option->id;
            } elseif ($awayPossession > $homePossession && strpos($optionText, strtolower($match->away_team)) !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: AMBOS EQUIPOS ANOTAN
     */
    private function evaluateBothScore(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $homeScore = $match->home_team_score ?? 0;
        $awayScore = $match->away_team_score ?? 0;

        $bothScored = $homeScore > 0 && $awayScore > 0;

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            if ($bothScored && (strpos($optionText, 'sí') !== false || strpos($optionText, 'si') !== false)) {
                $correctOptionIds[] = $option->id;
            } elseif (!$bothScored && (strpos($optionText, 'no') !== false)) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: SCORE EXACTO (ej: "2-1", "3-0")
     */
    private function evaluateExactScore(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $homeScore = $match->home_team_score ?? 0;
        $awayScore = $match->away_team_score ?? 0;
        $exactScore = "{$homeScore}-{$awayScore}";

        foreach ($question->options as $option) {
            $optionText = trim($option->text);

            // Match exact score like "2-1", "3-0", etc.
            if (strpos($optionText, $exactScore) !== false ||
                strpos($optionText, "{$homeScore} - {$awayScore}") !== false) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOLES OVER/UNDER
     * Ej: "Más de 2 goles", "Menos de 3 goles"
     */
    private function evaluateGoalsOverUnder(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $totalGoals = ($match->home_team_score ?? 0) + ($match->away_team_score ?? 0);

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            // Extract number from text like "over 2.5" or "under 2.5"
            if (preg_match('/(\d+\.?\d*)/', $optionText, $matches)) {
                $threshold = (float)$matches[1];

                if ((strpos($optionText, 'over') !== false || strpos($optionText, 'más') !== false) && $totalGoals > $threshold) {
                    $correctOptionIds[] = $option->id;
                } elseif ((strpos($optionText, 'under') !== false || strpos($optionText, 'menos') !== false) && $totalGoals < $threshold) {
                    $correctOptionIds[] = $option->id;
                }
            }
        }

        return $correctOptionIds;
    }

    /**
     * Parsea eventos JSON del partido
     */
    private function parseEvents($events): array
    {
        if (is_string($events)) {
            $events = json_decode($events, true) ?? [];
        }

        if (!is_array($events)) {
            return [];
        }

        return $events;
    }

    /**
     * Parsea estadísticas JSON del partido
     */
    private function parseStatistics($statistics): array
    {
        if (is_string($statistics)) {
            $statistics = json_decode($statistics, true) ?? [];
        }

        if (!is_array($statistics)) {
            return ['home' => [], 'away' => []];
        }

        return [
            'home' => $statistics['home'] ?? [],
            'away' => $statistics['away'] ?? []
        ];
    }
}
