<?php

use App\Modules\Attendance\Http\Controllers\AttendanceCorrectionController;
use App\Modules\Attendance\Http\Controllers\AttendanceSettingsController;
use App\Modules\Attendance\Http\Controllers\AttendanceSheetController;
use App\Modules\Attendance\Http\Controllers\HolidayController;
use App\Modules\Attendance\Http\Controllers\MyAttendanceController;
use App\Modules\Attendance\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

// ResolveTenantFromUser already runs for every /api route (see
// bootstrap/app.php) — only auth:sanctum needs adding here.
Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    // The staff app (SLMS360's attendance/today, mark, current-month,
    // previous-month, shift/my-shift) — always the signed-in person's own.
    Route::get('today', [MyAttendanceController::class, 'today']);
    Route::post('clock', [MyAttendanceController::class, 'clock']);
    Route::get('month', [MyAttendanceController::class, 'month']);
    Route::get('my-schedule', [MyAttendanceController::class, 'schedule']);

    Route::prefix('corrections')->group(function () {
        Route::post('create', [AttendanceCorrectionController::class, 'store']);
        Route::get('mine', [AttendanceCorrectionController::class, 'mine']);
        Route::get('list', [AttendanceCorrectionController::class, 'index']);
        Route::post('approve', [AttendanceCorrectionController::class, 'approve']);
        Route::post('reject', [AttendanceCorrectionController::class, 'reject']);
    });

    // HR.
    Route::get('daily', [AttendanceSheetController::class, 'daily']);
    Route::post('person-month', [AttendanceSheetController::class, 'personMonth']);
    Route::post('mark', [AttendanceSheetController::class, 'mark']);

    Route::prefix('schedules')->group(function () {
        Route::get('list', [WorkScheduleController::class, 'index']);
        Route::post('create', [WorkScheduleController::class, 'store']);
        Route::post('update', [WorkScheduleController::class, 'update']);
        Route::post('delete', [WorkScheduleController::class, 'destroy']);
        Route::post('assign', [WorkScheduleController::class, 'assign']);
        Route::get('assignments', [WorkScheduleController::class, 'assignments']);
    });

    Route::prefix('holidays')->group(function () {
        Route::get('list', [HolidayController::class, 'index']);
        Route::post('create', [HolidayController::class, 'store']);
        Route::post('update', [HolidayController::class, 'update']);
        Route::post('delete', [HolidayController::class, 'destroy']);
    });

    Route::get('policy', [AttendanceSettingsController::class, 'policy']);
    Route::post('policy/update', [AttendanceSettingsController::class, 'updatePolicy']);
    Route::post('staff-mode', [AttendanceSettingsController::class, 'setMode']);
});
