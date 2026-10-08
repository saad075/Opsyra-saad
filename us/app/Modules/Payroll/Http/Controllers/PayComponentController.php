<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\StaffSalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * New — replaces SLMS360's fixed salary columns. A component's type and
 * calculation are fixed once created: salaries and payslips refer to it by
 * meaning, so "change Medical from an earning to a deduction" is really a
 * new component. The single Basic component is seeded and can't be made
 * or unmade through the API.
 */
class PayComponentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-salaries');

        $data = PayComponent::query()
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('type')
            ->orderBy('sequence')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Pay components', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-salaries');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('pay_components')->where('tenant_id', $tenantId)],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z][A-Z0-9_]*$/', ValidationRule::unique('pay_components')->where('tenant_id', $tenantId)],
            'type' => ['required', ValidationRule::in(PayComponent::TYPES)],
            // % of gross only makes sense once gross is known, i.e. for
            // deductions; paying by the hour only for earnings.
            'calculation' => ['required', ValidationRule::in($request->type === 'earning' ? ['fixed', 'percent_of_basic', 'per_hour'] : ['fixed', 'percent_of_basic', 'percent_of_gross'])],
            'prorate' => ['nullable', 'boolean'],
            'sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $component = PayComponent::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? Str::upper(Str::snake(Str::ascii($validated['name']))),
            'type' => $validated['type'],
            'calculation' => $validated['calculation'],
            'prorate' => $validated['prorate'] ?? true,
            'sequence' => $validated['sequence'] ?? 50,
        ]);

        return response()->json(['status' => true, 'message' => 'Pay component created.', 'data' => $component], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-salaries');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('pay_components', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('pay_components')->where('tenant_id', $tenantId)->ignore($request->id)],
            'prorate' => ['nullable', 'boolean'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $component = PayComponent::findOrFail($validated['id']);

        if ($component->is_basic && array_key_exists('is_active', $validated) && ! $validated['is_active']) {
            return response()->json(['status' => false, 'message' => 'Basic pay cannot be deactivated.'], 409);
        }

        $component->update([
            'name' => $validated['name'],
            'prorate' => $validated['prorate'] ?? $component->prorate,
            'sequence' => $validated['sequence'] ?? $component->sequence,
            'is_active' => $validated['is_active'] ?? $component->is_active,
        ]);

        return response()->json(['status' => true, 'message' => 'Pay component updated.', 'data' => $component]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('pay_components', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $component = PayComponent::findOrFail($validated['id']);

        if ($component->is_system || StaffSalaryComponent::where('pay_component_id', $component->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'This component is built in or used on a salary. Deactivate it instead.',
            ], 409);
        }

        $component->delete();

        return response()->json(['status' => true, 'message' => 'Pay component deleted.']);
    }
}
