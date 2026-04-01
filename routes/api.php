<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PushTokenController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MatchesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/competitions/{competition}/teams', [App\Http\Controllers\CompetitionController::class, 'getTeams']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
    Route::get('/timezone-status', function (Request $request) {
        $user = $request->user();
        $deviceTimezone = $request->query('device_tz');

        return response()->json([
            'user_id' => $user->id,
            'saved_timezone' => $user->timezone,
            'device_timezone' => $deviceTimezone,
            'match' => $user->timezone === $deviceTimezone,
            'last_updated' => $user->updated_at,
        ]);
    });
    Route::post('/cache/clear-user', function (Request $request) {
        // Limpiar cache específico del usuario
        $userId = $request->user()->id;

        // Limpiar todos los caches relacionados con este usuario
        \Illuminate\Support\Facades\Cache::forget('user_answers_' . $userId);
        \Illuminate\Support\Facades\Cache::forget('user_groups_' . $userId);

        // Limpiar caches de grupos del usuario
        foreach ($request->user()->groups as $group) {
            \Illuminate\Support\Facades\Cache::forget("group_{$group->id}_match_questions");
            \Illuminate\Support\Facades\Cache::forget("group_{$group->id}_social_question");
            \Illuminate\Support\Facades\Cache::forget("group_{$group->id}_user_answers");
            \Illuminate\Support\Facades\Cache::forget("group_{$group->id}_show_data");
        }

        return response()->json(['success' => true, 'message' => 'Cache limpiado correctamente']);
    });
});

Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
Route::post('/actualizar-token', [PushTokenController::class, 'update']);

// Ruta pública para registrar tokens de push desde Capacitor (Web, Android, iOS)
Route::post('/push/token', [PushTokenController::class, 'update']);

// ============================================================================
// RUTAS DE PARTIDOS / CALENDARIO DE PARTIDOS (Públicas)
// ============================================================================
Route::prefix('matches')->group(function () {
    // Obtener partidos agrupados por día
    Route::get('/calendar', [MatchesController::class, 'calendar']);

    // Obtener partidos próximos (para Pre Match)
    Route::get('/upcoming', [MatchesController::class, 'upcoming']);

    // Obtener partidos de una competencia
    Route::get('/by-competition/{competitionId}', [MatchesController::class, 'byCompetition']);

    // Obtener partidos de equipos específicos
    Route::get('/by-teams', [MatchesController::class, 'byTeams']);

    // Obtener lista de competencias disponibles
    Route::get('/competitions', [MatchesController::class, 'competitions']);

    // Obtener lista de equipos disponibles
    Route::get('/teams', [MatchesController::class, 'teams']);

    // Obtener estadísticas de partidos
    Route::get('/statistics', [MatchesController::class, 'statistics']);
});

// ============================================================================
// RUTAS DE SINCRONIZACIÓN (Requiere autenticación y permisos de admin)
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/matches/sync', [MatchesController::class, 'sync']);

    // ========================================================================
    // RUTAS PRE MATCH - Desafíos anticipados en partidos
    // ========================================================================
    Route::prefix('pre-matches')->group(function () {
        // Listar Pre Matches (filtrar por grupo, estado)
        Route::get('/', [\App\Http\Controllers\Api\PreMatchController::class, 'index']);

        // Crear nuevo Pre Match (Admin only)
        Route::post('/', [\App\Http\Controllers\Api\PreMatchController::class, 'store']);

        // Obtener detalles Pre Match
        Route::get('/{preMatch}', [\App\Http\Controllers\Api\PreMatchController::class, 'show']);

        // Actualizar Pre Match (Admin)
        Route::patch('/{preMatch}', [\App\Http\Controllers\Api\PreMatchController::class, 'update']);

        // Agregar proposición de acción
        Route::post('/{preMatch}/propositions', [\App\Http\Controllers\Api\PreMatchController::class, 'addProposition']);

        // Obtener penalizaciones asociadas
        Route::get('/{preMatch}/penalties', [\App\Http\Controllers\Api\PreMatchController::class, 'getPenalties']);

        // Resolver Pre Match (Admin)
        Route::post('/{preMatch}/resolve', [\App\Http\Controllers\Api\PreMatchController::class, 'resolvePreMatch']);
    });

    // Pre Match Propositions - Votos en proposiciones
    Route::prefix('pre-match-propositions')->group(function () {
        Route::post('/{proposition}/vote', [\App\Http\Controllers\Api\PreMatchController::class, 'voteOnProposition']);
    });

    // Action Templates - Sugerencias de acciones
    Route::prefix('action-templates')->group(function () {
        Route::get('/', [\App\Http\Controllers\ActionTemplateController::class, 'index']);
        Route::get('/random', [\App\Http\Controllers\ActionTemplateController::class, 'random']);
        Route::get('/category/{category}', [\App\Http\Controllers\ActionTemplateController::class, 'byCategory']);
    });

    // Penalties - Historial de castigos (Admin)
    Route::prefix('penalties')->group(function () {
        Route::patch('/{penalty}/fulfill', [\App\Http\Controllers\Api\PreMatchController::class, 'markPenaltyFulfilled']);
    });
});
