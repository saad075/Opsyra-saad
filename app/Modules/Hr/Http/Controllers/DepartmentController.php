<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\Department;
use App\Modules\Hr\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Adapted from SLMS360's app/Http/Controllers/Client/DepartmentController.php
 * (see docs/provenance.md). Changes: tenant scoping now comes for free from
 * BelongsToTenant + RLS instead of explicit ->where('client_id', ...) calls;
 * mutating actions are gated behind the 'manage-hr' ability (SLMS360 had no
 * permission check at all here — any authenticated staff member could manage
 * departments/designations); department_id / this-department's-id existence
 * checks are validated within the tenant explicitly.
 */
class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $data = Department::withCount('designations')->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'message' => 'Departments retrieved successfully',
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('departments')->where('tenant_id', $tenantId)],
            'description' => ['nullable', 'string'],
        ]);

        Department::create($validated);

        return response()->json(['status' => true, 'message' => 'Department created successfully.'], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', Rule::unique('departments')->where('tenant_id', $tenantId)->ignore($request->id)],
            'description' => ['nullable', 'string'],
        ]);

        $department = Department::findOrFail($validated['id']);
        $department->update(['name' => $validated['name'], 'description' => $validated['description'] ?? null]);

        return response()->json(['status' => true, 'message' => 'Department updated successfully.']);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
        ]);

        $department = Department::findOrFail($validated['id']);

        if ($department->designations()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'This department still has job titles in it. Move or delete them first.',
            ], 409);
        }

        $department->delete();

        return response()->json(['status' => true, 'message' => 'Department deleted successfully.']);
    }

    // Designations — kept in the same controller since they're a small, tightly related pair.

    public function designationsList(Request $request)
    {
        $data = Designation::with('department:id,name')->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'message' => 'Designations retrieved successfully',
            'data' => $data,
        ]);
    }

    public function storeDesignation(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('designations')->where('tenant_id', $tenantId)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
        ]);

        Designation::create($validated);

        return response()->json(['status' => true, 'message' => 'Designation created successfully.'], 201);
    }

    public function updateDesignation(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', Rule::exists('designations', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', Rule::unique('designations')->where('tenant_id', $tenantId)->ignore($request->id)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
        ]);

        $designation = Designation::findOrFail($validated['id']);
        $designation->update([
            'name' => $validated['name'],
            'department_id' => $validated['department_id'] ?? null,
        ]);

        return response()->json(['status' => true, 'message' => 'Designation updated successfully.']);
    }

    public function destroyDesignation(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', Rule::exists('designations', 'id')->where('tenant_id', $tenantId)],
        ]);

        Designation::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'Designation deleted successfully.']);
    }
}
