<?php

use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeckController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StudySessionController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::delete('/me', [ProfileController::class, 'destroy']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'status']);

    Route::get('/dashboard', DashboardController::class);
    Route::get('/agenda', [AgendaController::class, 'week']);

    Route::apiResource('subjects', SubjectController::class)->except('show');
    Route::apiResource('tasks', TaskController::class)->except('show');
    Route::apiResource('sessions', StudySessionController::class)->only(['index', 'store', 'destroy'])
        ->parameters(['sessions' => 'session']);

    Route::apiResource('decks', DeckController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('/decks/{deck}/cards', [DeckController::class, 'storeCard']);
    Route::get('/decks/{deck}/review', [DeckController::class, 'review']);
    Route::delete('/cards/{card}', [DeckController::class, 'destroyCard']);
    Route::post('/cards/{card}/answer', [DeckController::class, 'answer']);
});
