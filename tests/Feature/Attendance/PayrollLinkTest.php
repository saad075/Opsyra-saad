<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitLocation;
use App\Modules\Attendance\Support\AttendancePolicy;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\Payslip;

/**
 * Attendance feeding payroll: hourly pay from verified time, and the
 * company's deduction rules for salaried staff. March 2026 has 31 days.
 */
class PayrollLinkTest extends AttendanceTestCase
{
    private User $officer;

    private int $basicId;

    private int $hourlyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inTenantA(function () {
            $this->officer = $this->makeStaff(['name' => 'Pat Payroll']);
            $this->officer->givePermissionTo('HRMS-SalaryManagement');
            $this->basicId = PayComponent::basic()->id;
            $this->hourlyId = PayComponent::create(['name' => 'Care visits', 'code' => 'CARE_HOURS', 'type' => 'earning', 'calculation' => 'per_hour'])->id;
        });
    }

    private function salary(User $who, array $components): void
    {
        $this->actingAs($this->officer, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $who->id, 'effective_from' => '2026-01-01', 'pay_frequency' => 'monthly', 'currency' => 'USD', 'components' => $components,
        ])->assertCreated();
    }

    private function runMarch(User $who): Payslip
    {
        $this->travelTo('2026-04-01 10:00');
        $this->actingAs($this->officer, 'sanctum')->postJson('/api/payroll/pay-runs/create', ['frequency' => 'monthly', 'period_start' => '2026-03-01'])->assertCreated();

        return $this->inTenantA(fn () => Payslip::with('lines')->where('user_id', $who->id)->firstOrFail());
    }

    private function dayOf(User $who, string $date, array $facts): void
    {
        $this->inTenantA(fn () => AttendanceDay::create(['user_id' => $who->id, 'date' => $date] + $facts + ['status' => 'present']));
    }

    public function test_caregivers_are_paid_for_verified_visit_time_only(): void
    {
        $carer = $this->inTenantA(fn () => $this->makeStaff(['attendance_mode' => 'visits']));
        $this->salary($carer, [['pay_component_id' => $this->hourlyId, 'value' => 18]]);

        $this->inTenantA(function () use ($carer) {
            $home = VisitLocation::create(['name' => 'Mr B', 'address' => 'x', 'latitude' => 51.5, 'longitude' => -0.12]);
            $visit = fn (string $date, array $facts) => Visit::create([
                'visit_location_id' => $home->id, 'user_id' => $carer->id, 'date' => $date,
                'planned_start' => "{$date} 08:00:00+00", 'planned_end' => "{$date} 09:00:00+00", 'status' => 'completed',
            ] + $facts);

            $visit('2026-03-02', ['worked_minutes' => 60]);                                        // verified
            $visit('2026-03-03', ['worked_minutes' => 90, 'review_status' => 'accepted']);         // accepted after a flag
            $visit('2026-03-04', ['worked_minutes' => 60, 'review_status' => 'needs_review']);     // still waiting
            $visit('2026-03-05', ['worked_minutes' => 60, 'review_status' => 'rejected']);
            $visit('2026-04-01', ['worked_minutes' => 60]);                                        // next month
        });

        $slip = $this->runMarch($carer);

        $line = $slip->lines->firstWhere('code', 'CARE_HOURS');
        $this->assertSame('Care visits (2.5 h)', $line->label);
        $this->assertEquals(45, $line->amount);
        $this->assertEquals(45, $slip->gross_earnings);
    }

    public function test_hourly_office_staff_are_paid_for_clocked_time(): void
    {
        $this->salary($this->employee, [['pay_component_id' => $this->basicId, 'value' => 0], ['pay_component_id' => $this->hourlyId, 'value' => 20]]);
        $this->dayOf($this->employee, '2026-03-02', ['worked_minutes' => 480]);
        $this->dayOf($this->employee, '2026-03-03', ['worked_minutes' => 450]);

        $this->assertEquals(310, $this->runMarch($this->employee)->lines->firstWhere('code', 'CARE_HOURS')->amount);
    }

    public function test_deductions_follow_the_companys_rules_when_switched_on(): void
    {
        $this->inTenantA(fn () => AttendancePolicy::update(['deductions_enabled' => true, 'free_lates_per_month' => 3, 'late_deduction_percent' => 25]));
        $this->salary($this->employee, [['pay_component_id' => $this->basicId, 'value' => 3100]]); // 100 a day in March

        $this->dayOf($this->employee, '2026-03-02', ['status' => 'absent']);
        $this->dayOf($this->employee, '2026-03-03', ['status' => 'half_day']);
        $this->dayOf($this->employee, '2026-03-04', ['status' => 'on_leave', 'leave_paid' => false]);
        $this->dayOf($this->employee, '2026-03-05', ['status' => 'on_leave', 'leave_paid' => true]);
        $this->dayOf($this->employee, '2026-03-06', ['missed_checkout' => true]);
        foreach (['2026-03-09', '2026-03-10', '2026-03-11', '2026-03-12', '2026-03-13'] as $date) {
            $this->dayOf($this->employee, $date, ['is_late' => true, 'late_minutes' => 20]);
        }

        $lines = $this->runMarch($this->employee)->lines->where('source', 'attendance')->keyBy('code');

        $this->assertEquals(100, $lines['ATT_ABSENT']->amount);
        $this->assertSame('1 absent day', $lines['ATT_ABSENT']->label);
        $this->assertEquals(50, $lines['ATT_HALF_DAY']->amount);
        $this->assertEquals(100, $lines['ATT_UNPAID_LEAVE']->amount);
        $this->assertEquals(100, $lines['ATT_MISSED_CHECKOUT']->amount, 'Counts as absent by default, as SLMS360.');
        $this->assertEquals(50, $lines['ATT_LATE']->amount, '5 lates, 3 free: 2 × 25% of a day.');
        $this->assertSame('2 late arrivals beyond 3 free a month', $lines['ATT_LATE']->label);
        $this->assertArrayNotHasKey('ATT_EARLY', $lines->all());
    }

    public function test_free_lates_count_from_the_start_of_the_month_even_before_the_period(): void
    {
        $this->inTenantA(fn () => AttendancePolicy::update(['deductions_enabled' => true, 'free_lates_per_month' => 2, 'late_deduction_percent' => 50]));
        $this->salary($this->employee, [['pay_component_id' => $this->basicId, 'value' => 3100]]);

        // Two lates in February's last week can't use up March's allowance…
        $this->dayOf($this->employee, '2026-02-26', ['is_late' => true]);
        $this->dayOf($this->employee, '2026-02-27', ['is_late' => true]);
        // …so March's first two are free and only the third costs half a day.
        foreach (['2026-03-02', '2026-03-03', '2026-03-04'] as $date) {
            $this->dayOf($this->employee, $date, ['is_late' => true]);
        }

        $this->assertEquals(50, $this->runMarch($this->employee)->lines->firstWhere('code', 'ATT_LATE')->amount);
    }

    public function test_no_deductions_until_the_company_turns_them_on(): void
    {
        $this->salary($this->employee, [['pay_component_id' => $this->basicId, 'value' => 3100]]);
        $this->dayOf($this->employee, '2026-03-02', ['status' => 'absent']);

        $slip = $this->runMarch($this->employee);

        $this->assertCount(0, $slip->lines->where('source', 'attendance'));
        $this->assertEquals(3100, $slip->net_pay);
    }

    public function test_a_salary_can_be_hourly_only_but_needs_one_or_the_other(): void
    {
        $this->salary($this->employee, [['pay_component_id' => $this->hourlyId, 'value' => 18]]);

        $allowance = $this->inTenantA(fn () => PayComponent::create(['name' => 'Phone', 'code' => 'PHONE', 'type' => 'earning', 'calculation' => 'fixed'])->id);
        $this->actingAs($this->officer, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $this->employee->id, 'effective_from' => '2026-02-01', 'pay_frequency' => 'monthly', 'components' => [['pay_component_id' => $allowance, 'value' => 50]],
        ])->assertUnprocessable()->assertJsonPath('errors.components.0', 'Every salary needs a basic pay amount or an hourly rate.');

        // An hourly rate isn't a percentage: over 100 is fine.
        $this->actingAs($this->officer, 'sanctum')->postJson('/api/payroll/salaries/preview', [
            'currency' => 'USD', 'components' => [['pay_component_id' => $this->hourlyId, 'value' => 150]],
        ])->assertOk()->assertJsonPath('data.gross', 0);
    }

    public function test_only_earnings_can_be_paid_by_the_hour(): void
    {
        $this->actingAs($this->officer, 'sanctum')->postJson('/api/payroll/pay-components/create', ['name' => 'Overtime', 'type' => 'earning', 'calculation' => 'per_hour'])->assertCreated();
        $this->actingAs($this->officer, 'sanctum')->postJson('/api/payroll/pay-components/create', ['name' => 'Dock', 'type' => 'deduction', 'calculation' => 'per_hour'])->assertUnprocessable();
    }
}
