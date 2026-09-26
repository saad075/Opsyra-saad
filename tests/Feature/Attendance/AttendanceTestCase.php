<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Rbac\Support\PermissionCatalogue;
use App\Modules\Setup\Models\TenantSetting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

/**
 * Tenant A keeps attendance from 1 Jan 2026 on its seeded default week:
 * Mon–Fri 09:00–17:00 with a 60-minute break (420 minutes), 10 minutes'
 * grace either end, company timezone UTC. 2 March 2026 is a Monday.
 */
abstract class AttendanceTestCase extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected User $hr;

    protected User $employee;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
        PermissionCatalogue::sync();

        $this->inTenantA(function () {
            TenantSetting::set('attendance_started_on', '2026-01-01');
            TenantSetting::set('timezone', 'UTC');

            $this->hr = $this->makeStaff(['name' => 'Hana HR']);
            $this->hr->givePermissionTo('HRMS-AttendanceManagement');

            $this->manager = $this->makeStaff(['name' => 'Max Manager']);
            $this->employee = $this->makeStaff(['name' => 'Erin Employee', 'hierarchy_parent_id' => $this->manager->id]);
        });
    }

    protected function inTenantA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    protected function makeStaff(array $attributes = []): User
    {
        return User::factory()->for($this->tenantA)->create($attributes + ['status' => 'active', 'joined_at' => '2026-01-01']);
    }

    /** Clock at a UTC moment, as the app would. */
    protected function clockAt(string $moment, ?User $who = null, array $body = []): TestResponse
    {
        $this->travelTo(CarbonImmutable::parse($moment, 'UTC'));

        return $this->actingAs($who ?? $this->employee, 'sanctum')->postJson('/api/attendance/clock', $body);
    }

    protected function day(string $date, ?User $who = null): ?AttendanceDay
    {
        return $this->inTenantA(fn () => AttendanceDay::with('punches')->where('user_id', ($who ?? $this->employee)->id)->whereDate('date', $date)->first());
    }

    protected function closeOut(string $moment): void
    {
        $this->travelTo(CarbonImmutable::parse($moment, 'UTC'));
        $this->artisan('attendance:close-out')->assertSuccessful();
    }
}
