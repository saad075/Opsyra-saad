<?php

use App\Modules\Setup\Http\Controllers\CompanySettingsController;
use App\Modules\Setup\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // SLMS360: GET setup-wizard/status.
    Route::prefix('setup-wizard')->group(function () {
        Route::get('status', [SetupController::class, 'status']);
        Route::post('skip', [SetupController::class, 'skip']);
        Route::post('mark-reviewed', [SetupController::class, 'markReviewed']);
        Route::post('reset', [SetupController::class, 'reset']);
    });

    Route::prefix('company-settings')->group(function () {
        Route::get('show', [CompanySettingsController::class, 'show']);
        Route::post('update', [CompanySettingsController::class, 'update']);
    });
});
