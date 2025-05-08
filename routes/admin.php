<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\QuestionAdminController;
use App\Http\Controllers\Admin\TemplateQuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    
    // Questions Management
    Route::resource('questions', QuestionAdminController::class)->except(['show']);
    Route::post('questions/{question}/toggle-featured', [QuestionAdminController::class, 'toggleFeatured'])
        ->name('questions.toggle-featured');
    
    // Template Questions Management
    Route::resource('template-questions', TemplateQuestionController::class)->except(['show']);
});
