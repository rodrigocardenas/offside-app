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
// RUTAS PRE MATCH - Protegidas con autenticación de sesión (cookies)
// ============================================================================
Route::middleware('auth:web')->prefix('pre-matches')->group(function () {
    // Listar Pre Matches (filtrar por grupo, estado)
    Route::get('/', [\App\Http\Controllers\Api\PreMatchController::class, 'index']);

    // Crear nuevo Pre Match
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
    Route::put('/{preMatch}/resolve', [\App\Http\Controllers\Api\PreMatchController::class, 'resolvePreMatch']);
});

// Polling endpoint para obtener nuevos eventos (fallback si SSE falla)
Route::get('/pre-matches/{preMatch}/events-poll', [\App\Http\Controllers\PreMatchEventController::class, 'poll'])
    ->middleware('auth:web')
    ->name('pre-matches.events-poll');

// Pre Match Propositions - Votos en proposiciones
Route::middleware('auth:web')->prefix('pre-match-propositions')->group(function () {
    Route::post('/{proposition}/vote', [\App\Http\Controllers\Api\PreMatchController::class, 'voteOnProposition']);
    Route::delete('/{proposition}', [\App\Http\Controllers\Api\PreMatchController::class, 'deleteProposition']);
});

// ============================================================================
Route::middleware('auth:web')->group(function () {
    Route::post('/matches/sync', [MatchesController::class, 'sync']);

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

    // ====================================================================
    // DEBUG ROUTES (Temporal - para diagnóstico de autenticación)
    // ====================================================================
    Route::get('/debug/auth-check', function (Request $request) {
        return response()->json([
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'request_expects_json' => $request->expectsJson(),
            'headers' => [
                'content-type' => $request->header('content-type'),
                'accept' => $request->header('accept'),
                'authorization' => $request->header('authorization') ? 'SET' : 'EMPTY'
            ]
        ]);
    });

    Route::post('/debug/pre-match-test', function (Request $request) {
        \Log::info('DEBUG: pre-match-test POST received', [
            'body' => $request->all(),
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'expect_json' => $request->expectsJson(),
        ]);

        return response()->json([
            'status' => 'test_success',
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'payload_received' => $request->all(),
        ], 201);
    });
});

// Endpoint público para verificación
Route::get('/debug/public-check', function () {
    return response()->json(['status' => 'public_access_ok']);
});

// Endpoint protegido de debug con auth:web
Route::middleware('auth:web')->get('/debug/protected-check', function (Request $request) {
    return response()->json([
        'authenticated' => true,
        'user_id' => auth()->id(),
        'user_email' => auth()->user()?->email,
        'message' => '✅ Session authentication is working!',
        'guard_check' => auth('web')->check(),
    ]);
});
