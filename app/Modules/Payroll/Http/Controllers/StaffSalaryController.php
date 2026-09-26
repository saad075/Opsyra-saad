<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Models\StaffSalaryComponent;
use App\Modules\Payroll\Services\SalaryCalculator;
use App\Modules\Payroll\Support\Money;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * Adapted from SLMS360's SalaryController::update()/detail(). SLMS360's
 * update() had no permission check (anyone could change anyone's pay) and
 * detail($id) returned any salary by user id. Both are gated here, and a
 * salary is now effective-dated rather than overwritten.
 */
class StaffSalaryController extends Controller
{
    public function __construct(private SalaryCalculator $calculator) {}

    /**
     * Creates the salary effective from a date, or replaces the one already
     * starting on that exact date (correcting a mistake rather than a raise).
     */
    public function set(Request $request)
    {
        $this->authorize('manage-salaries');

        [$validated, , $currency] = $this->validateSalary($request, requireStaff: true);

        $salary = DB::transaction(function () use ($validated, $currency, $request) {
            $salary = StaffSalary::updateOrCreate(
                ['user_id' => $validated['user_id'], 'effective_from' => $validated['effective_from']],
                [
                    'pay_frequency' => $validated['pay_frequency'],
                    'currency' => $currency,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ],
            );

            $salary->components()->delete();

            foreach ($validated['components'] as $line) {
                $salary->components()->create($line);
            }

            return $salary;
        });

        return response()->json([
            'status' => true,
            'message' => 'Salary saved.',
            'data' => $this->present($salary),
        ], 201);
    }

    /**
     * What a salary would come to per full period, without saving it — the
     * editor's live preview. Same validation and calculation as set().
     */
    public function preview(Request $request)
    {
        $this->authorize('manage-salaries');

        [$validated, $components, $currency] = $this->validateSalary($request, requireStaff: false);

        $items = collect($validated['components'])->map(function (array $line) use ($components) {
            $item = new StaffSalaryComponent($line);
            $item->setRelation('component', $components[$line['pay_component_id']]);

            return $item;
        });

        return response()->json(['status' => true, 'message' => 'Preview', 'data' => $this->calculator->calculateItems($items, $currency)]);
    }

    /**
     * Everyone who should be paid, with their current salary (and what it
     * comes to) and whether bank details are on file — the Salaries screen.
     */
    public function overview()
    {
        $this->authorize('manage-salaries');

        $banked = StaffBankAccount::pluck('user_id')->flip();

        $data = User::whereIn('status', ['active', 'pending'])
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'status', 'joined_at'])
            ->map(function (User $user) use ($banked) {
                $salary = StaffSalary::effectiveFor($user->id, today())
                    // Someone starting soon: the salary they'll start on.
                    ?? StaffSalary::where('user_id', $user->id)->orderBy('effective_from')->first();
                $period = $salary ? $this->calculator->calculate($salary) : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'designation' => $user->designation,
                    'status' => $user->status,
                    'has_bank_account' => $banked->has($user->id),
                    'salary' => $salary ? [
                        'id' => $salary->id,
                        'effective_from' => $salary->effective_from,
                        'pay_frequency' => $salary->pay_frequency,
                        'currency' => $salary->currency,
                        'gross' => $period['gross'],
                        'net' => $period['net'],
                    ] : null,
                ];
            });

        return response()->json(['status' => true, 'message' => 'Salaries', 'data' => $data]);
    }

    public function history(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $data = StaffSalary::with(['components.component:id,name,code,type,calculation', 'createdBy:id,name'])
            ->where('user_id', $validated['user_id'])
            ->orderByDesc('effective_from')
            ->get();

        return response()->json(['status' => true, 'message' => 'Salary history', 'data' => $data]);
    }

    public function current(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        return $this->respondWithCurrent(User::findOrFail($validated['user_id']));
    }

    /**
     * A staff member's own current salary — the one read open to everyone.
     */
    public function mine(Request $request)
    {
        return $this->respondWithCurrent($request->user());
    }

    private function respondWithCurrent(User $staff)
    {
        $salary = StaffSalary::effectiveFor($staff->id, today());

        return response()->json([
            'status' => true,
            'message' => $salary ? 'Current salary' : 'No salary set up yet.',
            'data' => $salary ? $this->present($salary) : null,
        ]);
    }

    /**
     * @return array{0: array, 1: Collection<int, PayComponent>, 2: string} [validated, components by id, currency]
     */
    private function validateSalary(Request $request, bool $requireStaff): array
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'user_id' => [$requireStaff ? 'required' : 'nullable', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'effective_from' => [$requireStaff ? 'required' : 'nullable', 'date'],
            'pay_frequency' => [$requireStaff ? 'required' : 'nullable', ValidationRule::in(StaffSalary::FREQUENCIES)],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.pay_component_id' => [
                'required', 'integer', 'distinct',
                ValidationRule::exists('pay_components', 'id')->where('tenant_id', $tenantId)->where('is_active', true),
            ],
            'components.*.value' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ]);

        $components = PayComponent::whereIn('id', collect($validated['components'])->pluck('pay_component_id'))->get()->keyBy('id');

        if (! $components->contains('is_basic', true)) {
            throw ValidationException::withMessages(['components' => 'Every salary needs a basic pay amount.']);
        }

        foreach ($validated['components'] as $i => $line) {
            if ($components[$line['pay_component_id']]->calculation !== 'fixed' && $line['value'] > 100) {
                throw ValidationException::withMessages(["components.{$i}.value" => 'A percentage cannot be more than 100.']);
            }
        }

        $currency = strtoupper($validated['currency']
            ?? TenantSetting::get('currency')
            ?? Money::currencyForCountry(Tenant::findOrFail($tenantId)->country));

        return [$validated, $components, $currency];
    }

    /**
     * The salary plus what one full pay period comes to — the same
     * calculation a payslip uses.
     */
    private function present(StaffSalary $salary): array
    {
        return $salary->load('components.component:id,name,code,type,calculation')->toArray()
            + ['per_period' => $this->calculator->calculate($salary)];
    }
}
