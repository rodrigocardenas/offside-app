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
 * - goal_before_minute: ¿Habrá gol antes del minuto X?
 */
class QuestionEvaluationService
{
    private ?GeminiService $geminiService;

    /**
     * ✅ CACHE: Almacena datos completos del partido para evitar llamadas múltiples a Gemini
     * Clave: match_id, Valor: datos completos obtenidos de Gemini
     *
     * Esto es CRUCIAL para evitar rate limiting cuando hay múltiples preguntas
     * que necesitan información del mismo partido
     */
    private array $matchDataCache = [];

    /**
     * ✅ DEDUPLICATION CACHE: Agrupa preguntas por (match_id, template_question_id)
     * para evitar verificaciones duplicadas.
     *
     * Ejemplo:
     * - Question #29 (template_id: 5, group: 1) → Resultado verificado: [1]
     * - Question #24 (template_id: 5, group: 2) → Usa MISMO resultado: [1]
     * - Question #18 (template_id: 5, group: 3) → Usa MISMO resultado: [1]
     *
     * Clave: "match_id|template_id", Valor: IDs de opciones correctas
     * Reducción esperada: 80-90% de llamadas a Gemini cuando hay preguntas duplicadas
     */
    private array $templateResultsCache = [];

    /**
     * ✅ TEAM API IDS CACHE: Almacena los IDs de la API de los equipos para fuzzy matching
     * Clave: match_id, Valor: ['home_id' => int, 'away_id' => int]
     */
    private array $teamApiIdsCache = [];

    public function __construct(?GeminiService $geminiService = null)
    {
        $this->geminiService = $geminiService;
        $this->matchDataCache = [];
        $this->templateResultsCache = [];
    }

    /**
     * Evalúa una pregunta y determina las opciones correctas.
     *
     * @param Question $question Pregunta a evaluar
     * @param FootballMatch $match Partido finalizado con datos
     * @return array IDs de opciones correctas
     */
    public function evaluateQuestion(Question $question, FootballMatch $match): array
    {
        if (!in_array($match->status, ['FINISHED', 'Match Finished', 'Finished'])) {
            Log::warning('Match not finished', [
                'match_id' => $match->id,
                'status' => $match->status
            ]);
            return [];
        }

        // ✅ DEDUPLICATION CHECK: Si ya verificamos este template para este partido,
        // usa el resultado cacheado (mismo template = mismo resultado)
        if ($question->template_question_id) {
            $templateKey = "{$match->id}|{$question->template_question_id}";

            if (isset($this->templateResultsCache[$templateKey])) {
                Log::info('✅ Template result cached (deduplication hit)', [
                    'question_id' => $question->id,
                    'match_id' => $match->id,
                    'template_question_id' => $question->template_question_id,
                    'cached_result' => $this->templateResultsCache[$templateKey],
                    'dedup_group' => $templateKey,
                ]);

                return $this->templateResultsCache[$templateKey];
            }
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
            $questionHandled = false;

            // Determinar tipo de pregunta y evaluar
            if ($this->isQuestionAbout($questionText, 'resultado|ganador|victoria|gana|ganará')) {
                // ✅ Score-based: Siempre se puede verificar
                $questionHandled = true;
                $correctOptions = $this->evaluateWinner($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'primer gol|anotará.*primer')) {
                // ❌ Event-based: Solo si hay datos verificados
                $questionHandled = true;
                $correctOptions = $this->evaluateFirstGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isGoalBeforeMinuteQuestion($questionText)) {
                // ❌ Event-based: Gol antes de cierto minuto
                $threshold = $this->extractMinuteThreshold($questionText) ?? 15;
                $questionHandled = true;
                $correctOptions = $this->evaluateGoalBeforeMinute($question, $match, $threshold);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'ultimo gol|anotará.*último')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateLastGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'más.*faltas|faltas')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateFouls($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'tarjetas amarillas|amarillas')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateYellowCards($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'tarjetas rojas|rojas')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateRedCards($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'autogol|auto gol')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateOwnGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'penal|penalty')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluatePenaltyGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'tiro libre|free kick')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateFreeKickGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'córner|corner')) {
                // ❌ Event-based
                $questionHandled = true;
                $correctOptions = $this->evaluateCornerGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'últimos.*15|últimos.*quince|últimos.*minutos|late.*goal')) {
                // ❌ Event-based: Gol en últimos 15 minutos
                $questionHandled = true;
                $correctOptions = $this->evaluateLateGoal($question, $match);
            } elseif ($hasVerifiedData && $this->isQuestionAbout($questionText, 'antes.*descanso|first.*half|primer.*tiempo|minuto.*45')) {
                // ❌ Event-based: Gol antes del descanso
                $questionHandled = true;
                $correctOptions = $this->evaluateGoalBeforeHalftime($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'tiros.*arco|shots.*target|remates.*portería|tiro al arco')) {
                // ✅ Statistics-based: Tiros al arco
                $questionHandled = true;
                $correctOptions = $this->evaluateShotsOnTarget($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'posesión|possession')) {
                // ✅ Statistics-based: Posesión está en statistics, no requiere Gemini
                $questionHandled = true;
                $correctOptions = $this->evaluatePossession($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'ambos.*anotan|both.*score')) {
                // ✅ Score-based: Siempre se puede verificar
                $questionHandled = true;
                $correctOptions = $this->evaluateBothScore($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'score.*exacto|exact|marcador')) {
                // ✅ Score-based: Siempre se puede verificar
                $questionHandled = true;
                $correctOptions = $this->evaluateExactScore($question, $match);
            } elseif ($this->isQuestionAbout($questionText, 'goles.*over|goles.*under|total.*goles|más.*goles|mas.*goles|menos.*goles')) {
                // ✅ Score-based: Siempre se puede verificar
                $questionHandled = true;
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
                    'question_text' => $question->title,
                    'match_id' => $match->id,
                    'match_name' => "{$match->home_team} vs {$match->away_team}",
                    'has_verified_data' => $hasVerifiedData,
                    'has_statistics' => !empty($match->statistics),
                    'has_events' => !empty($match->events),
                    'statistics_keys' => is_string($match->statistics) ? array_keys(json_decode($match->statistics, true) ?? []) : array_keys($match->statistics ?? []),
                ]);

                $fallbackOptions = $this->attemptGeminiFallback(
                    $question,
                    $match,
                    $questionHandled ? 'empty_result' : 'unknown_type'
                );

                if (!empty($fallbackOptions)) {
                    return $fallbackOptions;
                }
            }

            // ✅ DEDUPLICATION: Cachear resultado para futuras preguntas del mismo template
            if ($question->template_question_id && !empty($correctOptions)) {
                $templateKey = "{$match->id}|{$question->template_question_id}";
                $this->templateResultsCache[$templateKey] = $correctOptions;

                Log::info('✅ Template result cached for deduplication', [
                    'question_id' => $question->id,
                    'match_id' => $match->id,
                    'template_question_id' => $question->template_question_id,
                    'result' => $correctOptions,
                    'dedup_group' => $templateKey,
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
        $text = strtolower($text);
        $patterns = explode('|', strtolower($keywords));

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);

            if ($pattern === '') {
                continue;
            }

            $withPlaceholders = str_replace('.*', '{wildcard}', $pattern);
            $escaped = preg_quote($withPlaceholders, '/');
            $regexPattern = str_replace('\{wildcard\}', '.*', $escaped);

            if (@preg_match('/' . $regexPattern . '/u', $text)) {
                if (preg_match('/' . $regexPattern . '/u', $text)) {
                    return true;
                }
            } elseif (strpos($text, $pattern) !== false) {
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

        // Encontrar primer gol (excluyendo penales fallados)
        $firstGoalTeam = null;
        foreach ($events as $event) {
            // ✅ VALIDACIÓN MEJORADA: Usar isValidGoal() para excluir penales fallados
            if ($this->isValidGoal($event)) {
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

        // Obtener IDs de equipos para mejor matching
        $teamIds = $this->getTeamApiIds($match);
        $homeTeamId = $teamIds['home_id'] ?? null;
        $awayTeamId = $teamIds['away_id'] ?? null;

        // Determinar qué equipo anotó usando fuzzy matching + IDs
        $scoringTeamId = null;
        if ($this->teamNameMatches($firstGoalTeam, $match->home_team, $homeTeamId)) {
            $scoringTeamId = $homeTeamId;
        } elseif ($this->teamNameMatches($firstGoalTeam, $match->away_team, $awayTeamId)) {
            $scoringTeamId = $awayTeamId;
        }

        // Usar fuzzy matching para comparar contra opciones
        foreach ($question->options as $option) {
            // Intentar match por nombre
            if ($this->teamNameMatches($option->text, $firstGoalTeam)) {
                $correctOptionIds[] = $option->id;
            }
            // Si tenemos ID del equipo que anotó, también intentar match por ID
            elseif ($scoringTeamId !== null && preg_match('/\b' . preg_quote($scoringTeamId) . '\b/', $option->text)) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOL ANTES DEL MINUTO X
     */
    private function evaluateGoalBeforeMinute(Question $question, FootballMatch $match, int $thresholdMinutes): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        // Encontrar primer gol antes del umbral (excluyendo penales fallados)
        $firstGoalTeamBeforeThreshold = null;
        foreach ($events as $event) {
            // ✅ VALIDACIÓN MEJORADA: Usar isValidGoal() para excluir penales fallados
            if (!$this->isValidGoal($event)) {
                continue;
            }

            // ✅ ACTUALIZADO: 'minute' ya está normalizado en $event (en segundos o int)
            $minute = $event['minute'] ?? null;

            if ($minute === null) {
                continue;
            }

            if ($minute <= $thresholdMinutes) {
                $firstGoalTeamBeforeThreshold = $event['team'];
                break;
            }
        }

        if (!$firstGoalTeamBeforeThreshold) {
            // No hay gol antes del umbral - buscar "No"
            foreach ($question->options as $option) {
                if ($this->isNegativeOption(strtolower(trim($option->text)))) {
                    $correctOptionIds[] = $option->id;
                }
            }
            return $correctOptionIds;
        }

        // Hay gol antes del umbral - el event['team'] es el nombre del equipo (string)
        // Usar fuzzy matching para comparar contra opciones
        foreach ($question->options as $option) {
            if ($this->teamNameMatches($option->text, $firstGoalTeamBeforeThreshold)) {
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

        // Encontrar último gol (excluyendo penales fallados)
        $lastGoalTeam = null;
        foreach (array_reverse($events) as $event) {
            // ✅ VALIDACIÓN MEJORADA: Usar isValidGoal() para excluir penales fallados
            if ($this->isValidGoal($event)) {
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

        // El event['team'] es el nombre del equipo (string)
        foreach ($question->options as $option) {
            if (strpos(strtolower($option->text), strtolower($lastGoalTeam)) !== false) {
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

        if ($homeFouls === 0 && $awayFouls === 0) {
            \Log::warning('No fouls data found for match', [
                'match_id' => $match->id,
                'stats' => $stats,
            ]);
        }

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
        $questionText = strtolower($question->title ?? '');

        // event['team'] contiene el nombre del equipo (string), no HOME/AWAY
        $homeYellow = count(array_filter($events, fn($e) => $this->isCardEventOfType($e, 'YELLOW') && ($e['team'] ?? null) === $match->home_team));
        $awayYellow = count(array_filter($events, fn($e) => $this->isCardEventOfType($e, 'YELLOW') && ($e['team'] ?? null) === $match->away_team));
        $totalYellow = $homeYellow + $awayYellow;

        $threshold = $this->extractNumericValueFromText($questionText);
        $asksMoreThan = $this->referencesMoreThan($questionText);
        $asksLessThan = $this->referencesLessThan($questionText);

        if ($threshold !== null && ($asksMoreThan || $asksLessThan)) {
            $condition = $asksMoreThan ? $totalYellow > $threshold : $totalYellow < $threshold;
            $binaryResult = $this->resolveBinaryQuestionOptions($question, $condition);

            if (!empty($binaryResult)) {
                return $binaryResult;
            }
        }

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

        // event['team'] contiene el nombre del equipo (string), no HOME/AWAY
        $homeRed = count(array_filter($events, fn($e) => $this->isCardEventOfType($e, 'RED') && ($e['team'] ?? null) === $match->home_team));
        $awayRed = count(array_filter($events, fn($e) => $this->isCardEventOfType($e, 'RED') && ($e['team'] ?? null) === $match->away_team));

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

        // event['team'] contiene el nombre del equipo (string), no HOME/AWAY
        $homeOwnGoals = count(array_filter($events, fn($e) => $e['type'] === 'OWN_GOAL' && ($e['team'] ?? null) === $match->home_team));
        $awayOwnGoals = count(array_filter($events, fn($e) => $e['type'] === 'OWN_GOAL' && ($e['team'] ?? null) === $match->away_team));
        $hasOwnGoal = $homeOwnGoals > 0 || $awayOwnGoals > 0;

        $binaryResult = $this->resolveBinaryQuestionOptions($question, $hasOwnGoal);
        if (!empty($binaryResult)) {
            return $binaryResult;
        }

        if ($hasOwnGoal) {
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
     *
     * ✅ AHORA FUNCIONA: API Football PRO SÍ proporciona el campo 'detail' con "Penalty"
     *
     * Buscamos en el campo 'detail':
     * - "Penalty" → Gol de penal
     * - "Own Goal" → Autogol
     * - "Normal Goal" → Gol normal
     *
     * El campo 'detail' se captura desde API Football y se guarda en events.
     */
    private function evaluatePenaltyGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        // Buscar penales en el campo 'detail' (ahora disponible)
        $homePenalty = 0;
        $awayPenalty = 0;
        $foundPenaltyData = false;

        foreach ($events as $event) {
            $type = strtoupper($event['type'] ?? '');
            $team = $event['team'] ?? null;
            $detail = strtolower($event['detail'] ?? '');

            // ✅ AHORA BUSCAMOS EN 'detail'
            if ($type === 'GOAL' && stripos($detail, 'penalty') !== false) {
                $foundPenaltyData = true;
                if ($team === $match->home_team) {
                    $homePenalty++;
                } elseif ($team === $match->away_team) {
                    $awayPenalty++;
                }
            }
        }

        if ($foundPenaltyData) {
            \Log::info('✅ Penalty goals detected in events - using API Football detail field', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'home_penalties' => $homePenalty,
                'away_penalties' => $awayPenalty
            ]);
        } else {
            \Log::warning('Penalty goals NOT found in events - detail field may be empty', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'match' => "{$match->home_team} vs {$match->away_team}",
                'events_count' => count($events)
            ]);
        }

        $hasPenalty = $homePenalty > 0 || $awayPenalty > 0;

        $binaryResult = $this->resolveBinaryQuestionOptions($question, $hasPenalty);
        if (!empty($binaryResult)) {
            return $binaryResult;
        }

        if ($hasPenalty) {
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

        // ✅ AHORA FUNCIONA: El campo 'detail' contiene "Free Kick"
        $homeFreeKick = 0;
        $awayFreeKick = 0;

        foreach ($events as $event) {
            $type = strtoupper($event['type'] ?? '');
            $team = $event['team'] ?? null;
            $detail = strtolower($event['detail'] ?? '');

            if ($type === 'GOAL' && stripos($detail, 'free kick') !== false) {
                if ($team === $match->home_team) {
                    $homeFreeKick++;
                } elseif ($team === $match->away_team) {
                    $awayFreeKick++;
                }
            }
        }

        $hasFreeKickGoal = $homeFreeKick > 0 || $awayFreeKick > 0;

        if ($hasFreeKickGoal) {
            \Log::info('Free kick goals detected in events', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'home_free_kicks' => $homeFreeKick,
                'away_free_kicks' => $awayFreeKick
            ]);
        }

        $binaryResult = $this->resolveBinaryQuestionOptions($question, $hasFreeKickGoal);
        if (!empty($binaryResult)) {
            return $binaryResult;
        }

        if ($hasFreeKickGoal) {
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
     *
     * ✅ AHORA FUNCIONA: El campo 'detail' contiene "Corner"
     */
    private function evaluateCornerGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        // ✅ AHORA BUSCAMOS EN 'detail' para "Corner"
        $homeCorner = 0;
        $awayCorner = 0;

        foreach ($events as $event) {
            $type = strtoupper($event['type'] ?? '');
            $team = $event['team'] ?? null;
            $detail = strtolower($event['detail'] ?? '');

            if ($type === 'GOAL' && stripos($detail, 'corner') !== false) {
                if ($team === $match->home_team) {
                    $homeCorner++;
                } elseif ($team === $match->away_team) {
                    $awayCorner++;
                }
            }
        }

        $hasCornerGoal = $homeCorner > 0 || $awayCorner > 0;

        if ($hasCornerGoal) {
            \Log::info('Corner goals detected in events', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'home_corners' => $homeCorner,
                'away_corners' => $awayCorner
            ]);
        }

        $binaryResult = $this->resolveBinaryQuestionOptions($question, $hasCornerGoal);
        if (!empty($binaryResult)) {
            return $binaryResult;
        }

        if ($hasCornerGoal) {
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

        // ✅ OPTIMIZACIÓN: Buscar posesión en múltiples formatos (nuevo y viejo)
        $homePossession = $stats['possession_home']
            ?? $stats['possession']['home_percentage']
            ?? $stats['home']['possession']
            ?? 50;
        $awayPossession = $stats['possession_away']
            ?? $stats['possession']['away_percentage']
            ?? $stats['away']['possession']
            ?? 50;
        $questionText = strtolower($question->title ?? '');

        $threshold = $this->extractNumericValueFromText($questionText);
        $asksMoreThan = $this->referencesMoreThan($questionText);
        $asksLessThan = $this->referencesLessThan($questionText);

        $targetTeam = null;
        if ($this->teamNameMatches($questionText, $match->home_team)) {
            $targetTeam = 'home';
        } elseif ($this->teamNameMatches($questionText, $match->away_team)) {
            $targetTeam = 'away';
        }

        if ($targetTeam && $threshold !== null && ($asksMoreThan || $asksLessThan)) {
            $teamPossession = $targetTeam === 'home' ? $homePossession : $awayPossession;
            $condition = $asksMoreThan ? $teamPossession > $threshold : $teamPossession < $threshold;

            $binaryResult = $this->resolveBinaryQuestionOptions($question, $condition);
            if (!empty($binaryResult)) {
                return $binaryResult;
            }
        }

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            // Usar fuzzy matching para nombres de equipos
            if ($homePossession > $awayPossession && $this->teamNameMatches($optionText, $match->home_team)) {
                $correctOptionIds[] = $option->id;
            } elseif ($awayPossession > $homePossession && $this->teamNameMatches($optionText, $match->away_team)) {
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
        $questionText = strtolower($question->title ?? '');
        $questionThreshold = $this->extractNumericValueFromText($questionText);
        $questionAsksOver = $this->referencesMoreThan($questionText);
        $questionAsksUnder = $this->referencesLessThan($questionText);

        if ($questionThreshold !== null && ($questionAsksOver || $questionAsksUnder)) {
            $condition = $questionAsksOver ? $totalGoals > $questionThreshold : $totalGoals < $questionThreshold;
            $binaryResult = $this->resolveBinaryQuestionOptions($question, $condition);

            if (!empty($binaryResult)) {
                return $binaryResult;
            }
        }

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));
            $threshold = $this->extractNumericValueFromText($optionText);

            if ($threshold === null) {
                continue;
            }

            if ((strpos($optionText, 'over') !== false || strpos($optionText, 'más') !== false) && $totalGoals > $threshold) {
                $correctOptionIds[] = $option->id;
            } elseif ((strpos($optionText, 'under') !== false || strpos($optionText, 'menos') !== false) && $totalGoals < $threshold) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOL EN ÚLTIMOS 15 MINUTOS (Late Goal)
     * ✅ NUEVA: S1 - Gol en los últimos 15 minutos del partido
     *
     * Ejemplo: "¿Habrá gol en los últimos 15 minutos?"
     * Opciones: Sí/No o Equipo
     */
    private function evaluateLateGoal(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $events = $this->parseEvents($match->events ?? []);

        // Buscar goles en los últimos 15 minutos (minuto >= 75, excluyendo penales fallados)
        $lateGoals = array_filter($events, fn($e) =>
            $this->isValidGoal($e) && ($e['minute'] ?? 0) >= 75
        );

        if (empty($lateGoals)) {
            // No hubo goles en últimos 15 minutos
            foreach ($question->options as $option) {
                if ($this->isNegativeOption(strtolower(trim($option->text)))) {
                    $correctOptionIds[] = $option->id;
                }
            }
            return $correctOptionIds;
        }

        // Hubo goles - intentar resolver como pregunta binaria (Sí/No)
        $binaryResult = $this->resolveBinaryQuestionOptions($question, true);
        if (!empty($binaryResult)) {
            return $binaryResult;
        }

        // Si no es binaria, buscar por equipo
        $firstLateGoal = array_values($lateGoals)[0];
        $scoringTeam = $firstLateGoal['team'];

        foreach ($question->options as $option) {
            if ($this->teamNameMatches($option->text, $scoringTeam)) {
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * TIPO: GOL ANTES DEL DESCANSO (Goal Before Halftime)
     * ✅ NUEVA: S5 - Gol antes del minuto 45 (primer tiempo)
     *
     * Ejemplo: "¿Habrá gol en el primer tiempo?"
     * Opciones: Sí/No
     */
    private function evaluateGoalBeforeHalftime(Question $question, FootballMatch $match): array
    {
        // Reutilizar el método existente con threshold de 45
        return $this->evaluateGoalBeforeMinute($question, $match, 45);
    }

    /**
     * TIPO: TIROS AL ARCO (Shots on Target)
     * ✅ NUEVA: S2 - Cuál equipo tuvo más tiros al arco
     *
     * Ejemplo: "¿Cuál equipo tuvo más tiros al arco?"
     * Opciones: Home/Away
     * Datos: statistics.home.shots_on_target, statistics.away.shots_on_target
     */
    private function evaluateShotsOnTarget(Question $question, FootballMatch $match): array
    {
        $correctOptionIds = [];
        $statistics = $this->parseStatistics($match->statistics ?? []);

        // Obtener tiros al arco por equipo
        $homeShotsOnTarget = $statistics['home']['shots_on_target'] ?? 0;
        $awayShotsOnTarget = $statistics['away']['shots_on_target'] ?? 0;

        // Si no hay datos de tiros al arco, retornar vacío
        if ($homeShotsOnTarget === 0 && $awayShotsOnTarget === 0) {
            Log::warning('No shots on target data available', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'statistics_keys' => array_keys($statistics['home'] ?? [])
            ]);
            return $correctOptionIds;
        }

        // Comparar y encontrar opciones correctas
        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));

            if ($homeShotsOnTarget > $awayShotsOnTarget) {
                if ($this->teamNameMatches($optionText, $match->home_team)) {
                    $correctOptionIds[] = $option->id;
                }
            } elseif ($awayShotsOnTarget > $homeShotsOnTarget) {
                if ($this->teamNameMatches($optionText, $match->away_team)) {
                    $correctOptionIds[] = $option->id;
                }
            } elseif ($homeShotsOnTarget === $awayShotsOnTarget && strpos($optionText, 'igual') !== false) {
                // Si son iguales, buscar opción "Igual" o "Same amount"
                $correctOptionIds[] = $option->id;
            }
        }

        return $correctOptionIds;
    }

    /**
     * Determina si la pregunta habla de gol antes de cierto minuto
     */
    private function isGoalBeforeMinuteQuestion(string $questionText): bool
    {
        return strpos($questionText, 'gol') !== false &&
            (strpos($questionText, 'antes') !== false || strpos($questionText, 'primeros') !== false) &&
            (strpos($questionText, 'minuto') !== false || strpos($questionText, 'minutos') !== false);
    }

    /**
     * Extrae el minuto objetivo desde el texto de la pregunta
     */
    private function extractMinuteThreshold(string $questionText): ?int
    {
        if (preg_match("/(\\d+)\\s*(?:minuto|minutos|′|'|’)/u", $questionText, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function parseMinuteValue($minute): ?int
    {
        if ($minute === null || $minute === '') {
            return null;
        }

        if (is_numeric($minute)) {
            return (int) $minute;
        }

        if (is_string($minute)) {
            $value = trim($minute);

            if ($value === '') {
                return null;
            }

            if (preg_match('/(\d+)\s*\+\s*(\d+)/', $value, $matches)) {
                return (int) $matches[1] + (int) $matches[2];
            }

            if (preg_match('/(\d+)/', $value, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function resolveBinaryQuestionOptions(Question $question, bool $condition): array
    {
        $resolved = [];
        $hasBinaryOptions = false;

        foreach ($question->options as $option) {
            $optionText = strtolower(trim($option->text));
            $isAffirmative = $this->isAffirmativeOption($optionText);
            $isNegative = $this->isNegativeOption($optionText);

            if (!$isAffirmative && !$isNegative) {
                continue;
            }

            $hasBinaryOptions = true;

            if ($condition && $isAffirmative) {
                $resolved[] = $option->id;
            } elseif (!$condition && $isNegative) {
                $resolved[] = $option->id;
            }
        }

        return $hasBinaryOptions ? $resolved : [];
    }

    private function extractNumericValueFromText(?string $text): ?float
    {
        if ($text === null) {
            return null;
        }

        if (preg_match('/(\d+\.?\d*)/', $text, $matches)) {
            return (float)$matches[1];
        }

        return null;
    }

    private function referencesMoreThan(string $text): bool
    {
        $text = strtolower($text);

        return strpos($text, 'más') !== false ||
            strpos($text, 'mas') !== false ||
            strpos($text, 'over') !== false ||
            strpos($text, 'supera') !== false ||
            strpos($text, 'mayor') !== false;
    }

    private function referencesLessThan(string $text): bool
    {
        $text = strtolower($text);

        return strpos($text, 'menos') !== false ||
            strpos($text, 'under') !== false ||
            strpos($text, 'inferior') !== false ||
            strpos($text, 'menor') !== false;
    }

    private function isCardEventOfType(array $event, string $expectedType): bool
    {
        if (($event['type'] ?? '') !== 'CARD') {
            return false;
        }

        $cardType = strtoupper($event['card'] ?? $event['detail'] ?? '');

        return $cardType === strtoupper($expectedType);
    }

    /**
     * Valida si un evento es un gol válido (excluye penales fallados y otros casos inválidos)
     * 
     * @param array $event El evento a validar
     * @return bool true si es un gol válido, false si es inválido (ej: Missed Penalty)
     */
    private function isValidGoal(array $event): bool
    {
        // Verificar que sea del tipo GOAL (case-insensitive: 'GOAL', 'Goal', 'goal')
        $type = strtoupper($event['type'] ?? '');
        if ($type !== 'GOAL') {
            return false;
        }

        // Excluir penales fallados
        $detail = strtolower($event['detail'] ?? '');
        if (stripos($detail, 'missed penalty') !== false) {
            return false;
        }

        // Excluir otros casos inválidos potenciales
        if (stripos($detail, 'missed') !== false && stripos($detail, 'penalty') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Fuzzy matching: Intenta matchear un texto contra un nombre de equipo
     * Útil cuando hay variaciones en nombres (ej: "Manchester City" vs "Man City")
     *
     * @param string $optionText El texto de la opción de la pregunta
     * @param string $teamName El nombre del equipo
     * @param int|null $teamApiId Opcional: ID del equipo en la API para matching exacto
     * @return bool
     */
    private function teamNameMatches(string $optionText, string $teamName, ?int $teamApiId = null): bool
    {
        $optionLower = strtolower(trim($optionText));
        $teamLower = strtolower(trim($teamName));

        // Match exacto por nombre
        if ($optionLower === $teamLower) {
            return true;
        }

        // Contains check
        if (strpos($optionLower, $teamLower) !== false) {
            return true;
        }

        if (strpos($teamLower, $optionLower) !== false) {
            return true;
        }

        // Fuzzy: Levenshtein distance (para variaciones menores)
        // Si el error es menor al 30% de la longitud más larga, considerar match
        $maxLen = max(strlen($optionLower), strlen($teamLower));
        $distance = levenshtein($optionLower, $teamLower);
        $threshold = ceil($maxLen * 0.3);

        if ($distance <= $threshold && $distance > 0) {
            Log::debug('Fuzzy team name match', [
                'option' => $optionText,
                'team' => $teamName,
                'distance' => $distance,
                'threshold' => $threshold,
                'method' => 'levenshtein'
            ]);
            return true;
        }

        // Si se proporciona API ID, intentar buscar si la opción contiene números (como IDs)
        if ($teamApiId !== null) {
            // Buscar números en la opción de texto y compararlos
            if (preg_match('/\b' . preg_quote($teamApiId) . '\b/', $optionText)) {
                Log::debug('Team ID match found', [
                    'option' => $optionText,
                    'team_api_id' => $teamApiId
                ]);
                return true;
            }
        }

        return false;
    }

    private function isAffirmativeOption(string $optionText): bool
    {
        return strpos($optionText, 'sí') !== false ||
            strpos($optionText, 'si') !== false ||
            strpos($optionText, 'yes') !== false;
    }

    private function isNegativeOption(string $optionText): bool
    {
        return strpos($optionText, 'no') !== false ||
            strpos($optionText, 'ninguno') !== false ||
            strpos($optionText, 'ninguna') !== false;
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

        // ✅ NORMALIZAR eventos para manejar múltiples formatos
        return array_map(fn($event) => $this->normalizeEvent($event), $events);
    }

    /**
     * Normaliza eventos de diferentes formatos a un formato estándar.
     *
     * Formatos soportados:
     * - Antiguo: {'minute': '15', 'type': 'GOAL', 'team': 'HOME', ...}
     * - Nuevo (API Football): {'time': 16, 'type': 'Goal', 'team': 'Inter', ...}
     *
     * Retorna formato estándar: {'minute': 15, 'type': 'GOAL', 'team': 'equipo_name', ...}
     */
    private function normalizeEvent(array $event): array
    {
        return [
            // Normalizar minuto (puede ser 'minute' o 'time', string o int)
            'minute' => $this->parseMinuteValue($event['minute'] ?? $event['time'] ?? null),
            // Normalizar tipo de evento a UPPERCASE
            'type' => strtoupper($event['type'] ?? ''),
            // Mantener el equipo tal cual (puede ser HOME/AWAY o nombre real)
            'team' => $event['team'] ?? null,
            // Campos adicionales opcionales
            'player' => $event['player'] ?? null,
            'detail' => $event['detail'] ?? null,
            'assist' => $event['assist'] ?? null,
        ];
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

        // Si tiene estructura "teams" (nombre de equipos como keys)
        if (isset($statistics['teams']) && is_array($statistics['teams'])) {
            $teams = array_values($statistics['teams']);
            if (count($teams) >= 2) {
                return [
                    'home' => $teams[0],
                    'away' => $teams[1],
                    'possession_home' => $this->extractPercentage($teams[0]['possession'] ?? null),
                    'possession_away' => $this->extractPercentage($teams[1]['possession'] ?? null),
                ];
            }
        }

        $result = [
            'home' => $statistics['home'] ?? [],
            'away' => $statistics['away'] ?? []
        ];

        $metrics = ['possession', 'fouls', 'passes', 'shots', 'yellow_cards', 'red_cards'];
        foreach ($metrics as $metric) {
            // Dos formatos posibles: "home_metric" o "metric_home"
            $homeKey1 = 'home_' . $metric;
            $homeKey2 = $metric . '_home';
            $awayKey1 = 'away_' . $metric;
            $awayKey2 = $metric . '_away';

            if (!isset($result['home'][$metric])) {
                if (isset($statistics[$homeKey1])) {
                    $result['home'][$metric] = $statistics[$homeKey1];
                } elseif (isset($statistics[$homeKey2])) {
                    $result['home'][$metric] = $statistics[$homeKey2];
                }
            }

            if (!isset($result['away'][$metric])) {
                if (isset($statistics[$awayKey1])) {
                    $result['away'][$metric] = $statistics[$awayKey1];
                } elseif (isset($statistics[$awayKey2])) {
                    $result['away'][$metric] = $statistics[$awayKey2];
                }
            }
        }

        // Garantizar que los valores principales estén disponibles también en el nivel raíz
        $result['possession_home'] = $result['possession_home']
            ?? $this->extractPercentage($result['home']['possession'] ?? null)
            ?? null;
        $result['possession_away'] = $result['possession_away']
            ?? $this->extractPercentage($result['away']['possession'] ?? null)
            ?? null;

        return $result;
    }

    private function extractPercentage($value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            return (float) str_replace('%', '', $value);
        }
        return null;
    }

    private function shouldUseGeminiFallback(): bool
    {
        return (bool) config('question_evaluation.gemini_fallback_enabled', true);
    }

    private function attemptGeminiFallback(Question $question, FootballMatch $match, string $reason): array
    {
        if (!$this->shouldUseGeminiFallback()) {
            return [];
        }

        $gemini = $this->geminiService;

        if (!$gemini) {
            try {
                $gemini = app(GeminiService::class);
                $this->geminiService = $gemini;
            } catch (\Throwable $e) {
                Log::error('Unable to resolve GeminiService for fallback', [
                    'question_id' => $question->id,
                    'match_id' => $match->id,
                    'reason' => $reason,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        }

        // ✅ OPTIMIZACIÓN: Usar cache de datos del partido para evitar múltiples llamadas a Gemini
        $prompt = $this->buildGeminiFallbackPromptOptimized($question, $match, $reason, $gemini);

        // ✅ IMPORTANTE: Determinar si necesitamos grounding (búsqueda web)
        // - Si NO hay datos verificados en la BD: usar grounding (buscar en internet)
        // - Si SÍ hay datos verificados: NO usar grounding (más rápido)
        $hasVerified = $this->hasVerifiedMatchData($match);
        $useGrounding = !$hasVerified; // Solo usar grounding si NO hay datos verificados

        Log::info('Gemini fallback decision', [
            'question_id' => $question->id,
            'match_id' => $match->id,
            'has_verified_data' => $hasVerified,
            'use_grounding' => $useGrounding,
            'reason' => $reason
        ]);

        try {
            Log::info('Gemini fallback attempting to resolve question', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'reason' => $reason,
                'match_data_cached' => isset($this->matchDataCache[$match->id]),
                'grounding' => $useGrounding ? '✅ enabled' : '❌ disabled'
            ]);

            // ⚠️ Usar versión segura con timeout
            $response = $this->callGeminiSafe($gemini, $prompt, $useGrounding);

            if ($response === null) {
                Log::warning('Gemini did not respond (timeout or rate limit)', [
                    'question_id' => $question->id,
                    'match_id' => $match->id
                ]);
                return [];
            }

            $resolved = $this->parseGeminiFallbackResponse($response, $question);

            if (!empty($resolved)) {
                Log::info('Gemini fallback resolved question options', [
                    'question_id' => $question->id,
                    'match_id' => $match->id,
                    'reason' => $reason,
                    'option_ids' => $resolved
                ]);
            }

            return $resolved;
        } catch (\Throwable $e) {
            Log::error('Gemini fallback failed', [
                'question_id' => $question->id,
                'match_id' => $match->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            return [];
        }
    }

    /**
     * ✅ SEGURIDAD: Llamada a Gemini con timeout y manejo de rate limit
     * Lanza excepción si hay rate limit o timeout - no retorna null
     */
    private function callGeminiSafe(GeminiService $gemini, string $prompt, bool $useGrounding): ?array
    {
        $maxWait = 8; // máximo 8 segundos esperando
        $startTime = microtime(true);

        try {
            $response = $gemini->callGemini($prompt, $useGrounding);

            $elapsed = microtime(true) - $startTime;
            Log::info('Gemini responded successfully', [
                'elapsed_ms' => round($elapsed * 1000, 2)
            ]);

            return $response;

        } catch (\Exception $e) {
            $elapsed = microtime(true) - $startTime;
            $errorMsg = $e->getMessage();

            if (strpos($errorMsg, 'Rate limited') !== false || strpos($errorMsg, '429') !== false) {
                Log::warning('Gemini rate limited - throwing exception', [
                    'elapsed_ms' => round($elapsed * 1000, 2),
                    'error' => substr($errorMsg, 0, 100)
                ]);
                throw new \Exception('Rate limited por Gemini');
            }

            if ($elapsed > $maxWait) {
                Log::warning('Gemini call exceeded timeout threshold', [
                    'elapsed_ms' => round($elapsed * 1000, 2),
                    'max_wait_s' => $maxWait
                ]);
                throw new \Exception('Timeout esperando respuesta de Gemini (>' . $maxWait . 's)');
            }

            // Re-lanzar otros errores
            throw $e;
        }
    }

    /**
     * ✅ OPTIMIZACIÓN: Construir prompt reutilizando datos en caché
     *
     * Si es la primera pregunta del match:
     *   → Obtener datos del match una sola vez con Gemini
     *   → Guardar en cache ($this->matchDataCache)
     *
     * Para preguntas posteriores del MISMO match:
     *   → Reutilizar datos en caché
     *   → NO hacer llamada a Gemini adicional
     */
    private function buildGeminiFallbackPromptOptimized(
        Question $question,
        FootballMatch $match,
        string $reason,
        GeminiService $gemini
    ): string {
        // Obtener o generar datos del partido
        $matchData = $this->getMatchDataOnce($match, $gemini);

        $optionsPayload = [];
        foreach ($question->options as $index => $option) {
            $optionsPayload[] = [
                'key' => 'option_' . ($index + 1),
                'text' => (string) $option->text
            ];
        }

        $matchDate = $match->date ?? $match->match_date ?? null;
        if ($matchDate instanceof \Carbon\CarbonInterface) {
            $matchDate = $matchDate->toDateTimeString();
        }

        $context = [
            'match' => [
                'id' => $match->id,
                'home_team' => $match->home_team,
                'away_team' => $match->away_team,
                'status' => $match->status,
                'date' => $matchDate,
                'score' => [
                    'home' => $match->home_team_score,
                    'away' => $match->away_team_score
                ],
                'events' => $matchData['events'] ?? [],
                'statistics' => $matchData['statistics'] ?? []
            ],
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'type' => $question->type,
                'reason' => $reason
            ],
            'options' => $optionsPayload
        ];

        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($contextJson === false) {
            $contextJson = '{}';
        }

        return <<<PROMPT
Eres un árbitro virtual que verifica predicciones de fútbol usando datos estructurados del partido.
Analiza el contexto proporcionado y determina qué opciones son correctas.
Responde EXCLUSIVAMENTE con JSON usando la forma {"selected_options":["option_1"]}.

Contexto:
{$contextJson}

Instrucciones:
1. Basate solo en la información del contexto.
2. Si ninguna opción es correcta, devuelve {"selected_options": []}.
3. No agregues explicaciones ni texto adicional, solo el JSON solicitado.
PROMPT;
    }

    /**
     * ✅ CACHE: Obtener datos del partido UNA SOLA VEZ
     *
     * En lugar de hacer múltiples llamadas a Gemini para obtener detalles del partido,
     * esto cachea los datos en la sesión de QuestionEvaluationService.
     *
     * Primera pregunta del match:
     *   → Si no están los datos en DB → Llamar a Gemini UNA VEZ
     *   → Guardar en $matchDataCache[$match->id]
     *
     * Preguntas posteriores del MISMO match:
     *   → Usar datos en caché
     *   → CERO llamadas a Gemini adicionales
     */
    private function getMatchDataOnce(FootballMatch $match, GeminiService $gemini): array
    {
        $matchId = $match->id;

        // ✅ Si ya tenemos datos en caché, devolverlos inmediatamente
        if (isset($this->matchDataCache[$matchId])) {
            Log::debug('Match data retrieved from session cache', ['match_id' => $matchId]);
            return $this->matchDataCache[$matchId];
        }

        // Si el partido ya tiene datos en BD, usarlos
        $existingData = [
            'events' => $this->parseEvents($match->events ?? []),
            'statistics' => $this->parseStatistics($match->statistics ?? [])
        ];

        // Si hay eventos y stats existentes, guardar en caché y devolver
        if (!empty($existingData['events']) || !empty($existingData['statistics'])) {
            $this->matchDataCache[$matchId] = $existingData;
            Log::debug('Match data retrieved from database', ['match_id' => $matchId]);
            return $existingData;
        }

        // Solo si NO hay datos en BD, llamar a Gemini
        Log::info('Fetching match data from Gemini (first time)', ['match_id' => $matchId]);

        try {
            $details = $gemini->getDetailedMatchData(
                $match->home_team,
                $match->away_team,
                $match->date,
                $match->league,
                false // no force refresh
            );

            if ($details && is_array($details)) {
                $matchData = [
                    'events' => $details['events'] ?? [],
                    'statistics' => $details['statistics'] ?? []
                ];

                // Guardar en caché para preguntas posteriores del mismo match
                $this->matchDataCache[$matchId] = $matchData;

                Log::info('Match data cached from Gemini', [
                    'match_id' => $matchId,
                    'events_count' => count($matchData['events']),
                    'stats_keys' => count($matchData['statistics'])
                ]);

                return $matchData;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch match data from Gemini', [
                'match_id' => $matchId,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: devolver datos vacíos pero cachear para evitar múltiples intentos
        $matchData = ['events' => [], 'statistics' => []];
        $this->matchDataCache[$matchId] = $matchData;

        return $matchData;
    }

    private function parseGeminiFallbackResponse($response, Question $question): array
    {
        $payload = null;

        if (is_array($response)) {
            if (isset($response['selected_options']) || isset($response['selected_option'])) {
                $payload = $response;
            } elseif (isset($response['content'])) {
                $decoded = json_decode((string) $response['content'], true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        } elseif (is_string($response)) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (!is_array($payload)) {
            return [];
        }

        $selected = $payload['selected_options'] ?? $payload['selected_option'] ?? null;

        if (is_string($selected)) {
            $selected = [$selected];
        }

        if (!is_array($selected)) {
            return [];
        }

        $maps = $this->buildOptionMaps($question);
        $resolvedIds = [];

        foreach ($selected as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $normalized = strtolower(trim($candidate));

            if ($normalized === '') {
                continue;
            }

            if (isset($maps['keys'][$normalized])) {
                $resolvedIds[] = $maps['keys'][$normalized];
                continue;
            }

            if (isset($maps['texts'][$normalized])) {
                $resolvedIds[] = $maps['texts'][$normalized];
                continue;
            }

            foreach ($maps['texts'] as $text => $optionId) {
                if (str_contains($text, $normalized) || str_contains($normalized, $text)) {
                    $resolvedIds[] = $optionId;
                    break;
                }
            }
        }

        return array_values(array_unique($resolvedIds));
    }

    private function buildOptionMaps(Question $question): array
    {
        $keyMap = [];
        $textMap = [];

        foreach ($question->options as $index => $option) {
            $key = 'option_' . ($index + 1);
            $keyMap[strtolower($key)] = $option->id;
            $keyMap[$key] = $option->id;

            $text = strtolower(trim((string) $option->text));
            if ($text !== '') {
                $textMap[$text] = $option->id;
            }
        }

        return ['keys' => $keyMap, 'texts' => $textMap];
    }

    private function summarizeEventsForFallback(FootballMatch $match): array
    {
        $events = $this->parseEvents($match->events ?? []);
        $limit = (int) config('question_evaluation.gemini_fallback_max_events', 40);

        if ($limit > 0) {
            $events = array_slice($events, 0, $limit);
        }

        return $events;
    }

    /**
     * ✅ Obtiene estadísticas de deduplicación (cache hits)
     * Útil para monitoreo y debugging
     */
    public function getDeduplicationStats(): array
    {
        return [
            'template_cache_size' => count($this->templateResultsCache),
            'cached_templates' => array_keys($this->templateResultsCache),
            'template_results' => $this->templateResultsCache,
        ];
    }

    /**
     * ✅ Limpia el cache de deduplicación
     * Útil entre diferentes batch jobs o cuando hay cambios en datos
     */
    public function clearDeduplicationCache(): void
    {
        $this->templateResultsCache = [];
        Log::info('✅ Template deduplication cache cleared');
    }

    /**
     * Obtiene los IDs de la API de los equipos (home y away)
     * Cachea el resultado para evitar múltiples consultas
     *
     * @return array|null ['home_id' => int, 'away_id' => int] o null si no están disponibles
     */
    private function getTeamApiIds(FootballMatch $match): ?array
    {
        // Verificar caché
        if (isset($this->teamApiIdsCache[$match->id])) {
            return $this->teamApiIdsCache[$match->id];
        }

        try {
            // Cargar los equipos con sus IDs externos
            $homeTeam = $match->homeTeam()->first();
            $awayTeam = $match->awayTeam()->first();

            if (!$homeTeam || !$awayTeam || !$homeTeam->external_id || !$awayTeam->external_id) {
                return null;
            }

            $ids = [
                'home_id' => (int) $homeTeam->external_id,
                'away_id' => (int) $awayTeam->external_id,
            ];

            // Cachear
            $this->teamApiIdsCache[$match->id] = $ids;

            return $ids;
        } catch (\Exception $e) {
            Log::debug('Could not load team API IDs', [
                'match_id' => $match->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Evalúa una pregunta de tipo 'quiz'.
     * 
     * Las preguntas quiz son de conocimiento general sin relación a partidos.
     * Se evalúan basándose en la opción marcada como correcta en el template.
     *
     * @param Question $question Pregunta de tipo quiz
     * @return bool True si la opción seleccionada es correcta
     */
    public function evaluateQuizQuestion(Question $question, ?int $selectedOptionId = null): bool
    {
        if ($question->type !== 'quiz') {
            Log::warning('evaluateQuizQuestion called with non-quiz question', [
                'question_id' => $question->id,
                'type' => $question->type
            ]);
            return false;
        }

        // Si no se proporciona selectedOptionId, significa que se está validando después que el usuario respondió
        if ($selectedOptionId === null) {
            return false;
        }

        // Obtener la opción seleccionada
        $selectedOption = \App\Models\QuestionOption::find($selectedOptionId);
        
        if (!$selectedOption || $selectedOption->question_id !== $question->id) {
            Log::warning('Invalid question option for quiz evaluation', [
                'question_id' => $question->id,
                'selected_option_id' => $selectedOptionId
            ]);
            return false;
        }

        // Retornar si la opción seleccionada es correcta
        return (bool) $selectedOption->is_correct;
    }

    /**
     * Obtiene la opción correcta de una pregunta quiz
     *
     * @param Question $question
     * @return \App\Models\QuestionOption|null
     */
    public function getQuizCorrectOption(Question $question): ?\App\Models\QuestionOption
    {
        if ($question->type !== 'quiz') {
            return null;
        }

        return $question->options()
            ->where('is_correct', true)
            ->first();
    }
}

