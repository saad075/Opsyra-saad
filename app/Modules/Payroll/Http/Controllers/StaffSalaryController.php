<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Services\SalaryCalculator;
use App\Modules\Payroll\Support\Money;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Models\TenantSetting;
use Illuminate\Http\Request;
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

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'effective_from' => ['required', 'date'],
            'pay_frequency' => ['required', ValidationRule::in(StaffSalary::FREQUENCIES)],
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
     * The salary plus what one full pay period comes to — the same
     * calculation a payslip uses.
     */
    private function present(StaffSalary $salary): array
    {
        return $salary->load('components.component:id,name,code,type,calculation')->toArray()
            + ['per_period' => $this->calculator->calculate($salary)];
    }
}
