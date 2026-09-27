<?php

use App\Modules\Attendance\Http\Controllers\AttendanceCorrectionController;
use App\Modules\Attendance\Http\Controllers\AttendanceDashboardController;
use App\Modules\Attendance\Http\Controllers\AttendanceSettingsController;
use App\Modules\Attendance\Http\Controllers\AttendanceSheetController;
use App\Modules\Attendance\Http\Controllers\HolidayController;
use App\Modules\Attendance\Http\Controllers\MyAttendanceController;
use App\Modules\Attendance\Http\Controllers\MyVisitsController;
use App\Modules\Attendance\Http\Controllers\VisitBoardController;
use App\Modules\Attendance\Http\Controllers\VisitLocationController;
use App\Modules\Attendance\Http\Controllers\VisitPlanController;
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
    Route::get('dashboard', AttendanceDashboardController::class);
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
        Route::get('people', [WorkScheduleController::class, 'people']);
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

// Visits: staff who work at clients' homes (home care). The first group is
// the caregiver app (own visits only); the rest is for coordinators.
Route::middleware('auth:sanctum')->prefix('visits')->group(function () {
    Route::get('my-day', [MyVisitsController::class, 'day']);
    Route::post('check-in', [MyVisitsController::class, 'checkIn']);
    Route::post('check-out', [MyVisitsController::class, 'checkOut']);
    Route::get('my-month', [MyVisitsController::class, 'month']);

    Route::get('day', [VisitBoardController::class, 'day']);
    Route::get('caregivers', [VisitBoardController::class, 'caregivers']);
    Route::post('create', [VisitBoardController::class, 'store']);
    Route::post('update', [VisitBoardController::class, 'update']);
    Route::post('cancel', [VisitBoardController::class, 'cancel']);
    Route::post('set-times', [VisitBoardController::class, 'setTimes']);
    Route::get('review-queue', [VisitBoardController::class, 'reviewQueue']);
    Route::post('review', [VisitBoardController::class, 'review']);

    Route::prefix('locations')->group(function () {
        Route::get('list', [VisitLocationController::class, 'index']);
        Route::post('create', [VisitLocationController::class, 'store']);
        Route::post('update', [VisitLocationController::class, 'update']);
        Route::post('delete', [VisitLocationController::class, 'destroy']);
    });

    Route::prefix('plans')->group(function () {
        Route::get('list', [VisitPlanController::class, 'index']);
        Route::post('create', [VisitPlanController::class, 'store']);
        Route::post('update', [VisitPlanController::class, 'update']);
        Route::post('delete', [VisitPlanController::class, 'destroy']);
    });
});
