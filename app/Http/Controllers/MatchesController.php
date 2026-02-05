<?php

namespace App\Http\Controllers;

use App\Services\MatchesCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MatchesController extends Controller
{
    /**
     * Servicio de calendario de partidos
     */
    protected MatchesCalendarService $matchesService;

    public function __construct(MatchesCalendarService $matchesService)
    {
        $this->matchesService = $matchesService;
    }

    /**
     * GET /api/matches/calendar
     * 
     * Obtiene partidos agrupados por fecha
     * 
     * @param Request $request
     * 
     * Query Parameters:
     * - from_date: string (YYYY-MM-DD, default: hoy)
     * - to_date: string (YYYY-MM-DD, default: hoy + 7 días)
     * - competition_id: int (opcional)
     * - team_ids: array (opcional, IDs de equipos)
     * - include_finished: bool (default: true)
     * 
     * @return JsonResponse
     */
    public function calendar(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'nullable|date_format:Y-m-d',
                'to_date' => 'nullable|date_format:Y-m-d',
                'competition_id' => 'nullable|integer|exists:competitions,id',
                'team_ids' => 'nullable|array',
                'team_ids.*' => 'integer|exists:teams,id',
                'include_finished' => 'nullable|boolean',
            ]);

            $fromDate = $validated['from_date'] ?? null;
            $toDate = $validated['to_date'] ?? null;
            $competitionId = $validated['competition_id'] ?? null;
            $teamIds = $validated['team_ids'] ?? [];
            $includeFinished = $validated['include_finished'] ?? true;

            // Validar que from_date <= to_date
            if ($fromDate && $toDate && $fromDate > $toDate) {
                throw ValidationException::withMessages([
                    'to_date' => 'La fecha final debe ser posterior a la inicial.',
                ]);
            }

            $matches = $this->matchesService->getMatchesByDate(
                $fromDate,
                $toDate,
                $competitionId,
                $teamIds,
                $includeFinished
            );

            return response()->json([
                'success' => true,
                'data' => $matches,
                'meta' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'competition_id' => $competitionId,
                    'teams_count' => count($teamIds),
                    'total_matches' => collect($matches)->sum(fn($day) => count($day)),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en matches calendar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener partidos',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /api/matches/by-competition/{competitionId}
     * 
     * Obtiene partidos de una competencia específica
     * 
     * @param int $competitionId
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function byCompetition(int $competitionId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'nullable|date_format:Y-m-d',
                'to_date' => 'nullable|date_format:Y-m-d',
            ]);

            $matches = $this->matchesService->getByCompetition(
                $competitionId,
                $validated['from_date'] ?? null,
                $validated['to_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'competition_id' => $competitionId,
                'data' => $matches,
                'meta' => [
                    'total_matches' => collect($matches)->sum(fn($day) => count($day)),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener partidos de competencia',
            ], 500);
        }
    }

    /**
     * GET /api/matches/by-teams
     * 
     * Obtiene partidos de equipos específicos
     * 
     * @param Request $request
     * 
     * Query Parameters:
     * - team_ids: array (IDs de equipos - requerido)
     * - from_date: string (YYYY-MM-DD, optional)
     * - to_date: string (YYYY-MM-DD, optional)
     * 
     * @return JsonResponse
     */
    public function byTeams(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'team_ids' => 'required|array',
                'team_ids.*' => 'integer|exists:teams,id',
                'from_date' => 'nullable|date_format:Y-m-d',
                'to_date' => 'nullable|date_format:Y-m-d',
            ]);

            $teamIds = $validated['team_ids'];
            $fromDate = $validated['from_date'] ?? null;
            $toDate = $validated['to_date'] ?? null;

            $matches = $this->matchesService->getByTeams(
                $teamIds,
                $fromDate,
                $toDate
            );

            return response()->json([
                'success' => true,
                'teams' => $teamIds,
                'data' => $matches,
                'meta' => [
                    'teams_count' => count($teamIds),
                    'total_matches' => collect($matches)->sum(fn($day) => count($day)),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener partidos de equipos',
            ], 500);
        }
    }

    /**
     * GET /api/matches/competitions
     * 
     * Obtiene lista de competencias disponibles
     * 
     * @return JsonResponse
     */
    public function competitions(): JsonResponse
    {
        try {
            $competitions = $this->matchesService->getAvailableCompetitions();

            return response()->json([
                'success' => true,
                'data' => $competitions,
                'meta' => [
                    'total' => $competitions->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener competencias',
            ], 500);
        }
    }

    /**
     * GET /api/matches/teams
     * 
     * Obtiene lista de equipos disponibles
     * 
     * @return JsonResponse
     */
    public function teams(): JsonResponse
    {
        try {
            $teams = $this->matchesService->getAvailableTeams();

            return response()->json([
                'success' => true,
                'data' => $teams,
                'meta' => [
                    'total' => $teams->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener equipos',
            ], 500);
        }
    }

    /**
     * GET /api/matches/statistics
     * 
     * Obtiene estadísticas de partidos
     * 
     * @param Request $request
     * 
     * Query Parameters:
     * - from_date: string (YYYY-MM-DD, optional)
     * - to_date: string (YYYY-MM-DD, optional)
     * - competition_id: int (optional)
     * 
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'nullable|date_format:Y-m-d',
                'to_date' => 'nullable|date_format:Y-m-d',
                'competition_id' => 'nullable|integer|exists:competitions,id',
            ]);

            $stats = $this->matchesService->getStatistics(
                $validated['from_date'] ?? null,
                $validated['to_date'] ?? null,
                $validated['competition_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
            ], 500);
        }
    }

    /**
     * POST /api/matches/sync
     * 
     * Sincroniza partidos desde API externa (requiere autenticación)
     * 
     * @param Request $request
     * 
     * Body Parameters:
     * - competition_id: int (requerido)
     * - league_id: int (requerido - ID en API-Sports)
     * - season: int (requerido)
     * 
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        // Esta ruta requiere autenticación y privilegios de admin
        // La protección debe ir en las rutas

        try {
            $validated = $request->validate([
                'competition_id' => 'required|integer|exists:competitions,id',
                'league_id' => 'required|integer',
                'season' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            ]);

            $result = $this->matchesService->syncFromExternalAPI(
                $validated['competition_id'],
                $validated['league_id'],
                $validated['season']
            );

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($result, $statusCode);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error sincronizando partidos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar partidos',
            ], 500);
        }
    }
}
