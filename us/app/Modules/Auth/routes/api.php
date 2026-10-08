<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\LoginLogController;
use App\Modules\Auth\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Per-IP backstop on top of login()'s own per-account limiter.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->name('auth.forgot-password');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('auth.reset-password');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
    });
});

Route::middleware('auth:sanctum')->get('login-logs/list', [LoginLogController::class, 'index']);
