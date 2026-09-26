<?php

use App\Modules\Rbac\Http\Controllers\ApproverController;
use App\Modules\Rbac\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// ResolveTenantFromUser already runs for every /api route (see
// bootstrap/app.php) — only auth:sanctum needs adding here.
Route::middleware('auth:sanctum')->prefix('roles')->group(function () {
    Route::get('list', [RoleController::class, 'index']);
    Route::get('permissions', [RoleController::class, 'permissions']);
    Route::post('create', [RoleController::class, 'store']);
    Route::post('update', [RoleController::class, 'update']);
    Route::post('delete', [RoleController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('approvers')->group(function () {
    Route::get('list', [ApproverController::class, 'index']);
    Route::post('set', [ApproverController::class, 'set']);
});
