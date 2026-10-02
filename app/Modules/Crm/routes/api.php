<?php

use App\Modules\Crm\Http\Controllers\LeadController;
use App\Modules\Crm\Http\Controllers\LeadPipelineController;
use App\Modules\Crm\Http\Controllers\LeadSourceController;
use App\Modules\Crm\Http\Controllers\LeadTypeController;
use Illuminate\Support\Facades\Route;

// ResolveTenantFromUser already runs for every /api route (see
// bootstrap/app.php) — only auth:sanctum needs adding here.
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('lead-sources')->group(function () {
        Route::get('list', [LeadSourceController::class, 'index']);
        Route::post('create', [LeadSourceController::class, 'store']);
        Route::post('update', [LeadSourceController::class, 'update']);
        Route::post('delete', [LeadSourceController::class, 'destroy']);
    });

    Route::prefix('lead-types')->group(function () {
        Route::get('list', [LeadTypeController::class, 'index']);
        Route::post('create', [LeadTypeController::class, 'store']);
        Route::post('update', [LeadTypeController::class, 'update']);
        Route::post('delete', [LeadTypeController::class, 'destroy']);
    });

    Route::prefix('lead-pipelines')->group(function () {
        Route::get('list', [LeadPipelineController::class, 'index']);
        Route::post('create', [LeadPipelineController::class, 'store']);
        Route::post('update', [LeadPipelineController::class, 'update']);
        Route::post('delete', [LeadPipelineController::class, 'destroy']);
        Route::post('stages/create', [LeadPipelineController::class, 'storeStage']);
        Route::post('stages/update', [LeadPipelineController::class, 'updateStage']);
        Route::post('stages/delete', [LeadPipelineController::class, 'destroyStage']);
    });

    Route::prefix('leads')->group(function () {
        Route::get('list', [LeadController::class, 'index']);
        Route::post('show', [LeadController::class, 'show']);
        Route::post('create', [LeadController::class, 'store']);
        Route::post('update', [LeadController::class, 'update']);
        Route::post('move', [LeadController::class, 'move']);
        Route::post('delete', [LeadController::class, 'destroy']);
    });
});
