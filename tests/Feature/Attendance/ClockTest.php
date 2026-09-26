<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Attendance\Models\WorkScheduleAssignment;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\UserBranch;
use App\Modules\Setup\Models\TenantSetting;

class ClockTest extends AttendanceTestCase
{
    public function test_an_on_time_day_is_present_with_minutes_worked(): void
    {
        $this->clockAt('2026-03-02 08:55')->assertCreated()->assertJsonPath('data.status', 'open')->assertJsonPath('data.punch_type', 'in');

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/today')
            ->assertOk()->assertJsonPath('data.next_action', 'out')->assertJsonPath('data.expected.kind', 'work');

        $this->clockAt('2026-03-02 17:05')->assertCreated()->assertJsonPath('data.punch_type', 'out');

        $day = $this->day('2026-03-02');
        $this->assertSame('present', $day->status);
        $this->assertSame(490, $day->worked_minutes);
        $this->assertSame(420, $day->scheduled_minutes);
        $this->assertFalse($day->is_late);
        $this->assertFalse($day->left_early);
    }

    public function test_lateness_counts_from_the_start_but_only_after_the_grace_period(): void
    {
        $this->clockAt('2026-03-02 09:08');
        $this->assertFalse($this->day('2026-03-02')->is_late, 'Within 10 minutes’ grace.');

        $this->clockAt('2026-03-03 09:25');
        $day = $this->day('2026-03-03');
        $this->assertTrue($day->is_late);
        $this->assertSame(25, $day->late_minutes);
    }

    public function test_a_short_day_becomes_a_half_day_or_absent_by_the_company_thresholds(): void
    {
        // 3 of 7 hours (43%) → half day (under 50%).
        $this->clockAt('2026-03-02 09:00');
        $this->clockAt('2026-03-02 12:00');
        $day = $this->day('2026-03-02');
        $this->assertSame('half_day', $day->status);
        $this->assertTrue($day->left_early);
        $this->assertSame(300, $day->early_minutes);

        // 1 hour (14%) → absent (under 25%).
        $this->clockAt('2026-03-03 09:00');
        $this->clockAt('2026-03-03 10:00');
        $this->assertSame('absent', $this->day('2026-03-03')->status);
    }

    public function test_a_double_tap_cannot_clock_in_twice(): void
    {
        $this->clockAt('2026-03-02 09:00', body: ['type' => 'in'])->assertCreated();
        $this->clockAt('2026-03-02 09:00', body: ['type' => 'in'])->assertStatus(409)->assertJsonPath('message', 'You’re already clocked in.');
        $this->assertCount(1, $this->day('2026-03-02')->punches);
    }

    public function test_far_from_the_branch_is_recorded_and_flagged_not_refused(): void
    {
        $branch = $this->inTenantA(function () {
            $branch = Branch::create(['name' => 'Muscat office', 'latitude' => 23.5880, 'longitude' => 58.3829, 'geofence_radius_m' => 150]);
            UserBranch::create(['user_id' => $this->employee->id, 'branch_id' => $branch->id, 'status' => true]);

            return $branch;
        });

        // About 1.1 km away.
        $this->clockAt('2026-03-02 09:00', body: ['branch_id' => $branch->id, 'latitude' => 23.5980, 'longitude' => 58.3829, 'accuracy_m' => 20])
            ->assertCreated()
            ->assertJsonPath('data.needs_review', true)
            ->assertJsonPath('data.punches.0.within_range', false);

        $punch = $this->day('2026-03-02')->punches->first();
        $this->assertEqualsWithDelta(1112, $punch->distance_m, 5);

        // Near enough: within range.
        $this->clockAt('2026-03-02 17:00', body: ['branch_id' => $branch->id, 'latitude' => 23.5881, 'longitude' => 58.3830])
            ->assertCreated()->assertJsonPath('data.punches.1.within_range', true);
    }

    public function test_the_branch_must_be_one_of_your_own(): void
    {
        $other = $this->inTenantA(fn () => Branch::create(['name' => 'Elsewhere']));

        $this->clockAt('2026-03-02 09:00', body: ['branch_id' => $other->id])->assertUnprocessable()->assertJsonValidationErrors('branch_id');
    }

    public function test_a_night_shift_belongs_to_the_day_it_started(): void
    {
        $this->inTenantA(function () {
            $nights = WorkSchedule::create(['name' => 'Nights']);
            foreach (range(1, 7) as $weekday) {
                $nights->days()->create(['weekday' => $weekday, 'is_working' => $weekday <= 5, 'start_time' => $weekday <= 5 ? '22:00' : null, 'end_time' => $weekday <= 5 ? '06:00' : null, 'break_minutes' => 30]);
            }
            WorkScheduleAssignment::create(['user_id' => $this->employee->id, 'work_schedule_id' => $nights->id, 'starts_on' => '2026-01-01']);
        });

        $this->clockAt('2026-03-02 21:55');
        $this->clockAt('2026-03-03 06:05');

        $day = $this->day('2026-03-02');
        $this->assertSame('present', $day->status);
        $this->assertSame(490, $day->worked_minutes);
        $this->assertSame(450, $day->scheduled_minutes);
        $this->assertNull($this->day('2026-03-03'), 'Nothing on Tuesday until Tuesday night’s shift.');

        // Clocking in at 01:00 during Monday night's shift also counts for Monday.
        $this->clockAt('2026-03-03 23:30');
        $this->assertNotNull($this->day('2026-03-03'));
    }

    public function test_dates_follow_the_companys_timezone(): void
    {
        $this->inTenantA(fn () => TenantSetting::set('timezone', 'Asia/Karachi')); // UTC+5

        // 03:55 UTC is 08:55 in Karachi on Monday.
        $this->clockAt('2026-03-02 03:55')->assertCreated()->assertJsonPath('data.date', '2026-03-02')->assertJsonPath('data.is_late', false);
        $this->assertStringContainsString('+05:00', $this->day('2026-03-02') ? $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/today')->json('data.day.first_in_at') : '');
    }

    public function test_visit_based_and_untracked_staff_cannot_use_the_clock(): void
    {
        $this->inTenantA(fn () => $this->employee->update(['attendance_mode' => 'visits']));
        $this->clockAt('2026-03-02 09:00')->assertStatus(409);

        $this->inTenantA(fn () => $this->employee->update(['attendance_mode' => 'not_tracked']));
        $this->clockAt('2026-03-02 09:00')->assertStatus(409);
    }

    public function test_the_month_and_schedule_are_the_callers_own(): void
    {
        $this->clockAt('2026-03-02 09:00');
        $this->clockAt('2026-03-02 17:00');
        $this->clockAt('2026-03-02 09:00', $this->manager);

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/month?month=2026-03')
            ->assertOk()
            ->assertJsonCount(1, 'data.days')
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.worked_minutes', 480);

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/my-schedule')
            ->assertOk()
            ->assertJsonPath('data.schedule.days.0.name', 'Monday')
            ->assertJsonPath('data.schedule.days.0.start_time', '09:00')
            ->assertJsonPath('data.schedule.days.6.is_working', false);
    }
}
