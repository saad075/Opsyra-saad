<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\UserBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Branch management — closes the gap noted when the HR core module was
 * ported (the table existed, but only as a staff dependency). A branch with
 * staff assigned can be deactivated, not deleted.
 */
class BranchController extends Controller
{
    public function index(Request $request)
    {
        $data = Branch::withCount('userBranches as staff_count')
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('status', true))
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Branches', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-hr');

        $branch = Branch::create($this->validated($request) + ['status' => true]);

        return response()->json(['status' => true, 'message' => 'Branch created.', 'data' => $branch], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-hr');

        $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('branches', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'status' => ['nullable', 'boolean'],
        ]);

        $branch = Branch::findOrFail($request->id);
        $branch->update($this->validated($request, $branch) + ['status' => $request->boolean('status', $branch->status)]);

        return response()->json(['status' => true, 'message' => 'Branch updated.', 'data' => $branch]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-hr');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('branches', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        if (UserBranch::where('branch_id', $validated['id'])->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Staff are assigned to this branch. Deactivate it instead.',
            ], 409);
        }

        Branch::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'Branch deleted.']);
    }

    private function validated(Request $request, ?Branch $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('branches')->where('tenant_id', $request->user()->tenant_id)->ignore($existing?->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'timezone:all'],
        ]);
    }
}
