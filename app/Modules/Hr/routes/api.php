<?php

use App\Modules\Hr\Http\Controllers\DepartmentController;
use App\Modules\Hr\Http\Controllers\StaffController;
use App\Modules\Hr\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// ResolveTenantFromUser already runs for every /api route (see
// bootstrap/app.php) — only auth:sanctum needs adding here.
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('departments')->group(function () {
        Route::get('list', [DepartmentController::class, 'index']);
        Route::post('create', [DepartmentController::class, 'store']);
        Route::post('update', [DepartmentController::class, 'update']);
        Route::post('delete', [DepartmentController::class, 'destroy']);
    });

    Route::prefix('designations-management')->group(function () {
        Route::get('list', [DepartmentController::class, 'designationsList']);
        Route::post('create', [DepartmentController::class, 'storeDesignation']);
        Route::post('update', [DepartmentController::class, 'updateDesignation']);
        Route::post('delete', [DepartmentController::class, 'destroyDesignation']);
    });

    Route::prefix('staff')->group(function () {
        Route::post('create', [StaffController::class, 'create']);
        Route::post('update', [StaffController::class, 'update']);
        Route::post('list', [StaffController::class, 'list']);
        Route::post('index', [StaffController::class, 'index']);
        Route::post('limited-list', [StaffController::class, 'limitedList']);
        Route::post('status-update', [StaffController::class, 'statusUpdate']);
        Route::get('eligible-managers', [StaffController::class, 'eligibleManagers']);
        Route::post('active-direct-reports', [StaffController::class, 'activeDirectReports']);
        Route::post('update-avatar', [StaffController::class, 'updateAvatar']);
        Route::post('clear-device-id', [StaffController::class, 'clearDeviceId']);
    });

    Route::prefix('teams')->group(function () {
        Route::get('list', [TeamController::class, 'index']);
        Route::post('members', [TeamController::class, 'members']);
    });
});
