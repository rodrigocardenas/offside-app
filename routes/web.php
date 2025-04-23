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

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    // Página principal (grupos)
    Route::get('/', [GroupController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Grupos
    Route::resource('groups', GroupController::class);
    Route::post('groups/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');

    // Preguntas
    Route::resource('questions', QuestionController::class);
    Route::post('questions/{question}/answer', [QuestionController::class, 'answer'])->name('questions.answer');
    Route::get('questions/{question}/results', [QuestionController::class, 'results'])->name('questions.results');

    // Chat
    Route::post('groups/{group}/chat', [ChatController::class, 'store'])->name('chat.store');

    // Rankings
    Route::get('groups/{group}/ranking', [RankingController::class, 'groupRanking'])->name('rankings.group');
    Route::get('rankings/daily', [RankingController::class, 'dailyRanking'])->name('rankings.daily');
    Route::get('questions/{question}/ranking', [RankingController::class, 'questionRanking'])->name('rankings.question');
    Route::get('users/{user}/stats', [RankingController::class, 'userStats'])->name('rankings.user-stats');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('competitions', CompetitionController::class);

    Route::get('/groups/invite/{code}', [GroupController::class, 'joinByInvite'])->name('groups.invite');
});

