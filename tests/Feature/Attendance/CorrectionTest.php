<?php

namespace Tests\Feature\Attendance;

class CorrectionTest extends AttendanceTestCase
{
    private function ask(array $body): int
    {
        return $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/corrections/create', $body + ['date' => '2026-03-02', 'reason' => 'Phone died'])
            ->assertCreated()->json('data.id');
    }

    public function test_the_manager_approves_forgotten_clocking_and_the_day_is_fixed(): void
    {
        $this->clockAt('2026-03-02 09:00');
        $this->closeOut('2026-03-03 01:00');

        $id = $this->ask(['requested_in' => '09:00', 'requested_out' => '17:30']);

        $this->actingAs($this->manager, 'sanctum')->getJson('/api/attendance/corrections/list')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->manager, 'sanctum')->postJson('/api/attendance/corrections/approve', ['id' => $id])->assertOk();

        $day = $this->day('2026-03-02');
        $this->assertSame('present', $day->status);
        $this->assertSame(510, $day->worked_minutes);
        $this->assertFalse($day->missed_checkout);
        $this->assertSame('correction', $day->source);
    }

    public function test_hr_approves_a_remote_day_or_leave_and_decides_if_leave_is_paid(): void
    {
        $this->closeOut('2026-03-03 01:00');
        $id = $this->ask(['requested_status' => 'on_leave', 'reason' => 'Sick']);

        $this->actingAs($this->hr, 'sanctum')->postJson('/api/attendance/corrections/approve', ['id' => $id, 'leave_paid' => true])->assertOk();

        $day = $this->day('2026-03-02');
        $this->assertSame('on_leave', $day->status);
        $this->assertTrue($day->leave_paid);
    }

    public function test_nobody_decides_their_own_and_colleagues_cannot_decide(): void
    {
        $this->inTenantA(fn () => $this->employee->givePermissionTo('HRMS-AttendanceManagement'));
        $id = $this->ask(['requested_in' => '09:00', 'requested_out' => '17:00']);
        $colleague = $this->inTenantA(fn () => $this->makeStaff());

        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/corrections/approve', ['id' => $id])->assertForbidden();
        $this->actingAs($colleague, 'sanctum')->postJson('/api/attendance/corrections/approve', ['id' => $id])->assertForbidden();
        $this->actingAs($colleague, 'sanctum')->getJson('/api/attendance/corrections/list')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/corrections/list')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_one_open_request_per_day_and_never_for_the_future(): void
    {
        $this->travelTo('2026-03-02 12:00');
        $this->ask(['requested_in' => '09:00']);

        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/corrections/create', ['date' => '2026-03-02', 'requested_in' => '09:00', 'reason' => 'Again'])->assertStatus(409);
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/attendance/corrections/create', ['date' => '2026-03-04', 'requested_in' => '09:00', 'reason' => 'Future'])->assertUnprocessable();
    }

    public function test_a_declined_request_leaves_the_day_as_it_was(): void
    {
        $this->closeOut('2026-03-03 01:00');
        $id = $this->ask(['requested_status' => 'remote']);

        $this->actingAs($this->manager, 'sanctum')->postJson('/api/attendance/corrections/reject', ['id' => $id, 'response' => 'You were in the office log as absent.'])->assertOk();
        $this->actingAs($this->manager, 'sanctum')->postJson('/api/attendance/corrections/approve', ['id' => $id])->assertStatus(409);

        $this->assertSame('absent', $this->day('2026-03-02')->status);
        $this->actingAs($this->employee, 'sanctum')->getJson('/api/attendance/corrections/mine')->assertJsonPath('data.0.status', 'rejected');
    }
}
