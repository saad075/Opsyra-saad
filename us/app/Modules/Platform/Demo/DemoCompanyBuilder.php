<?php

namespace App\Modules\Platform\Demo;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceCorrection;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\AttendancePunch;
use App\Modules\Attendance\Models\Holiday;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitLocation;
use App\Modules\Attendance\Models\VisitPlan;
use App\Modules\Attendance\Services\AttendanceEditor;
use App\Modules\Attendance\Services\DayCloser;
use App\Modules\Attendance\Services\DayEvaluator;
use App\Modules\Attendance\Services\VisitDaySettler;
use App\Modules\Attendance\Services\VisitScheduler;
use App\Modules\Attendance\Services\WorkCalendar;
use App\Modules\Attendance\Support\Geo;
use App\Modules\Auth\Models\LoginLog;
use App\Modules\Directory\Models\ClientDirectoryEntry;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\Department;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Hr\Models\Designation;
use App\Modules\Hr\Models\StaffChecklist;
use App\Modules\Hr\Models\UserBranch;
use App\Modules\Hr\Services\StaffChecklists;
use App\Modules\Hr\Services\StaffOffboarding;
use App\Modules\Hr\Services\StaffOnboarding;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryAdjustment;
use App\Modules\Payroll\Models\SalaryClaim;
use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Services\PayslipApprovals;
use App\Modules\Payroll\Services\PayslipGenerator;
use App\Modules\Payroll\Support\PayPeriod;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Services\TenantProvisioner;
use App\Modules\Rbac\Models\Role;
use App\Modules\Setup\Models\SetupStepState;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Tenancy\Support\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Builds the public demo company (user decision 2026-09-25: a live sample
 * company like Horilla's demo, rebuilt every night). Everything goes
 * through Opsyra's own services — onboarding, offboarding, checklists,
 * payroll, approvals — so the demo behaves exactly like a real company,
 * and all dates are relative to today so it always looks current.
 *
 * No Faker (it's a dev-only dependency): fixed names, deterministic data.
 */
class DemoCompanyBuilder
{
    public const CLIENT_CODE = 'demo';

    public const ADMIN_EMAIL = 'admin@demo-company.example';

    /** Public by design — shown on the demo sign-in page. */
    public const ADMIN_PASSWORD = 'opsyra-demo-2026';

    private const DOMAIN = 'demo-company.example'; // .example is reserved: mail can never reach a real inbox

    private CarbonImmutable $today;

    private Tenant $tenant;

    /** @var array<string, User> */
    private array $people = [];

    /** @var array<string, Branch> */
    private array $branches = [];

    /** Monthly basic pay per person key — kept apart from the User models so it's never saved to them. */
    private array $basic = [];

    public function __construct(
        private TenantProvisioner $provisioner,
        private StaffOnboarding $onboarding,
        private StaffOffboarding $offboarding,
        private StaffChecklists $checklists,
        private PayslipGenerator $payslips,
        private PayslipApprovals $approvals,
        private AttendanceEditor $attendanceEditor,
        private DayCloser $dayCloser,
        private DayEvaluator $dayEvaluator,
        private WorkCalendar $workCalendar,
        private VisitScheduler $visitScheduler,
        private VisitDaySettler $visitSettler,
    ) {}

    /**
     * Deletes the demo company (if any) and builds it fresh.
     */
    public function rebuild(): Tenant
    {
        $this->today = CarbonImmutable::today();

        $this->wipe();

        ['tenant' => $this->tenant, 'admin' => $admin] = $this->provisioner->provision([
            'name' => 'Demo Company',
            'client_code' => self::CLIENT_CODE,
            'admin_name' => 'Alex Morgan',
            'admin_email' => self::ADMIN_EMAIL,
            'status' => 'active',
        ]);

        $this->tenant->update([
            'is_demo' => true,
            'plan_code' => 'premium',
            'features' => ['people', 'hiring_approvals', 'checklists', 'payroll', 'pay_approvals', 'attendance', 'login_history'],
        ]);

        app(TenantContext::class)->runAs($this->tenant->id, function () use ($admin) {
            DB::transaction(function () use ($admin) {
                $admin->forceFill([
                    'password' => self::ADMIN_PASSWORD,
                    'must_change_password' => false,
                    'designation' => 'Operations Director',
                    'joined_at' => $this->today->subYears(4)->startOfMonth(),
                    'phone' => '+1 212 555 0100',
                ])->save();
                $this->people['admin'] = $admin;

                $this->company();
                $this->organisation();
                $this->staff();
                $this->access();
                $this->pay();
                $this->hiringAndLeaving();
                $this->homeCare();
                $this->payroll();
                $this->attendance();
                $this->activity();
                $this->finishSetup($admin);
            });
        });

        return $this->tenant->fresh();
    }

    /**
     * Removes the demo company completely. Every company table cascades from
     * `tenants`; the country's own directory entry is separate.
     */
    public function wipe(): void
    {
        $existing = Tenant::withTrashed()->where('client_code', self::CLIENT_CODE)->first();

        DB::transaction(function () use ($existing) {
            ClientDirectoryEntry::where('client_code', self::CLIENT_CODE)->delete();

            if ($existing) {
                // Rows inside the company that block deletion of rows they
                // reference (e.g. a salary's pay component) go first.
                app(TenantContext::class)->runAs($existing->id, function () {
                    DB::table('staff_salary_components')->delete();
                    DB::table('staff_offboard_requests')->delete();
                });

                $existing->forceDelete();
            }
        });
    }

    private function company(): void
    {
        foreach ([
            'company_name' => 'Demo Company',
            'address' => '350 Fifth Avenue, Suite 4100',
            'city' => 'New York',
            'phone' => '+1 212 555 0100',
            'email' => 'hello@'.self::DOMAIN,
            'timezone' => 'America/New_York',
            'currency' => 'USD',
        ] as $key => $value) {
            TenantSetting::set($key, $value);
        }
    }

    private function organisation(): void
    {
        foreach ([
            ['New York HQ', '350 Fifth Avenue', 'New York', 'America/New_York'],
            ['Chicago', '233 S Wacker Drive', 'Chicago', 'America/Chicago'],
            ['Austin', '500 W 2nd Street', 'Austin', 'America/Chicago'],
        ] as [$name, $address, $city, $tz]) {
            $this->branches[$name] = Branch::create(['name' => $name, 'address' => $address, 'city' => $city, 'timezone' => $tz, 'status' => true]);
        }

        foreach ([
            'Leadership' => ['Chief Executive Officer', 'Chief Financial Officer', 'Operations Director'],
            'Engineering' => ['Engineering Manager', 'Senior Software Engineer', 'Software Engineer', 'QA Engineer'],
            'Sales' => ['Sales Manager', 'Account Executive', 'Sales Development Rep'],
            'Operations' => ['HR Manager', 'Payroll Officer', 'Office Manager', 'Customer Support', 'Care Worker'],
            'Finance' => ['Accountant'],
        ] as $department => $titles) {
            $dept = Department::create(['name' => $department]);
            foreach ($titles as $title) {
                Designation::create(['department_id' => $dept->id, 'name' => $title]);
            }
        }
    }

    /**
     * [key, name, title, branch, manager key, years employed, basic monthly USD]
     */
    private function staff(): void
    {
        $roster = [
            ['ceo', 'Daniel Okafor', 'Chief Executive Officer', 'New York HQ', null, 6, 16500],
            ['cfo', 'Marcus Bell', 'Chief Financial Officer', 'New York HQ', 'ceo', 5, 14200],
            ['hr', 'Hannah Reyes', 'HR Manager', 'New York HQ', 'admin', 3, 8200],
            ['payroll', 'Priya Shah', 'Payroll Officer', 'New York HQ', 'cfo', 2, 6400],
            ['engmgr', 'Tomás Alvarez', 'Engineering Manager', 'Austin', 'ceo', 4, 12800],
            ['eng1', 'Mei Chen', 'Senior Software Engineer', 'Austin', 'engmgr', 3, 10400],
            ['eng2', 'Samuel Adeyemi', 'Senior Software Engineer', 'Austin', 'engmgr', 2, 10100],
            ['eng3', 'Olivia Novak', 'Software Engineer', 'Austin', 'engmgr', 1, 8300],
            ['eng4', 'Ravi Kumar', 'Software Engineer', 'Chicago', 'engmgr', 1, 8100],
            ['eng5', 'Grace Kim', 'QA Engineer', 'Austin', 'engmgr', 2, 7200],
            ['salesmgr', 'Jordan Hughes', 'Sales Manager', 'Chicago', 'ceo', 4, 11200],
            ['sales1', 'Aisha Rahman', 'Account Executive', 'Chicago', 'salesmgr', 2, 6800],
            ['sales2', 'Liam O’Connor', 'Account Executive', 'Chicago', 'salesmgr', 1, 6600],
            ['sales3', 'Sofia Rossi', 'Account Executive', 'New York HQ', 'salesmgr', 3, 7000],
            ['sales4', 'Ethan Brooks', 'Sales Development Rep', 'Chicago', 'salesmgr', 1, 4900],
            ['office', 'Nadia Petrova', 'Office Manager', 'New York HQ', 'admin', 5, 5600],
            ['support1', 'Kwame Mensah', 'Customer Support', 'Chicago', 'admin', 2, 4600],
            ['support2', 'Isabel Torres', 'Customer Support', 'Austin', 'admin', 1, 4500],
            ['accountant', 'Yusuf Demir', 'Accountant', 'New York HQ', 'cfo', 3, 6900],
            ['eng6', 'Chloe Martin', 'Software Engineer', 'Austin', 'engmgr', 0, 7900], // joined last month
        ];

        foreach ($roster as [$key, $name, $title, $branch, $manager, $years, $basic]) {
            $joined = $key === 'eng6'
                ? $this->today->subMonthNoOverflow()->startOfMonth()->addDays(14)
                : $this->today->subYears($years)->subMonths(crc32($key) % 9)->startOfMonth();

            $this->people[$key] = $this->makePerson($key, $name, $title, $branch, $manager, $joined, 'active');
            $this->basic[$key] = $basic;
        }

        $this->basic['admin'] = 11500;
        UserBranch::create(['user_id' => $this->people['admin']->id, 'branch_id' => $this->branches['New York HQ']->id, 'status' => true]);

        // Someone who left two months ago — shows in history and in that month's payroll.
        $leftAt = $this->today->subMonthsNoOverflow(2)->startOfMonth()->addDays(9);
        $past = $this->makePerson('former', 'Victor Lang', 'Sales Development Rep', 'Chicago', 'salesmgr', $this->today->subYears(2), 'offboarded');
        $past->forceFill([
            'lefted_at' => $leftAt,
            'left_reason' => 'Resignation: Moved to another city',
            'departure_reason_id' => DepartureReason::where('name', 'Resignation')->value('id'),
        ])->save();
        $this->basic['former'] = 4800;
        $this->people['former'] = $past;
    }

    private function makePerson(string $key, string $name, string $title, string $branch, ?string $managerKey, mixed $joined, string $status): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $this->emailFor($name),
            'password' => bin2hex(random_bytes(16)), // staff don't sign in to the demo
            'email_verified_at' => now(),
            'status' => $status,
            'designation' => $title,
            'phone' => '+1 555 01'.str_pad((string) (crc32($key) % 100), 2, '0', STR_PAD_LEFT),
            'joined_at' => $joined,
            'dob' => $this->today->subYears(24 + crc32($name) % 30)->subDays(crc32($key) % 300),
            'hierarchy_parent_id' => $managerKey ? $this->people[$managerKey]->id : null,
            'is_remote' => $branch === 'Austin' && crc32($key) % 3 === 0,
            'city' => $this->branches[$branch]->city,
        ]);

        UserBranch::create(['user_id' => $user->id, 'branch_id' => $this->branches[$branch]->id, 'status' => true]);

        return $user;
    }

    private function emailFor(string $name): string
    {
        return strtolower(preg_replace('/[^a-z.]/i', '', str_replace(' ', '.', iconv('UTF-8', 'ASCII//TRANSLIT', $name) ?: $name))).'@'.self::DOMAIN;
    }

    private function access(): void
    {
        // Leadership and HR/payroll work across every office, so they see
        // all branches in staff lists (lists follow the viewer's branches).
        foreach (['admin', 'ceo', 'cfo', 'hr', 'payroll'] as $key) {
            foreach ($this->branches as $branch) {
                UserBranch::firstOrCreate(['user_id' => $this->people[$key]->id, 'branch_id' => $branch->id], ['status' => true]);
            }
        }

        $this->people['hr']->assignRole(['HR Manager', 'Approver']);
        $this->people['payroll']->assignRole('Payroll Officer');
        $this->people['cfo']->assignRole('Senior approver');
        $this->people['ceo']->assignRole('Senior approver');
    }

    private function pay(): void
    {
        $components = [
            'COMMISSION' => ['Commission', 'earning', 'fixed', 20],
            'REMOTE' => ['Remote work stipend', 'earning', 'fixed', 30],
            'HEALTH' => ['Health insurance', 'deduction', 'fixed', 70],
            'RETIREMENT' => ['401(k) contribution', 'deduction', 'percent_of_basic', 75],
            'TAX_EST' => ['Income tax (estimate)', 'deduction', 'percent_of_gross', 80],
        ];

        $ids = ['BASIC' => PayComponent::basic()->id];
        foreach ($components as $code => [$name, $type, $calc, $seq]) {
            $ids[$code] = PayComponent::create(['name' => $name, 'code' => $code, 'type' => $type, 'calculation' => $calc, 'sequence' => $seq])->id;
        }

        foreach ($this->people as $key => $person) {
            $basic = $this->basic[$key];
            $lines = [
                ['pay_component_id' => $ids['BASIC'], 'value' => $basic],
                ['pay_component_id' => $ids['HEALTH'], 'value' => 185],
                ['pay_component_id' => $ids['RETIREMENT'], 'value' => [3, 4, 5, 6][crc32($key) % 4]],
                ['pay_component_id' => $ids['TAX_EST'], 'value' => $basic > 10000 ? 24 : ($basic > 7000 ? 20 : 15)],
            ];

            if (str_starts_with($key, 'sales')) {
                $lines[] = ['pay_component_id' => $ids['COMMISSION'], 'value' => 900 + crc32($key) % 1200];
            }
            if ($person->is_remote) {
                $lines[] = ['pay_component_id' => $ids['REMOTE'], 'value' => 150];
            }

            $salary = StaffSalary::create([
                'user_id' => $person->id,
                'effective_from' => $person->joined_at,
                'pay_frequency' => 'monthly',
                'currency' => 'USD',
                'created_by' => $this->people['payroll']->id ?? null,
            ]);
            foreach ($lines as $line) {
                $salary->components()->create($line);
            }

            // Three people haven't given bank details yet — they're paid by cheque.
            if (! in_array($key, ['support2', 'sales4', 'eng6'], true)) {
                StaffBankAccount::create([
                    'user_id' => $person->id,
                    'account_holder_name' => $person->name,
                    'bank_name' => ['Chase', 'Bank of America', 'Wells Fargo', 'Citibank'][crc32($key) % 4],
                    'bank_country' => 'US',
                    'account_number' => (string) (100000000 + crc32($key) % 899999999),
                    'routing_number' => '021000021',
                    'account_currency' => 'USD',
                ]);
            }
        }
    }

    private function hiringAndLeaving(): void
    {
        $hr = $this->people['hr'];
        $cfo = $this->people['cfo'];
        $resignation = DepartureReason::where('name', 'Resignation')->value('id');

        // Joined 8 days ago: onboarding approved, checklist part-done, one task overdue.
        $recent = $this->makePerson('recent', 'Leah Goldberg', 'Software Engineer', 'Austin', 'engmgr', $this->today->subDays(8), 'active');
        $request = $this->onboarding->submit($recent, $hr);
        $this->onboarding->approve($request, $cfo);
        $checklist = StaffChecklist::where('user_id', $recent->id)->where('type', 'onboarding')->first();
        foreach ($checklist?->tasks ?? [] as $i => $task) {
            if ($i < 2) {
                $this->checklists->updateTask($task, $task->assignee_user_id ? User::find($task->assignee_user_id) : $hr, 'done');
            }
        }

        // Starts in 10 days: approved, checklist under way.
        $starter = $this->makePerson('starter', 'Noah Williams', 'Account Executive', 'New York HQ', 'salesmgr', $this->today->addDays(10), 'active');
        $this->onboarding->approve($this->onboarding->submit($starter, $hr), $cfo);

        // Starts in 3 weeks: still waiting for level-2 approval.
        $candidate = $this->makePerson('candidate', 'Fatima Zahra', 'Customer Support', 'Chicago', 'admin', $this->today->addDays(21), 'active');
        $this->onboarding->submit($candidate, $hr);

        // Leaving in two weeks: approved, offboarding checklist running.
        $leaver = $this->people['sales2'];
        $exit = $this->offboarding->submit($leaver, $hr, [
            'last_working_day' => $this->today->addDays(14)->toDateString(),
            'departure_reason_id' => $resignation,
            'note' => 'Returning to studies',
        ]);
        $this->offboarding->approve($exit, $cfo);

        // A leaver HR has put forward, waiting for level-2 sign-off.
        $this->offboarding->submit($this->people['support1'], $hr, [
            'last_working_day' => $this->today->addDays(30)->toDateString(),
            'departure_reason_id' => DepartureReason::where('name', 'End of contract')->value('id'),
        ]);

        // The recent joiner needs a salary too.
        $salary = StaffSalary::create(['user_id' => $recent->id, 'effective_from' => $recent->joined_at, 'pay_frequency' => 'monthly', 'currency' => 'USD']);
        $salary->components()->create(['pay_component_id' => PayComponent::basic()->id, 'value' => 8000]);
        $salary->components()->create(['pay_component_id' => PayComponent::where('code', 'TAX_EST')->value('id'), 'value' => 20]);
    }

    /**
     * Three approved monthly runs, then this month's run with some payslips
     * approved at level 1 and the rest still drafts.
     */
    private function payroll(): void
    {
        $l1 = $this->people['hr'];
        $l2 = $this->people['cfo'];

        // Last month's bonuses.
        foreach (['eng1', 'sales3', 'salesmgr'] as $key) {
            SalaryAdjustment::create([
                'user_id' => $this->people[$key]->id,
                'type' => 'credit',
                'amount' => 750 + crc32($key) % 1000,
                'title' => 'Quarterly performance bonus',
                'effective_date' => $this->today->subMonthNoOverflow()->startOfMonth()->addDays(20),
                'created_by' => $this->people['payroll']->id,
            ]);
        }

        foreach ([3, 2, 1, 0] as $monthsAgo) {
            $period = PayPeriod::for('monthly', $this->today->subMonthsNoOverflow($monthsAgo)->startOfMonth());
            $run = PayRun::create([
                'frequency' => 'monthly',
                'period_start' => $period->start,
                'period_end' => $period->end,
                'pay_date' => $period->end,
                'status' => 'processing',
                'created_by' => $this->people['payroll']->id,
            ]);

            foreach ($this->payslips->eligibleStaff($run) as $person) {
                $this->payslips->generate($run, $person);
            }

            $run->update(['status' => 'completed', 'payslips_count' => $run->payslips()->count(), 'completed_at' => now()]);

            $ids = $run->payslips()->orderBy('id')->pluck('id')->all();

            if ($monthsAgo > 0) {
                $this->approvals->approve($ids, $l1);
                $this->approvals->approve($ids, $l2);
                // Nobody approves their own pay: the CFO's own payslip needs
                // the other senior approver.
                $this->approvals->approve($ids, $this->people['ceo']);

                // Older cheques have numbers; last month's are still to do.
                if ($monthsAgo > 1) {
                    Payslip::where('pay_run_id', $run->id)->where('payment_method', 'cheque')->get()
                        ->each(fn (Payslip $p) => $this->approvals->saveChequeNumber($p, 'CHQ-'.$run->period_start->format('ym').'-'.$p->id));
                }
            } else {
                // This month: half through level 1, the rest still drafts.
                $this->approvals->approve(array_slice($ids, 0, intdiv(count($ids), 2)), $l1);
            }
        }

        // A query on last month's payslip.
        $payslip = Payslip::where('user_id', $this->people['eng4']->id)
            ->whereDate('period_start', $this->today->subMonthNoOverflow()->startOfMonth())
            ->first();

        if ($payslip) {
            SalaryClaim::create([
                'payslip_id' => $payslip->id,
                'user_id' => $payslip->user_id,
                'reason' => 'My on-call weekend (the 14th–15th) doesn’t seem to be included.',
            ]);
        }
    }

    /**
     * Three weeks of attendance: mostly on time, some late arrivals and early
     * leavers, a missed check-out, a few clock-ins far from the office (to
     * review), paid leave and an open correction request. Punches are written
     * directly (the live clock's locking and look-ups made the nightly
     * rebuild ten times slower), then every day is judged by the real
     * DayEvaluator and DayCloser, so the same rules apply as in real use.
     * Varies by person and date, but the same on every rebuild.
     */
    private function attendance(): void
    {
        TenantSetting::set('attendance_started_on', $this->today->subDays(21)->toDateString());

        foreach ([
            'New York HQ' => [40.7484, -73.9857],
            'Chicago' => [41.8787, -87.6360],
            'Austin' => [30.2653, -97.7470],
        ] as $name => [$lat, $lng]) {
            $this->branches[$name]->update(['latitude' => $lat, 'longitude' => $lng, 'geofence_radius_m' => 200]);
        }

        $holidays = $this->usHolidays((int) $this->today->format('Y')) + $this->usHolidays((int) $this->today->format('Y') + 1);

        foreach ($holidays as $date => $name) {
            Holiday::create(['date' => $date, 'name' => $name]);
        }

        $hr = $this->people['hr'];
        $onLeave = [$this->today->subDays(9)->toDateString(), $this->today->subDays(8)->toDateString()];

        // Everyone active — including people hired through the onboarding flow above.
        foreach (User::where('status', 'active')->orderBy('id')->get() as $person) {
            $key = array_search($person->id, array_map(fn (User $u) => $u->id, $this->people), true) ?: $person->email;

            $branch = $this->branches[$this->branchOf($person)];

            // Remote staff don't clock (the close-out records them as remote);
            // caregivers check in at each visit instead (homeCare()).
            for ($date = $this->today->subDays(21); ! $person->is_remote && $person->attendance_mode !== 'visits' && $date->lt($this->today); $date = $date->addDay()) {
                $day = $date->toDateString();

                if ($date->isWeekend() || isset($holidays[$day]) || ($person->joined_at && $person->joined_at->toDateString() > $day)) {
                    continue;
                }

                if ($key === 'sales3' && in_array($day, $onLeave, true)) {
                    $this->attendanceEditor->setStatus($person, $day, 'on_leave', true, 'Annual leave', $hr);

                    continue;
                }

                $r = crc32($key.$day) % 100;

                if ($r < 3) {
                    continue; // absent
                }

                $late = $r >= 5 && $r < 11;                                          // about 1 day in 16
                $in = $late ? 9 * 60 + 12 + $r * 2 : 8 * 60 + 40 + $r % 18;
                $out = $r >= 15 && $r < 19 ? 15 * 60 + 30 : 17 * 60 + 2 + $r % 35;   // left early, or a full day

                // 3–4: forgot to clock out; 96+: clocked in away from the office.
                $this->demoDay($person, $branch, $day, $in, $r < 5 ? null : $out, far: $r >= 96);
            }

            $this->dayCloser->closeFor($person);
        }

        AttendanceCorrection::create([
            'user_id' => $this->people['eng4']->id,
            'date' => $this->today->subWeekdays(2)->toDateString(),
            'requested_in' => '09:00',
            'requested_out' => '17:30',
            'reason' => 'My phone ran out of battery, so I couldn’t clock out.',
        ]);
    }

    /**
     * A small home-care team (the Kindstead case): three caregivers based in
     * Chicago who visit six clients at home, 3–4 visits a day on repeating
     * plans, checked in and out with the phone's location. Paid by the hour
     * of verified visit time, so this runs before payroll(). Mostly on time
     * and at the door; one check-in far away (to review), the odd missed
     * visit and forgotten check-out. A coordinator (the office manager)
     * plans and reviews visits.
     */
    private function homeCare(): void
    {
        $chicago = $this->branches['Chicago'];
        $chicago->update(['latitude' => 41.8787, 'longitude' => -87.6360, 'geofence_radius_m' => 200]);
        $joined = $this->today->subDays(40);

        $coordinator = Role::create(['name' => 'Care coordinator', 'guard_name' => 'sanctum']);
        $coordinator->givePermissionTo('HRMS-VisitPlanning');
        $this->people['office']->assignRole($coordinator);

        $carers = [];
        foreach ([['care1', 'Ama Boateng'], ['care2', 'Luis Herrera'], ['care3', 'Grace O’Neill']] as [$key, $name]) {
            $carers[$key] = $this->makePerson($key, $name, 'Care Worker', 'Chicago', 'office', $joined, 'active');
            $carers[$key]->forceFill(['attendance_mode' => 'visits', 'is_remote' => false])->save();
        }

        $hourly = PayComponent::create(['name' => 'Care visits', 'code' => 'CARE_HOURS', 'type' => 'earning', 'calculation' => 'per_hour', 'sequence' => 5]);
        $tax = PayComponent::where('code', 'TAX_EST')->first() ?? PayComponent::create(['name' => 'Income tax (estimate)', 'code' => 'TAX_EST', 'type' => 'deduction', 'calculation' => 'percent_of_gross', 'sequence' => 80]);

        foreach ($carers as $key => $carer) {
            $salary = StaffSalary::create(['user_id' => $carer->id, 'effective_from' => $joined, 'pay_frequency' => 'monthly', 'currency' => 'USD', 'created_by' => $this->people['payroll']->id]);
            $salary->components()->create(['pay_component_id' => $hourly->id, 'value' => [21.5, 20, 22.75][crc32($key) % 3]]);
            $salary->components()->create(['pay_component_id' => $tax->id, 'value' => 12]);
            StaffBankAccount::create([
                'user_id' => $carer->id, 'account_holder_name' => $carer->name, 'bank_name' => 'Chase', 'bank_country' => 'US',
                'account_number' => (string) (100000000 + crc32($key) % 899999999), 'routing_number' => '021000021', 'account_currency' => 'USD',
            ]);
        }

        // Six clients a short drive apart on Chicago's north side (sample addresses).
        $clients = [];
        foreach ([
            ['walsh', 'Mrs E. Walsh', '1520 N Dearborn Pkwy', 41.9103, -87.6310],
            ['delgado', 'Mr R. Delgado', '2210 N Lincoln Ave', 41.9222, -87.6490],
            ['novak', 'Mrs M. Novak', '615 W Wellington Ave', 41.9362, -87.6440],
            ['brooks', 'Mr T. Brooks', '3030 N Broadway', 41.9372, -87.6446],
            ['okoye', 'Mrs A. Okoye', '1050 W Addison St', 41.9474, -87.6555],
            ['lindqvist', 'Mr H. Lindqvist', '4400 N Clarendon Ave', 41.9612, -87.6497],
        ] as [$key, $name, $address, $lat, $lng]) {
            $clients[$key] = VisitLocation::create([
                'branch_id' => $chicago->id, 'name' => $name, 'reference' => 'CL-'.strtoupper(substr($key, 0, 3)), 'address' => $address,
                'city' => 'Chicago', 'postcode' => '606'.(10 + crc32($key) % 40), 'latitude' => $lat, 'longitude' => $lng, 'geofence_radius_m' => 150,
            ]);
        }

        // [carer, client, weekdays, start, end]
        foreach ([
            ['care1', 'walsh', [1, 2, 3, 4, 5], '08:00', '09:00'],
            ['care1', 'delgado', [1, 2, 3, 4, 5], '09:30', '10:30'],
            ['care1', 'novak', [1, 3, 5], '11:00', '12:00'],
            ['care1', 'brooks', [2, 4], '11:00', '12:30'],
            ['care2', 'okoye', [1, 2, 3, 4, 5, 6, 7], '08:00', '08:45'],
            ['care2', 'lindqvist', [1, 2, 3, 4, 5], '10:00', '11:00'],
            ['care2', 'walsh', [1, 2, 3, 4, 5], '17:00', '17:45'],
            ['care2', 'delgado', [6], '10:00', '11:00'],
            ['care3', 'novak', [2, 4], '14:00', '15:00'],
            ['care3', 'brooks', [1, 3, 5], '14:00', '15:00'],
            ['care3', 'okoye', [1, 2, 3, 4, 5], '18:00', '18:45'],
            ['care3', 'lindqvist', [6, 7], '09:00', '10:00'],
        ] as [$carer, $client, $days, $start, $end]) {
            $plan = VisitPlan::create([
                'visit_location_id' => $clients[$client]->id, 'user_id' => $carers[$carer]->id, 'weekdays' => $days,
                'start_time' => $start, 'end_time' => $end, 'starts_on' => $joined->toDateString(), 'created_by' => $this->people['office']->id,
            ]);
            $from = CarbonImmutable::parse($joined->toDateString(), $chicago->timezone);
            $this->visitScheduler->generateFor($plan->load(['staff', 'location']), $from, (int) $from->diffInDays(CarbonImmutable::now($chicago->timezone)) + VisitScheduler::DAYS_AHEAD);
        }

        // What happened on the visits so far.
        $now = CarbonImmutable::now();
        foreach (Visit::with('location')->where('planned_end', '<', $now)->orderBy('planned_start')->get() as $visit) {
            $r = crc32('visit'.$visit->id) % 100;

            if ($r < 2) {
                continue; // missed: settled as missed below
            }

            $far = $r >= 98;
            $in = $visit->planned_start->addMinutes($r % 14 - 4);
            $point = fn (bool $away) => ['lat' => $visit->location->latitude + ($away ? 0.021 : 0.0002), 'lng' => $visit->location->longitude + ($away ? 0 : 0.0001)];
            $distance = fn (array $pt) => Geo::distanceMetres($pt['lat'], $pt['lng'], $visit->location->latitude, $visit->location->longitude);
            $checkIn = $point($far);

            $facts = [
                'checked_in_at' => $in, 'check_in_latitude' => $checkIn['lat'], 'check_in_longitude' => $checkIn['lng'], 'check_in_accuracy_m' => 12,
                'check_in_distance_m' => $distance($checkIn), 'check_in_within_range' => ! $far, 'review_status' => $far ? 'needs_review' : null,
            ];

            if ($r < 4 && $now->gte($visit->planned_end->addHours(4))) {
                // Forgot to check out: the settler flags it with no time until a coordinator sets it.
                $visit->update($facts + ['status' => 'in_progress']);

                continue;
            }

            $out = $visit->planned_end->addMinutes($r % 9);
            $checkOut = $point(false);
            $visit->update($facts + [
                'status' => 'completed', 'checked_out_at' => $out, 'check_out_latitude' => $checkOut['lat'], 'check_out_longitude' => $checkOut['lng'],
                'check_out_accuracy_m' => 10, 'check_out_distance_m' => $distance($checkOut), 'check_out_within_range' => true,
                'worked_minutes' => (int) floor(($out->getTimestamp() - $in->getTimestamp()) / 60),
                'caregiver_note' => $r % 11 === 0 ? 'Client in good spirits.' : null,
            ]);
        }

        // Settle each caregiver's finished days from their visits.
        foreach ($carers as $carer) {
            for ($date = $joined; $date->lt($this->today); $date = $date->addDay()) {
                $this->visitSettler->settle($carer, $this->workCalendar->expectationFor($carer, $date->toDateString()), isOver: true, now: $now);
            }
        }
    }

    /** One clocked day: in (and out) at minutes past local midnight, judged by the real rules. */
    private function demoDay(User $person, Branch $branch, string $day, int $in, ?int $out, bool $far): void
    {
        $midnight = CarbonImmutable::parse($day, $branch->timezone);
        $record = AttendanceDay::create([
            'user_id' => $person->id, 'date' => $day, 'status' => 'open', 'source' => 'app', 'branch_id' => $branch->id, 'closed_at' => now(),
        ]);

        $punches = [];

        foreach (array_filter(['in' => $in, 'out' => $out], fn ($m) => $m !== null) as $type => $minutes) {
            $lat = $branch->latitude + ($far && $type === 'in' ? 0.02 : 0.0003);
            $distance = Geo::distanceMetres($lat, $branch->longitude, $branch->latitude, $branch->longitude);
            $punches[] = [
                'tenant_id' => $this->tenant->id, 'user_id' => $person->id, 'attendance_day_id' => $record->id, 'type' => $type,
                'punched_at' => $midnight->addMinutes($minutes)->utc(), 'branch_id' => $branch->id, 'latitude' => $lat, 'longitude' => $branch->longitude,
                'accuracy_m' => 15, 'distance_m' => $distance, 'within_range' => $distance <= $branch->geofence_radius_m, 'source' => 'app',
                'created_at' => now(), 'updated_at' => now(),
            ];
        }

        AttendancePunch::insert($punches);

        $this->dayEvaluator->evaluate($record, $this->workCalendar->expectationFor($person, $day), $person, isOver: true);
    }

    private function branchOf(User $person): string
    {
        $branchId = UserBranch::where('user_id', $person->id)->orderByDesc('status')->value('branch_id');

        return collect($this->branches)->search(fn (Branch $b) => $b->id === $branchId) ?: 'New York HQ';
    }

    /** @return array<string, string> the US federal holidays a small company typically observes */
    private function usHolidays(int $year): array
    {
        $month = fn (int $m) => CarbonImmutable::create($year, $m, 1);

        return [
            "{$year}-01-01" => 'New Year’s Day',
            $month(1)->nthOfMonth(3, CarbonInterface::MONDAY)->toDateString() => 'Martin Luther King Jr. Day',
            $month(5)->lastOfMonth(CarbonInterface::MONDAY)->toDateString() => 'Memorial Day',
            "{$year}-07-04" => 'Independence Day',
            $month(9)->firstOfMonth(CarbonInterface::MONDAY)->toDateString() => 'Labor Day',
            $month(11)->nthOfMonth(4, CarbonInterface::THURSDAY)->toDateString() => 'Thanksgiving',
            "{$year}-12-25" => 'Christmas Day',
        ];
    }

    private function activity(): void
    {
        foreach ([['admin', 0], ['hr', 1], ['payroll', 1], ['cfo', 2], ['hr', 3], ['admin', 4]] as [$key, $daysAgo]) {
            LoginLog::create([
                'user_id' => $this->people[$key]->id,
                'ip_address' => '203.0.113.'.(10 + $daysAgo),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/140.0',
                'device_name' => 'web',
                'login_at' => now()->subDays($daysAgo)->setTime(9 + $daysAgo, 12),
            ]);
        }
    }

    /**
     * The demo is a company that has finished setting up.
     */
    private function finishSetup(User $admin): void
    {
        foreach (['departure_reasons', 'checklist_templates', 'pay_components', 'work_schedules', 'attendance_rules'] as $key) {
            SetupStepState::create(['step_key' => $key, 'state' => 'reviewed', 'updated_by' => $admin->id]);
        }

        // Remembered so the demo guard can protect this account.
        TenantSetting::set('demo_admin_user_id', $admin->id);
    }
}
