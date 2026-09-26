<?php

namespace App\Modules\Attendance;

use App\Models\User;
use App\Modules\Attendance\Console\CloseOutAttendanceCommand;
use App\Modules\Attendance\Console\GenerateVisitsCommand;
use App\Modules\Attendance\Models\AttendanceCorrection;
use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Attendance\Setup\AttendanceSetupSteps;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Contracts\SetupStepProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Attendance: working schedules, holidays, clocking in and out (app and
 * web), settling each day, and correction requests. See
 * docs/research/attendance.md for the design and what changed from SLMS360.
 */
class AttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(AttendanceSetupSteps::class, SetupStepProvider::TAG);
    }

    public function boot(): void
    {
        Gate::define('manage-attendance', fn (User $user) => $user->can('HRMS-AttendanceManagement'));

        Gate::define('plan-visits', fn (User $user) => $user->can('HRMS-VisitPlanning'));

        // HR, or the person's own manager — never the person themselves.
        Gate::define('resolve-attendance-correction', fn (User $user, AttendanceCorrection $correction) => $correction->tenant_id === $user->tenant_id
            && $correction->user_id !== $user->id
            && ($user->can('HRMS-AttendanceManagement') || $correction->staff?->hierarchy_parent_id === $user->id));

        // Every new company starts with its country's working week.
        Tenant::created(fn (Tenant $tenant) => WorkSchedule::seedDefaultsFor($tenant));

        if ($this->app->runningInConsole()) {
            $this->commands([CloseOutAttendanceCommand::class, GenerateVisitsCommand::class]);
        }
    }
}
