<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\Holiday;
use App\Modules\Setup\Models\TenantSetting;

class CloseOutTest extends AttendanceTestCase
{
    public function test_finished_days_are_settled_from_the_calendar(): void
    {
        $this->inTenantA(fn () => Holiday::create(['date' => '2026-03-04', 'name' => 'Founders’ day']));

        // Mon 2 → Sun 8 March, settled on Monday 9th.
        $this->closeOut('2026-03-09 01:00');

        $this->assertSame('absent', $this->day('2026-03-02')->status);
        $this->assertSame('holiday', $this->day('2026-03-04')->status);
        $this->assertSame('day_off', $this->day('2026-03-07')->status);
        $this->assertSame('day_off', $this->day('2026-03-08')->status);
        $this->assertNull($this->day('2026-03-09'), 'Today isn’t finished.');
        $this->assertNotNull($this->day('2026-03-02')->closed_at);
    }

    public function test_remote_staff_are_remote_on_working_days_without_clocking(): void
    {
        $this->inTenantA(fn () => $this->employee->update(['is_remote' => true]));

        $this->closeOut('2026-03-03 01:00');

        $this->assertSame('remote', $this->day('2026-03-02')->status);
    }

    public function test_a_clock_in_never_clocked_out_becomes_a_missed_checkout(): void
    {
        $this->clockAt('2026-03-02 09:00');

        $this->closeOut('2026-03-03 01:00');

        $day = $this->day('2026-03-02');
        $this->assertSame('present', $day->status, 'They came in; the pay rule decides what a missed checkout costs.');
        $this->assertTrue($day->missed_checkout);

        // The next clock is a fresh clock-in, not a clock-out of Monday.
        $this->clockAt('2026-03-03 09:00')->assertJsonPath('data.punch_type', 'in');
    }

    public function test_days_are_settled_once_and_a_missed_run_catches_up(): void
    {
        $this->closeOut('2026-03-03 01:00');
        $this->closeOut('2026-03-03 02:00');
        $this->assertSame(1, $this->inTenantA(fn () => AttendanceDay::where('user_id', $this->employee->id)->whereDate('date', '2026-03-02')->count()));

        // Nothing ran Tue–Thu; Friday's run fills Tue, Wed and Thu.
        $this->closeOut('2026-03-06 01:00');
        foreach (['2026-03-03', '2026-03-04', '2026-03-05'] as $date) {
            $this->assertSame('absent', $this->day($date)->status);
        }
    }

    public function test_never_before_joining_or_before_the_company_started_keeping_attendance(): void
    {
        $joiner = $this->inTenantA(fn () => $this->makeStaff(['joined_at' => '2026-03-04']));
        $this->inTenantA(fn () => TenantSetting::set('attendance_started_on', '2026-03-03'));

        $this->closeOut('2026-03-06 01:00');

        $this->assertNull($this->day('2026-03-02'), 'Before the company started.');
        $this->assertNotNull($this->day('2026-03-03'));
        $this->assertNull($this->day('2026-03-03', $joiner), 'Before they joined.');
        $this->assertSame('absent', $this->day('2026-03-04', $joiner)->status);
    }

    public function test_untracked_staff_are_left_alone_and_visit_workers_are_settled_from_visits(): void
    {
        $this->inTenantA(fn () => $this->employee->update(['attendance_mode' => 'not_tracked']));
        $this->inTenantA(fn () => $this->manager->update(['attendance_mode' => 'visits']));

        $this->closeOut('2026-03-03 01:00');

        $this->assertNull($this->day('2026-03-02'));
        // No visits planned that day: a day off, not an absence.
        $this->assertSame('day_off', $this->day('2026-03-02', $this->manager)->status);
    }
}
