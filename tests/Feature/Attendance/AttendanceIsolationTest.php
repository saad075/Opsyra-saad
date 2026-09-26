<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\AttendanceCorrection;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\AttendancePunch;
use App\Modules\Attendance\Models\Holiday;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitLocation;
use App\Modules\Attendance\Models\VisitPlan;
use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Attendance\Models\WorkScheduleAssignment;
use App\Modules\Attendance\Models\WorkScheduleDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

class AttendanceIsolationTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_work_schedules_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(WorkSchedule::class, fn () => WorkSchedule::create(['name' => 'B nights']));
    }

    public function test_work_schedule_days_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(WorkScheduleDay::class, fn () => WorkSchedule::create(['name' => 'B nights'])
            ->days()->create(['weekday' => 1, 'is_working' => true, 'start_time' => '22:00', 'end_time' => '06:00']));
    }

    public function test_work_schedule_assignments_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(WorkScheduleAssignment::class, fn () => WorkScheduleAssignment::create([
            'user_id' => $this->userB->id, 'work_schedule_id' => WorkSchedule::create(['name' => 'B nights'])->id, 'starts_on' => '2026-03-01',
        ]));
    }

    public function test_holidays_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(Holiday::class, fn () => Holiday::create(['date' => '2026-12-25', 'name' => 'B holiday']));
    }

    public function test_attendance_days_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(AttendanceDay::class, fn () => AttendanceDay::create(['user_id' => $this->userB->id, 'date' => '2026-03-02', 'status' => 'present']));
    }

    public function test_attendance_punches_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(AttendancePunch::class, function () {
            $day = AttendanceDay::create(['user_id' => $this->userB->id, 'date' => '2026-03-02', 'status' => 'open']);

            return $day->punches()->create(['user_id' => $this->userB->id, 'type' => 'in', 'punched_at' => now()]);
        });
    }

    public function test_attendance_corrections_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(AttendanceCorrection::class, fn () => AttendanceCorrection::create([
            'user_id' => $this->userB->id, 'date' => '2026-03-02', 'requested_in' => '09:00', 'reason' => 'B',
        ]));
    }

    public function test_visit_locations_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(VisitLocation::class, fn () => VisitLocation::create(['name' => 'B client', 'address' => 'B street']));
    }

    public function test_visit_plans_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(VisitPlan::class, fn () => VisitPlan::create([
            'visit_location_id' => VisitLocation::create(['name' => 'B client', 'address' => 'B street'])->id, 'user_id' => $this->userB->id,
            'weekdays' => [1], 'start_time' => '08:00', 'end_time' => '09:00', 'starts_on' => '2026-03-01',
        ]));
    }

    public function test_visits_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(Visit::class, fn () => Visit::create([
            'visit_location_id' => VisitLocation::create(['name' => 'B client', 'address' => 'B street'])->id, 'user_id' => $this->userB->id,
            'date' => '2026-03-02', 'planned_start' => '2026-03-02 08:00:00+00', 'planned_end' => '2026-03-02 09:00:00+00',
        ]));
    }
}
