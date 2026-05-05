<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\MobileOAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\TemplateQuestionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TestAvatarController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MatchesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rutas públicas sin autenticación
Route::get('/.well-known/assetlinks.json', function () {
    $filePath = public_path('.well-known/assetlinks.json');
    if (!file_exists($filePath)) {
        abort(404);
    }
    $content = file_get_contents($filePath);
    return response($content, 200, [
        'Content-Type' => 'application/json',
        'Access-Control-Allow-Origin' => '*'
    ]);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

// Ruta alternativa: /assetlinks.json (para Android App Links como fallback)
Route::get('/assetlinks.json', function () {
    $filePath = public_path('.well-known/assetlinks.json');
    if (!file_exists($filePath)) {
        abort(404);
    }
    $content = file_get_contents($filePath);
    return response($content, 200, [
        'Content-Type' => 'application/json',
        'Access-Control-Allow-Origin' => '*'
    ]);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('rate-limit-users');
});

// Google OAuth Routes (accesibles para guests y autenticados)
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Mobile OAuth Routes (para Capacitor - sin CSRF)
Route::post('/api/auth/mobile/google-login', [MobileOAuthController::class, 'mobileGoogleLogin'])->name('api.mobile.google.login')->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::get('/api/auth/mobile/google-url', [MobileOAuthController::class, 'getGoogleAuthUrl'])->name('api.mobile.google.url');

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Página principal (grupos)
    Route::get('/', [GroupController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Calendario de Partidos
    Route::get('/matches/calendar', [MatchesController::class, 'view'])->name('matches.calendar');

    // Grupos
    Route::resource('groups', GroupController::class);
    Route::post('groups/join', [GroupController::class, 'join'])->name('groups.join');
    Route::delete('groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    Route::get('groups/by-match/{matchId}', [GroupController::class, 'getGroupsByMatch']);
    Route::get('groups/{group}/ranking-quiz', [GroupController::class, 'showQuizRanking'])->name('groups.ranking-quiz');  // 🎮 Quiz Ranking View
    Route::get('groups/{group}/quiz-ranking', [GroupController::class, 'getQuizRanking'])->name('groups.quiz-ranking');  // 🎮 Quiz Ranking API
    Route::get('groups/{group}/pre-matches', [GroupController::class, 'showPreMatches'])->name('groups.pre-matches');  // 🔥 Pre Matches
    Route::get('groups/{group}/pre-matches/{preMatch}', [GroupController::class, 'showPreMatchDetail'])->name('groups.pre-matches.show');  // 🔥 Ver detalle
    Route::get('groups/{group}/summary', [\App\Http\Controllers\GroupSummaryController::class, 'show'])->name('groups.summary');  // 📊 Group Summary

    // Preguntas
    Route::resource('questions', QuestionController::class);
    Route::post('questions/{question}/answer', [QuestionController::class, 'answer'])->name('questions.answer');
    Route::get('questions/{question}/results', [QuestionController::class, 'results'])->name('questions.results');

    // Feedback
    Route::post('feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');

    // Chat
    Route::post('/groups/{group}/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::post('/groups/{group}/chat/mark-as-read', [ChatController::class, 'markAsRead'])->name('chat.mark-as-read');
    Route::get('/groups/{group}/chat/unread-count', [ChatController::class, 'getUnreadCount'])->name('chat.unread-count');

    // Rankings
    Route::get('groups/{group}/ranking', [RankingController::class, 'groupRanking'])->name('rankings.group');
    Route::get('rankings/daily', [RankingController::class, 'dailyRanking'])->name('rankings.daily');
    Route::get('questions/{question}/ranking', [RankingController::class, 'questionRanking'])->name('rankings.question');
    Route::get('users/{user}/stats', [RankingController::class, 'userStats'])->name('rankings.user-stats');

    // Market
    Route::get('market', [MarketController::class, 'index'])->name('market.index');
    Route::get('market/{id}', [MarketController::class, 'show'])->name('market.show');

    // Rutas de perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/clubs/{competitionId}', [ProfileController::class, 'getClubsByCompetition'])->name('profile.clubs');
    Route::get('/profile/national-teams', [ProfileController::class, 'getNationalTeams'])->name('profile.national-teams');

    // Rutas de configuración
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::resource('competitions', CompetitionController::class);

    Route::get('/groups/invite/{code}', [GroupController::class, 'joinByInvite'])->name('groups.invite');
    Route::get('/invite/{code}', [GroupController::class, 'joinByInvite'])->name('invite'); // Atajo para deep links

    Route::get('/test-questions', [GroupController::class, 'testGenerateQuestions'])->middleware(['auth']);

    // Handle question reactions (like/dislike)
    Route::post('/questions/{question}/react', [QuestionController::class, 'react'])->name('questions.react');

    // Update reward or penalty
    Route::post('/groups/{group}/reward-or-penalty', [\App\Http\Controllers\GroupController::class, 'updateRewardOrPenalty'])->name('groups.updateRewardOrPenalty');

    // API endpoints for modal
    Route::get('groups/by-match/{matchId}', [GroupController::class, 'getGroupsByMatch']);
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/cloudflare-images', [AdminController::class, 'cloudflareImagesDashboard'])->name('cloudflare-dashboard');
    Route::get('/competitions/{competition}/teams', [AdminCompetitionController::class, 'getTeams'])->name('competitions.teams');
    Route::get('/competitions/{competition}/matches', [AdminCompetitionController::class, 'getMatches'])->name('competitions.matches');
    Route::get('/matches/{match}', [AdminCompetitionController::class, 'getMatch'])->name('matches.show');
    Route::resource('template-questions', TemplateQuestionController::class);
});

Route::get('/test-match-verification', function() {
    $service = new \App\Services\OpenAIService();
    $result = $service->testRealMatchVerification();
    return response()->json($result);
});

Route::get('/test-goles-partido', function() {
    $service = app(\App\Services\FootballService::class);
    $fixtureId = $service->buscarFixtureId('premier-league', 2024, 'Manchester United', 'Liverpool');
    if ($fixtureId) {
        $goles = $service->obtenerGolesPartido($fixtureId);
        return response()->json($goles);
    } else {
        return response()->json(['error' => 'No se encontró el partido.'], 404);
    }
});

Route::get('/test-actualizar-partido/{id}', function($id) {
    $service = app(\App\Services\FootballService::class);
    $match = $service->updateMatchFromApi($id);
    if ($match) {
        return response()->json($match);
    } else {
        return response()->json(['error' => 'No se pudo actualizar el partido.'], 404);
    }
});

// Ruta para servir avatares
Route::get('/avatars/{filename}', function ($filename) {
    // 1. WHITELIST CHECK: Only allow safe filenames
    // Pattern: alphanumeric, dot, dash, underscore (max 255 chars)
    if (!preg_match('/^[a-zA-Z0-9._-]{1,255}$/', $filename)) {
        abort(403, 'Invalid filename format');
    }

    // 2. SAFE PATH CONSTRUCTION
    $basePath = storage_path('app/public/avatars');
    $path = $basePath . DIRECTORY_SEPARATOR . $filename;

    // 3. PATH VALIDATION: Ensure path is within avatars directory
    // This prevents directory traversal even if filename validation fails
    $realPath = realpath($path);
    $realBasePath = realpath($basePath);

    if (!$realPath || !$realBasePath || strpos($realPath, $realBasePath) !== 0) {
        abort(403, 'Access denied');
    }

    // 4. FILE EXISTENCE CHECK
    if (!file_exists($realPath) || !is_file($realPath)) {
        abort(404, 'Avatar not found');
    }

    // 5. FILE TYPE VALIDATION (optional but recommended)
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime = mime_content_type($realPath);
    if (!in_array($mime, $allowed_mimes)) {
        abort(403, 'Invalid file type');
    }

    // 6. SAFE FILE DELIVERY
    $file = file_get_contents($realPath);

    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000')
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('Content-Disposition', 'inline; filename="' . basename($realPath) . '"');
})->where('filename', '[a-zA-Z0-9._-]{1,255}');

// Ruta de prueba para avatares
Route::post('/test-avatar-upload', [TestAvatarController::class, 'testUpload']);
Route::get('/test-avatar', function() {
    return view('test-avatar');
});

// Ruta para limpiar cache del service worker
Route::get('/clear-cache', function() {
    return view('clear-cache');
})->name('clear-cache');

// 🌍 Sincronización de Timezone - Ruta protegida por autenticación web
Route::middleware('auth')->post('/timezone/sync', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'timezone' => 'required|string|timezone',
    ]);

    $user = $request->user();
    $oldTimezone = $user->timezone;

    $updated = $user->update([
        'timezone' => $request->timezone,
    ]);

    if ($updated) {
        \Illuminate\Support\Facades\Log::info("✅ Timezone actualizado para usuario {$user->id} ({$user->name}): {$oldTimezone} → {$request->timezone}");
    } else {
        \Illuminate\Support\Facades\Log::error("❌ Error al actualizar timezone para usuario {$user->id}");
    }

    return response()->json([
        'success' => $updated,
        'message' => $updated ? 'Zona horaria actualizada correctamente' : 'Error al actualizar',
        'timezone' => $request->timezone,
        'previous_timezone' => $oldTimezone,
        'synced_at' => now()->toIso8601String(),
    ]);
})->name('timezone.sync');

// ============ DEBUG ROUTE FOR PREMATCH MODAL ============
Route::get('/debug/test-prematch', function () {
    try {
        echo "<h2>🧪 Pre Match Modal Test</h2>";

        // Get a match
        $match = \App\Models\Match::orderBy('id', 'desc')->first();

        if (!$match) {
            return "❌ No matches found";
        }

        echo "<p>✅ Match found: {$match->id} ({$match->home_team->name} vs {$match->away_team->name})</p>";

        // Create Pre Match
        $pm = \App\Models\PreMatch::create([
            'match_id' => $match->id,
            'group_id' => 12,
            'created_by' => 1,
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
            'status' => 'pending'
        ]);

        echo "<p>✅ Pre Match created with ID: {$pm->id}</p>";
        echo "<p>   - match_id in DB: {$pm->match_id}</p>";

        // Verify
        $verify = \App\Models\PreMatch::find($pm->id);
        if ($verify && $verify->match_id == $match->id) {
            echo "<p><strong style='color:green'>✅ VERIFICATION PASSED</strong></p>";
            echo "<p>Pre Match is correctly stored with match_id = {$pm->match_id}</p>";
        } else {
            echo "<p><strong style='color:red'>❌ VERIFICATION FAILED</strong></p>";
        }

        echo "<pre>" . json_encode($pm->toArray(), JSON_PRETTY_PRINT) . "</pre>";

    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
});

Route::get('/debug/modal', function () {
    return view('components.modals.debug-prematch-modal');
});

Route::get('/debug/modal-test', function () {
    return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Pre Match Modal</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 20px; background: #f5f5f5; }
        button { padding: 10px 20px; margin: 10px 0; cursor: pointer; font-size: 16px; background: #007bff; color: white; border: none; border-radius: 4px; }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: slideUp 0.3s ease;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            padding: 24px;
        }
        input[type="text"], .results {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 8px 0;
        }
        .results { max-height: 150px; overflow-y: auto; background: #f9f9f9; }
        .match-item { padding: 8px; border-bottom: 1px solid #ddd; cursor: pointer; }
        .match-item:hover { background: #e9f5ff; }
        .display { padding: 12px; border: 1px solid #00deb0; border-radius: 4px; margin: 12px 0; background: #e5f3f0; display: none; }
        .status-box { padding: 12px; background: #f0f0f0; border-radius: 4px; margin: 12px 0; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <h1>🧪 Debug Pre Match Modal Test</h1>
    <p>Click "Open Modal" below, then:</p>
    <ol>
        <li>Type "Real" or "Liverpool" in search box</li>
        <li>Click a match</li>
        <li>Click Submit button</li>
        <li>Check browser console (F12) for logs</li>
    </ol>
    <button onclick="openModal()">Open Modal</button>
    <button onclick="alert('Check browser console (press F12) for detailed logs and values')">📋 Check Console (F12)</button>

    <div id="myModal" class="modal">
        <div class="modal-content">
            <h2>🧪 DEBUG Modal</h2>
            <input type="text" id="searchInput" placeholder="Type to search...">
            <div id="results" class="results"></div>
            <div id="display" class="display"></div>
            <div class="status-box">
                Hidden Input Value: <strong id="statusValue">(empty)</strong>
            </div>
            <div>
                <input type="hidden" id="matchInput" value="">
                <button onclick="submitForm()">Submit</button>
                <button onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const matches = [
            { id: 1, home: 'Real Madrid', away: 'Barcelona', time: '19:00', comp: 'La Liga' },
            { id: 2, home: 'Liverpool', away: 'Manchester', time: '16:00', comp: 'Premier League' },
            { id: 3, home: 'PSG', away: 'Monaco', time: '20:00', comp: 'Ligue 1' }
        ];

        function openModal() {
            console.log('%c🎬 OPENING MODAL', 'color: blue; font-weight: bold; font-size: 14px');
            document.getElementById('myModal').style.display = 'flex';
            initSearch();
        }

        function closeModal() {
            document.getElementById('myModal').style.display = 'none';
        }

        function initSearch() {
            console.log('%c🔍 INITIALIZING SEARCH', 'color: green; font-weight: bold; font-size: 14px');
            const input = document.getElementById('searchInput');
            input.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const resultsDiv = document.getElementById('results');

                if (!query) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                const filtered = matches.filter(m => {
                    const text = `${m.home} ${m.away} ${m.comp}`.toLowerCase();
                    return text.includes(query);
                });

                console.log(`%cFiltered: ${filtered.length} matches`, 'color: gray');

                if (filtered.length === 0) {
                    resultsDiv.innerHTML = '<div style="padding: 8px;">No matches</div>';
                    resultsDiv.style.display = 'block';
                    return;
                }

                resultsDiv.innerHTML = filtered.map(m => `
                    <div class="match-item" onclick="selectMatch(${m.id}, '${m.home}', '${m.away}', '${m.time}')">
                        <strong>${m.home} vs ${m.away}</strong><br>
                        <small>${m.time} · ${m.comp}</small>
                    </div>
                `).join('');

                resultsDiv.style.display = 'block';
            });
        }

        window.selectMatch = function(matchId, home, away, time) {
            console.log('%c✅ MATCH SELECTED', 'color: orange; font-weight: bold; font-size: 14px');
            console.log('matchId:', matchId, '| type:', typeof matchId);

            const input = document.getElementById('matchInput');
            const display = document.getElementById('display');
            const status = document.getElementById('statusValue');

            input.value = matchId;
            console.log('Set input.value to:', input.value);
            console.log('Confirmed .value is:', input.value);

            display.textContent = `✅ ${home} vs ${away} (${time})`;
            display.style.display = 'block';
            status.textContent = input.value;

            document.getElementById('results').style.display = 'none';
            document.getElementById('searchInput').value = `${home} vs ${away}`;
        };

        window.submitForm = function() {
            console.log('%c🚀 SUBMITTING FORM', 'color: red; font-weight: bold; font-size: 14px');
            const input = document.getElementById('matchInput');
            const value = input.value;

            console.log('Getting value from input...');
            console.log('input.value:', value);
            console.log('typeof:', typeof value);
            console.log('length:', value ? value.length : 0);
            console.log('Boolean(!value):', !value);
            console.log('Boolean(!!value):', !!value);

            if (!value) {
                console.error('%c❌ SUBMISSION FAILED - Empty value!', 'color: red; font-weight: bold');
                alert('❌ ERROR: matchId is empty!\\nValue: "' + value + '"');
                return;
            }

            console.log('%c✅ SUCCESS - Value:', 'color: green; font-weight: bold', value);
            alert('✅ SUCCESS! Submitted value: ' + value);
        };
    </script>
</body>
</html>
HTML;
});

// Debug: Check matches in database
Route::get('/debug/matches-check', function () {
    $now = now();
    $endDate = $now->copy()->addDays(7);

    $matches = \App\Models\FootballMatch::whereBetween('date', [
        $now->format('Y-m-d'),
        $endDate->format('Y-m-d')
    ])
        ->orderBy('date')
        ->limit(10)
        ->with(['homeTeam', 'awayTeam', 'competition'])
        ->get();

    $allMatches = \App\Models\FootballMatch::orderByDesc('date')->limit(5)->get(['id', 'date', 'status']);
    $statuses = \App\Models\FootballMatch::distinct()->pluck('status')->toArray();

    return response()->json([
        'current_time' => $now->toIso8601String(),
        'total_matches_in_db' => \App\Models\FootballMatch::count(),
        'unique_statuses' => $statuses,
        'newest_matches' => $allMatches->map(fn($m) => ['id' => $m->id, 'date' => $m->date, 'status' => $m->status]),
        'matches_in_range' => $matches->count(),
        'matches' => $matches->map(fn($m) => [
            'id' => $m->id,
            'date' => $m->date,
            'status' => $m->status,
            'home' => $m->homeTeam?->name,
            'away' => $m->awayTeam?->name,
            'competition' => $m->competition?->name,
        ])
    ]);
});

