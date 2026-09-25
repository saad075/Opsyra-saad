<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Hr\Models\UserBranch;
use App\Modules\Hr\Models\UserUpdateLog;
use App\Modules\Rbac\Models\Role;
use App\Modules\Tenancy\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's app/Http/Controllers/Client/StaffController.php
 * (1098 lines; see docs/provenance.md for the exact method-by-method
 * mapping). This is the plain-CRUD subset only — onboarding/offboarding
 * approval workflow (StaffOnboardingRequest, StaffOffboardRequest,
 * ApprovalHistory, the "L2 gate", the attendance backfill on approval) is a
 * separate module, not yet ported; create() here always activates a staff
 * record directly (or leaves it 'pending' if joined_at is in the future) with
 * no approval gate. The next module layers that gate on top rather than this
 * one half-building it.
 *
 * Also not ported here (deferred to the module that owns them):
 * UserSalary creation (Salaries Hub), the account-creation email
 * (no email templates ported yet), attendance_shift_id/annual_leaves_allowed
 * (Attendance Hub), country_id (no Country model ported).
 *
 * Security fixes made while porting (see docs/decisions.md for the general
 * pattern): every mutating action is gated behind the 'manage-hr' or
 * 'manage-staff-member' ability (SLMS360 checked no permission at all for
 * plain CRUD); activeDirectReports/updateAvatar/clearDeviceId had zero
 * tenant scoping in SLMS360 — BelongsToTenant + RLS close that automatically
 * here, but findOrFail() on an explicitly tenant-scoped Rule::exists() also
 * gives a clean 422 instead of relying on it implicitly; branches/role_id/
 * hierarchy_parent_id/user_id are all validated within the tenant, not
 * globally.
 */
class StaffController extends Controller
{
    public function list(Request $request)
    {
        $branchIds = $this->mySelectedBranchIds($request);

        $query = User::with(['branches.branch', 'permissions', 'roles'])
            ->whereHas('branches', fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 0 END DESC")
            ->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => true,
            'message' => 'Staff lists',
            'data' => $query->get(),
        ]);
    }

    public function index(Request $request)
    {
        $branchIds = $this->mySelectedBranchIds($request);

        $query = User::with(['branches.branch', 'permissions', 'roles'])
            ->whereHas('branches', fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->where('status', $request->filled('status') ? $request->status : 'active')
            ->orderBy('name');

        return response()->json([
            'status' => true,
            'message' => 'Staff lists',
            'data' => $query->get(),
        ]);
    }

    public function limitedList(Request $request)
    {
        $branchIds = $this->mySelectedBranchIds($request);

        $query = User::whereHas('branches', fn ($q) => $q->whereIn('branch_id', $branchIds));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => true,
            'message' => 'Staff lists with limited results',
            'data' => $query->orderBy('updated_at', 'desc')->get(['id', 'name', 'designation', 'status']),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', ValidationRule::unique('users')->where('tenant_id', $tenantId)],
            'phone' => ['required', 'string'],
            'designation' => ['required', 'string'],
            'joined_at' => ['required', 'date'],
            'is_remote' => ['required', 'boolean'],
            'dob' => ['required', 'date'],
            'hierarchy_parent_id' => ['nullable', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => [ValidationRule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'role_id' => ['nullable', ValidationRule::exists('roles', 'id')->where('tenant_id', $tenantId)->where('guard_name', 'sanctum')],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'national_id' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string'],
        ]);

        $status = Carbon::parse($validated['joined_at'])->startOfDay()->gt(now()->startOfDay())
            ? 'pending'
            : 'active';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::password(16),
            'email_verified_at' => now(),
            'phone' => $validated['phone'],
            'designation' => $validated['designation'],
            'dob' => $validated['dob'],
            'joined_at' => $validated['joined_at'],
            'is_remote' => $validated['is_remote'],
            'hierarchy_parent_id' => $validated['hierarchy_parent_id'] ?? null,
            'status' => $status,
            'national_id' => $validated['national_id'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
        ]);

        if (! empty($validated['permissions'])) {
            $this->authorize('update-staff-permissions');
            $user->syncPermissions($validated['permissions']);
        }

        if (! empty($validated['role_id'])) {
            $this->authorize('update-staff-permissions');
            $role = Role::findById($validated['role_id'], 'sanctum');
            $user->syncRoles([$role->name]);
        }

        foreach ($validated['branches'] as $branchId) {
            UserBranch::create(['user_id' => $user->id, 'branch_id' => $branchId, 'status' => false]);
        }
        $user->branches()->first()?->update(['status' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Staff has been created successfully.',
            'data' => ['id' => $user->id],
        ], 201);
    }

    public function update(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'user_id' => ['required', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'is_remote' => ['required', 'boolean'],
            'dob' => ['required', 'date'],
            'designation' => ['required', 'string'],
            'joined_at' => ['required', 'date'],
            'hierarchy_parent_id' => ['nullable', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => [ValidationRule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'team_name' => ['nullable', 'string'],
            'national_id' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string'],
            'celebrate_birthday' => ['nullable', 'boolean'],
            'justify_work_hours' => ['nullable', 'boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'role_id' => ['nullable', ValidationRule::exists('roles', 'id')->where('tenant_id', $tenantId)->where('guard_name', 'sanctum')],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $this->authorize('manage-staff-member', $user);

        if ($user->email !== $validated['email']) {
            $request->validate([
                'email' => [ValidationRule::unique('users')->where('tenant_id', $tenantId)->ignore($user->id)],
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'designation' => $validated['designation'],
            'team_name' => $validated['team_name'] ?? null,
            'dob' => $validated['dob'],
            'joined_at' => $validated['joined_at'],
            'is_remote' => $validated['is_remote'],
            'hierarchy_parent_id' => $validated['hierarchy_parent_id'] ?? null,
            'national_id' => $validated['national_id'] ?? $user->national_id,
            'blood_group' => $validated['blood_group'] ?? $user->blood_group,
            'celebrate_birthday' => $validated['celebrate_birthday'] ?? $user->celebrate_birthday,
            'justify_work_hours' => $validated['justify_work_hours'] ?? $user->justify_work_hours,
            'address' => $validated['address'] ?? $user->address,
            'city' => $validated['city'] ?? $user->city,
            'zip_code' => $validated['zip_code'] ?? $user->zip_code,
        ]);

        if ($request->has('permissions')) {
            $this->authorize('update-staff-permissions');
            $this->logPermissionChanges($request, $user, $validated['permissions'] ?? []);
            $user->syncPermissions($validated['permissions'] ?? []);
        }

        if ($request->has('role_id')) {
            $this->authorize('update-staff-permissions');

            if (! empty($validated['role_id'])) {
                $role = Role::findById($validated['role_id'], 'sanctum');
                $user->syncRoles([$role->name]);
            } else {
                $user->syncRoles([]);
            }
        }

        $this->syncBranches($request, $user, $validated['branches']);

        return response()->json([
            'status' => true,
            'message' => 'Staff has been updated successfully.',
        ]);
    }

    public function statusUpdate(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $user = User::findOrFail($validated['id']);

        $this->authorize('manage-staff-member', $user);

        if ($user->status !== 'offboarded') {
            return response()->json([
                'status' => false,
                'message' => 'Only an offboarded staff member can be reactivated here.',
            ]);
        }

        $user->update(['status' => 'active', 'lefted_at' => null, 'left_reason' => null]);

        return response()->json(['status' => true, 'message' => 'Staff member has been reactivated.']);
    }

    /**
     * Active direct reports of a staff member — for the offboarding
     * manager-reassignment check. Deliberately active-only, unlike the
     * generic hierarchy-children endpoint (which intentionally shows
     * everyone): an inactive/offboarded report isn't currently being managed
     * by anyone, so it doesn't need reassigning here.
     */
    public function activeDirectReports(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data = User::where('hierarchy_parent_id', $validated['id'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'designation']);

        return response()->json(['status' => true, 'message' => 'Active direct reports', 'data' => $data]);
    }

    /**
     * Staff eligible to be picked as a replacement manager when offboarding
     * someone — explicitly active only, excluding the staff member being
     * offboarded.
     */
    public function eligibleManagers(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'exclude_id' => ['nullable', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data = User::where('status', 'active')
            ->when($validated['exclude_id'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))
            ->orderBy('name')
            ->get(['id', 'name', 'designation']);

        return response()->json(['status' => true, 'message' => 'Eligible managers', 'data' => $data]);
    }

    public function updateAvatar(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'image' => ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
        ]);

        $staff = User::where('id', $validated['id'])->where('status', 'active')->first();

        if (! $staff) {
            return response()->json([
                'status' => false,
                'message' => 'No staff found with that id, or the staff member is not active.',
            ]);
        }

        $this->authorize('manage-staff-member', $staff);

        $path = $request->file('image')->store(
            app(TenantContext::class)->storagePath('avatars'),
            'public'
        );

        $staff->update(['avatar' => Storage::disk('public')->url($path)]);

        return response()->json(['status' => true, 'message' => 'Image updated successfully.']);
    }

    public function clearDeviceId(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $staff = User::findOrFail($validated['id']);

        $this->authorize('manage-staff-member', $staff);

        if (empty($staff->device_id)) {
            return response()->json(['status' => true, 'message' => 'Device ID is already empty.']);
        }

        $staff->update(['device_id' => null]);

        return response()->json(['status' => true, 'message' => 'Device ID has been cleared successfully.']);
    }

    /**
     * The currently authenticated staff member's own active branch
     * selections — SLMS360's global getMySelectedBranchesId() helper
     * (app/Helpers/Helper.php), scoped per-viewer rather than per-tenant:
     * two staff members in the same tenant can have different branches
     * "selected" and therefore see different staff lists.
     */
    private function mySelectedBranchIds(Request $request): array
    {
        return UserBranch::where('user_id', $request->user()->id)
            ->where('status', true)
            ->pluck('branch_id')
            ->all();
    }

    private function logPermissionChanges(Request $request, User $user, array $newPermissions): void
    {
        $oldPermissions = $user->permissions->pluck('name')->all();

        foreach (array_diff($newPermissions, $oldPermissions) as $permission) {
            UserUpdateLog::create([
                'user_id' => $user->id,
                'updated_by' => $request->user()->id,
                'field_name' => 'permission_added',
                'new_value' => $permission,
                'changed_at' => now(),
            ]);
        }

        foreach (array_diff($oldPermissions, $newPermissions) as $permission) {
            UserUpdateLog::create([
                'user_id' => $user->id,
                'updated_by' => $request->user()->id,
                'field_name' => 'permission_removed',
                'old_value' => $permission,
                'changed_at' => now(),
            ]);
        }
    }

    private function syncBranches(Request $request, User $user, array $branchIds): void
    {
        $oldBranchIds = $user->branches()->pluck('branch_id')->all();

        foreach (array_diff($branchIds, $oldBranchIds) as $branchId) {
            UserUpdateLog::create([
                'user_id' => $user->id,
                'updated_by' => $request->user()->id,
                'field_name' => 'branch_added',
                'new_value' => $branchId,
                'changed_at' => now(),
            ]);
        }

        foreach (array_diff($oldBranchIds, $branchIds) as $branchId) {
            UserUpdateLog::create([
                'user_id' => $user->id,
                'updated_by' => $request->user()->id,
                'field_name' => 'branch_removed',
                'old_value' => $branchId,
                'changed_at' => now(),
            ]);
        }

        $user->branches()->delete();

        foreach ($branchIds as $branchId) {
            UserBranch::create(['user_id' => $user->id, 'branch_id' => $branchId, 'status' => false]);
        }

        $user->branches()->first()?->update(['status' => true]);
    }
}
