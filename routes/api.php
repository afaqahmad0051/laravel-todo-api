<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TodoController;
use App\Http\Middleware\JwtAuthMiddleware;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(JwtAuthMiddleware::class)->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(JwtAuthMiddleware::class)->prefix('todos')->controller(TodoController::class)->group(function () {
    Route::get('', 'index');
    Route::post('', 'store');
    Route::get('{todo}', 'show');
    Route::put('{todo}', 'update');
    Route::delete('{todo}', 'destroy');
});
