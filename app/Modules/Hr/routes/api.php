<?php

use App\Modules\Hr\Http\Controllers\BranchController;
use App\Modules\Hr\Http\Controllers\ChecklistTemplateController;
use App\Modules\Hr\Http\Controllers\DepartmentController;
use App\Modules\Hr\Http\Controllers\DepartureReasonController;
use App\Modules\Hr\Http\Controllers\StaffChecklistController;
use App\Modules\Hr\Http\Controllers\StaffController;
use App\Modules\Hr\Http\Controllers\StaffOffboardingController;
use App\Modules\Hr\Http\Controllers\StaffOnboardingController;
use App\Modules\Hr\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// ResolveTenantFromUser already runs for every /api route (see
// bootstrap/app.php) — only auth:sanctum needs adding here.
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('branches')->group(function () {
        Route::get('list', [BranchController::class, 'index']);
        Route::post('create', [BranchController::class, 'store']);
        Route::post('update', [BranchController::class, 'update']);
        Route::post('delete', [BranchController::class, 'destroy']);
    });

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

        // Same paths as SLMS360's routes/client/BasicInformationRoutes.php.
        Route::post('onboard/approve', [StaffOnboardingController::class, 'approve']);
        Route::post('onboard/reject', [StaffOnboardingController::class, 'reject']);
        Route::post('onboard/complete-rejected', [StaffOnboardingController::class, 'completeRejected']);
        Route::get('onboard/pending-approvals', [StaffOnboardingController::class, 'pendingApprovals']);
        Route::get('onboard/rejected-awaiting-notification', [StaffOnboardingController::class, 'rejectedAwaitingNotification']);

        Route::post('offboard', [StaffOffboardingController::class, 'offboard']);
        Route::post('cancel-offboarding', [StaffOffboardingController::class, 'cancel']);
        Route::post('offboard/approve', [StaffOffboardingController::class, 'approve']);
        Route::post('offboard/reject', [StaffOffboardingController::class, 'reject']);
        Route::get('offboard/pending-approvals', [StaffOffboardingController::class, 'pendingApprovals']);
    });

    Route::prefix('checklist-templates')->group(function () {
        Route::get('list', [ChecklistTemplateController::class, 'index']);
        Route::post('create', [ChecklistTemplateController::class, 'store']);
        Route::post('update', [ChecklistTemplateController::class, 'update']);
        Route::post('delete', [ChecklistTemplateController::class, 'destroy']);
    });

    Route::prefix('checklists')->group(function () {
        Route::get('list', [StaffChecklistController::class, 'index']);
        Route::post('show', [StaffChecklistController::class, 'show']);
        Route::post('start', [StaffChecklistController::class, 'start']);
        Route::post('add-task', [StaffChecklistController::class, 'addTask']);
        Route::post('cancel', [StaffChecklistController::class, 'cancel']);
        Route::get('my-tasks', [StaffChecklistController::class, 'myTasks']);
        Route::post('task/update', [StaffChecklistController::class, 'updateTask']);
    });

    Route::prefix('departure-reasons')->group(function () {
        Route::get('list', [DepartureReasonController::class, 'index']);
        Route::post('create', [DepartureReasonController::class, 'store']);
        Route::post('update', [DepartureReasonController::class, 'update']);
        Route::post('delete', [DepartureReasonController::class, 'destroy']);
    });

    Route::prefix('teams')->group(function () {
        Route::get('list', [TeamController::class, 'index']);
        Route::post('members', [TeamController::class, 'members']);
    });
});
