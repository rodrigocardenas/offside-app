<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\TemplateQuestionController;
use App\Http\Controllers\TestAvatarController;

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

Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Página principal (grupos)
    Route::get('/', [GroupController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Grupos
    Route::resource('groups', GroupController::class);
    Route::post('groups/join', [GroupController::class, 'join'])->name('groups.join');
    Route::delete('groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    Route::get('groups/{group}/predictive-results', [GroupController::class, 'showPredictiveResults'])->name('groups.predictive-results');

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

    // Rutas de perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('competitions', CompetitionController::class);

    Route::get('/groups/invite/{code}', [GroupController::class, 'joinByInvite'])->name('groups.invite');

    Route::get('/test-questions', [GroupController::class, 'testGenerateQuestions'])->middleware(['auth']);

    // Handle question reactions (like/dislike)
    Route::post('/questions/{question}/react', [QuestionController::class, 'react'])->name('questions.react');

    // Update reward or penalty
    Route::post('/groups/{group}/reward-or-penalty', [\App\Http\Controllers\GroupController::class, 'updateRewardOrPenalty'])->name('groups.updateRewardOrPenalty');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
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
    $path = storage_path('app/public/avatars/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file, 200)
        ->header('Content-Type', $type)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '.*');

// Ruta de prueba para avatares
Route::post('/test-avatar-upload', [TestAvatarController::class, 'testUpload']);
Route::get('/test-avatar', function() {
    return view('test-avatar');
});

