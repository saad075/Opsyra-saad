<?php

namespace App\Modules\Attendance\Setup;

use App\Modules\Attendance\Models\Holiday;
use App\Modules\Setup\Contracts\SetupStepProvider;
use App\Modules\Setup\Support\SetupStep;

/**
 * The Attendance module's part of the Setup wizard. A working week is
 * pre-filled per country, so schedules and the rules are review steps;
 * holidays are optional (every company's list differs).
 */
class AttendanceSetupSteps implements SetupStepProvider
{
    public function steps(): array
    {
        return [
            SetupStep::review(
                'work_schedules', 'attendance', 10,
                'Working hours',
                'Your working week — days, start and finish times. A standard week is pre-filled for your country; add others (e.g. a night shift) and assign people to them.',
            ),
            new SetupStep(
                'holidays', 'attendance', 20,
                'Public holidays',
                'Days off for everyone, or for one branch.',
                required: false,
                check: function () {
                    $count = Holiday::whereBetween('date', [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()])->count();

                    return ['done' => $count > 0, 'detail' => $count ? "{$count} holiday(s) this year" : null];
                },
            ),
            SetupStep::review(
                'attendance_rules', 'attendance', 30,
                'Attendance rules',
                'When a short day counts as a half day or absent, and whether lateness and absence reduce pay. Pay deductions start switched off.',
            ),
        ];
    }
}
