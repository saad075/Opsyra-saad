<?php

use App\Modules\Payroll\Http\Controllers\PayComponentController;
use App\Modules\Payroll\Http\Controllers\PayRunController;
use App\Modules\Payroll\Http\Controllers\PayslipController;
use App\Modules\Payroll\Http\Controllers\SalaryAdjustmentController;
use App\Modules\Payroll\Http\Controllers\SalaryClaimController;
use App\Modules\Payroll\Http\Controllers\StaffBankAccountController;
use App\Modules\Payroll\Http\Controllers\StaffSalaryController;
use Illuminate\Support\Facades\Route;

// ResolveTenantFromUser already runs for every /api route (see
// bootstrap/app.php) — only auth:sanctum needs adding here. Paths are new:
// SLMS360's salary routes mixed setup, runs and self-service under one
// prefix, and its fixed-column shape doesn't carry over.
Route::middleware('auth:sanctum')->prefix('payroll')->group(function () {
    Route::prefix('pay-components')->group(function () {
        Route::get('list', [PayComponentController::class, 'index']);
        Route::post('create', [PayComponentController::class, 'store']);
        Route::post('update', [PayComponentController::class, 'update']);
        Route::post('delete', [PayComponentController::class, 'destroy']);
    });

    Route::prefix('salaries')->group(function () {
        Route::post('set', [StaffSalaryController::class, 'set']);
        Route::post('history', [StaffSalaryController::class, 'history']);
        Route::post('current', [StaffSalaryController::class, 'current']);
        Route::get('mine', [StaffSalaryController::class, 'mine']);
    });

    Route::prefix('bank-accounts')->group(function () {
        Route::post('show', [StaffBankAccountController::class, 'show']);
        Route::post('save', [StaffBankAccountController::class, 'save']);
        Route::get('mine', [StaffBankAccountController::class, 'mine']);
    });

    Route::prefix('adjustments')->group(function () {
        Route::post('list', [SalaryAdjustmentController::class, 'index']);
        Route::post('create', [SalaryAdjustmentController::class, 'store']);
        Route::post('delete', [SalaryAdjustmentController::class, 'destroy']);
    });

    Route::prefix('pay-runs')->group(function () {
        Route::get('list', [PayRunController::class, 'index']);
        Route::post('create', [PayRunController::class, 'store']);
        Route::post('generate', [PayRunController::class, 'generate']);
    });

    Route::prefix('payslips')->group(function () {
        Route::post('list', [PayslipController::class, 'index']);
        Route::post('show', [PayslipController::class, 'show']);
        Route::get('mine', [PayslipController::class, 'mine']);
        Route::post('regenerate', [PayslipController::class, 'regenerate']);
        Route::post('approve', [PayslipController::class, 'approve']);
        Route::post('carry-forward', [PayslipController::class, 'carryForward']);
        Route::get('pending-cheques', [PayslipController::class, 'pendingCheques']);
        Route::post('cheque-number', [PayslipController::class, 'saveCheque']);
    });

    Route::prefix('claims')->group(function () {
        Route::get('list', [SalaryClaimController::class, 'index']);
        Route::get('mine', [SalaryClaimController::class, 'mine']);
        Route::post('create', [SalaryClaimController::class, 'store']);
        Route::post('approve', [SalaryClaimController::class, 'approve']);
        Route::post('reject', [SalaryClaimController::class, 'reject']);
    });
});
