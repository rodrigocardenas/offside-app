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

Route::get('/competitions/{competition}/teams', function (App\Models\Competition $competition) {
    return $competition->teams()
        ->select('teams.id', 'teams.name')
        ->get();
});

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

// Ruta autenticada para registrar tokens de push desde Capacitor
Route::middleware('auth:sanctum')->post('/push/token', [PushTokenController::class, 'update']);

// ============================================================================
// RUTAS DE PARTIDOS / CALENDARIO DE PARTIDOS (Públicas)
// ============================================================================
Route::prefix('matches')->group(function () {
    // Obtener partidos agrupados por día
    Route::get('/calendar', [MatchesController::class, 'calendar']);

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
});
