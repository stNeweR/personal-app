<?php

use App\Modules\Plugin\Infrastructure\Http\V1\Controllers\PluginController;
use App\Modules\Pomodoro\Infrastructure\Http\V1\Controllers\PomodoroController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\AuthController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\NotificationController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('telegram-link-token', [AuthController::class, 'telegramLinkToken']);
        });
    });

    Route::middleware('auth:sanctum')->prefix('pomodoro')->group(function () {
        Route::get('sessions', [PomodoroController::class, 'today']);
        Route::get('sessions/active', [PomodoroController::class, 'activeSession']);
        Route::get('settings', [PomodoroController::class, 'getSettings']);
        Route::post('settings', [PomodoroController::class, 'saveSettings']);
        Route::post('sessions', [PomodoroController::class, 'createSession']);
        Route::patch('sessions/{id}', [PomodoroController::class, 'updateSession']);
        Route::delete('sessions/{id}', [PomodoroController::class, 'deleteSession']);
    });

    Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
        Route::get('status', [NotificationController::class, 'status']);
        Route::put('channel', [NotificationController::class, 'updateChannel']);
        Route::put('email', [NotificationController::class, 'updateEmail']);
        Route::post('email/send-verification', [NotificationController::class, 'sendEmailVerification']);
    });

    Route::get('notifications/email/verify', [NotificationController::class, 'verifyEmail'])
        ->name('api.v1.notifications.email.verify');

    Route::middleware('auth:sanctum')->prefix('plugins')->group(function () {
        Route::get('/', [PluginController::class, 'index']);
        Route::get('enabled', [PluginController::class, 'getEnabledManifests']);
        Route::post('{name}/enable', [PluginController::class, 'enable']);
        Route::post('{name}/disable', [PluginController::class, 'disable']);
    });

    Route::middleware('auth:sanctum')->prefix('user')->group(function () {
        Route::put('plan', [UserController::class, 'updatePlan']);
    });
});
