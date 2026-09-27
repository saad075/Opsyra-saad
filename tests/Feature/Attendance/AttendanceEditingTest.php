<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Attendance\Models\WorkScheduleAssignment;
use App\Modules\Setup\Models\TenantSetting;

class AttendanceEditingTest extends AttendanceTestCase
{
    public function test_hr_marks_leave_which_the_clock_then_leaves_alone(): void
    {
        $this->travelTo('2026-03-01 12:00');

        // Planned leave can be recorded ahead of time.
        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/mark', [
            'user_id' => $this->employee->id, 'date' => '2026-03-02', 'action' => 'status', 'status' => 'on_leave', 'leave_paid' => false, 'note' => 'Family visit',
        ])->assertOk()->assertJsonPath('data.status', 'on_leave')->assertJsonPath('data.leave_paid', false);

        $this->clockAt('2026-03-02 10:00');
        $this->closeOut('2026-03-03 01:00');

        $day = $this->day('2026-03-02');
        $this->assertSame('on_leave', $day->status);
        $this->assertTrue($day->is_manual);

        // Handed back to the clock, the day is judged from its punches: they
        // clocked in at 10:00 and never out.
        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/mark', ['user_id' => $this->employee->id, 'date' => '2026-03-02', 'action' => 'reset'])->assertOk();
        $day = $this->day('2026-03-02');
        $this->assertSame('present', $day->status);
        $this->assertTrue($day->missed_checkout);
        $this->assertFalse($day->is_manual);
        $this->assertNull($day->leave_paid);
    }

    public function test_hr_sets_times_and_the_original_punches_are_kept_voided(): void
    {
        $this->clockAt('2026-03-02 09:40');
        $this->closeOut('2026-03-03 01:00');
        $this->assertTrue($this->day('2026-03-02')->missed_checkout);

        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/mark', [
            'user_id' => $this->employee->id, 'date' => '2026-03-02', 'action' => 'times', 'in' => '09:00', 'out' => '17:00',
        ])->assertOk()->assertJsonPath('data.status', 'present')->assertJsonPath('data.missed_checkout', false)->assertJsonCount(2, 'data.punches');

        $day = $this->day('2026-03-02');
        $this->assertSame(480, $day->worked_minutes);
        $this->assertCount(3, $day->punches, 'Two corrected punches plus the voided original.');
        $this->assertNotNull($day->punches->firstWhere('source', 'app')->voided_at);
    }

    public function test_times_hr_sets_are_the_persons_local_times(): void
    {
        $this->inTenantA(fn () => TenantSetting::set('timezone', 'Asia/Karachi')); // UTC+5
        $this->travelTo('2026-03-03 12:00');

        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/mark', [
            'user_id' => $this->employee->id, 'date' => '2026-03-02', 'action' => 'times', 'in' => '09:00', 'out' => '17:00',
        ])->assertOk()->assertJsonPath('data.first_in_at', '2026-03-02T09:00:00+05:00')->assertJsonPath('data.is_late', false);

        $this->assertSame('2026-03-02 04:00', $this->day('2026-03-02')->first_in_at->utc()->format('Y-m-d H:i'), '09:00 in Karachi is 04:00 UTC.');
    }

    public function test_the_future_is_off_limits_except_for_leave(): void
    {
        $this->travelTo('2026-03-02 12:00');

        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/mark', [
            'user_id' => $this->employee->id, 'date' => '2026-03-05', 'action' => 'times', 'in' => '09:00', 'out' => '17:00',
        ])->assertStatus(409);
    }

    public function test_the_daily_sheet_shows_who_is_in_late_or_not_in_yet(): void
    {
        $this->clockAt('2026-03-02 09:30'); // late
        $this->travelTo('2026-03-02 11:00');

        $rows = $this->actingAs($this->hr, 'sanctum')->getJson('/api/attendance/daily?date=2026-03-02')
            ->assertOk()
            ->assertJsonPath('data.summary.in', 1)
            ->assertJsonPath('data.summary.late', 1)
            // HR, the manager and tenant A's seed user.
            ->assertJsonPath('data.summary.not_in_yet', 3)
            ->json('data.rows');

        $this->assertCount(4, $rows);
        $this->assertNull(collect($rows)->firstWhere('staff.id', $this->hr->id)['day']);
    }

    public function test_assigning_a_schedule_ends_the_previous_one(): void
    {
        [$a, $b] = $this->inTenantA(fn () => [
            WorkSchedule::create(['name' => 'Early']),
            WorkSchedule::create(['name' => 'Late']),
        ]);

        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/schedules/assign', ['work_schedule_id' => $a->id, 'user_ids' => [$this->employee->id], 'starts_on' => '2026-02-01'])->assertOk();
        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/schedules/assign', ['work_schedule_id' => $b->id, 'user_ids' => [$this->employee->id], 'starts_on' => '2026-03-01'])->assertOk();

        $assignments = $this->inTenantA(fn () => WorkScheduleAssignment::where('user_id', $this->employee->id)->orderBy('starts_on')->get());
        $this->assertSame('2026-02-28', $assignments[0]->ends_on->toDateString());
        $this->assertNull($assignments[1]->ends_on);
        $this->assertSame($b->id, $this->inTenantA(fn () => WorkScheduleAssignment::forUserOn($this->employee->id, '2026-03-05'))->work_schedule_id);
        $this->assertSame($a->id, $this->inTenantA(fn () => WorkScheduleAssignment::forUserOn($this->employee->id, '2026-02-15'))->work_schedule_id);

        $this->travelTo('2026-03-05 12:00');
        $people = collect($this->actingAs($this->hr, 'sanctum')->getJson('/api/attendance/schedules/people')->assertOk()->json('data'))->keyBy('id');
        $this->assertSame('Late', $people[$this->employee->id]['schedule']['name']);
        $this->assertFalse($people[$this->employee->id]['on_default']);
        $this->assertTrue($people[$this->manager->id]['on_default'], 'Everyone else follows the default week.');
    }

    public function test_the_rules_are_validated_and_saved(): void
    {
        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/policy/update', ['absent_below_percent' => 60])
            ->assertUnprocessable();

        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/policy/update', ['half_day_below_percent' => 40, 'free_lates_per_month' => 2])
            ->assertOk()->assertJsonPath('data.free_lates_per_month', 2)->assertJsonPath('data.deductions_enabled', false);

        // 3 of 7 hours (43%) is now a full day, not a half day.
        $this->clockAt('2026-03-02 09:00');
        $this->clockAt('2026-03-02 12:00');
        $this->assertSame('present', $this->day('2026-03-02')->status);
    }

    public function test_the_dashboard_shows_the_fortnight_and_what_is_waiting(): void
    {
        $this->clockAt('2026-03-02 09:30');
        $this->clockAt('2026-03-02 17:00');
        $this->closeOut('2026-03-04 01:00');
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/corrections/create', ['date' => '2026-03-03', 'requested_status' => 'remote', 'reason' => 'WFH']);

        $data = $this->actingAs($this->hr, 'sanctum')->getJson('/api/attendance/dashboard')->assertOk()->json('data');

        $this->assertCount(14, $data['trend']);
        $monday = collect($data['trend'])->firstWhere('date', '2026-03-02');
        $this->assertSame(1, $monday['late']);
        $this->assertSame(1, $monday['present']);
        $this->assertSame(1, $data['waiting']['corrections']);
        $this->assertSame('Erin Employee', $data['month']['most_late'][0]['name']);

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/dashboard')->assertForbidden();
    }

    public function test_hr_screens_need_the_attendance_permission(): void
    {
        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/daily')->assertForbidden();
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/mark', ['user_id' => $this->employee->id, 'date' => '2026-03-02', 'action' => 'status', 'status' => 'present'])->assertForbidden();
        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/schedules/list')->assertForbidden();
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/holidays/create', ['date' => '2026-12-25', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/policy/update', [])->assertForbidden();

        // …and stay inside the company.
        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/person-month', ['user_id' => $this->userB->id])->assertUnprocessable();
    }
}
