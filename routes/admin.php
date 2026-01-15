<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\QuestionAdminController;
use App\Http\Controllers\Admin\TemplateQuestionController;
use App\Http\Controllers\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\VerificationDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/verification-dashboard', [VerificationDashboardController::class, 'index'])
        ->name('verification-dashboard');
    Route::get('/verification-dashboard/data', [VerificationDashboardController::class, 'data'])
        ->name('verification-dashboard.data');

    // Questions Management
    Route::resource('questions', QuestionAdminController::class)->except(['show']);
    Route::post('questions/{question}/toggle-featured', [QuestionAdminController::class, 'toggleFeatured'])
        ->name('questions.toggle-featured');

    // Template Questions Management

    // Route::get('/competitions/{competition}/teams', [AdminCompetitionController::class, 'getTeams'])->name('competitions.teams');
    // Route::get('/competitions/{competition}/matches', [AdminCompetitionController::class, 'getMatches'])->name('competitions.matches');
    // Route::get('/matches/{match}', [AdminCompetitionController::class, 'getMatch'])->name('matches.show');
});
