<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PushTokenController;

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
});
Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
Route::post('/actualizar-token', [PushTokenController::class, 'update']);
