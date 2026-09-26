<?php

namespace App\Modules\Rbac\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Who approves: level 1 (salaries) and level 2 (final say on hires, leavers
 * and salaries). New build for the Setup wizard's Approvers step. A person
 * has one job role (HR Manager, Payroll Officer…), so approval is granted
 * as a direct permission on top of it rather than by swapping their role —
 * the Horilla/Frappe pattern of approvers picked per person.
 */
class ApproverController extends Controller
{
    private const L1 = 'HRMS-Approval-L1';

    private const L2 = 'HRMS-Approval-L2';

    public function index()
    {
        $this->authorize('update-staff-permissions');

        $data = User::with(['roles.permissions:id,name', 'permissions:id,name'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation,
                'roles' => $user->roles->pluck('name')->values(),
                'direct_level' => $this->level($user->permissions->pluck('name')),
                'role_level' => $this->level($user->roles->flatMap(fn ($role) => $role->permissions->pluck('name'))),
            ])
            ->map(fn (array $row) => $row + ['level' => max($row['direct_level'], $row['role_level'])]);

        return response()->json(['status' => true, 'message' => 'Approvers', 'data' => $data]);
    }

    /**
     * Sets a person's direct approval level (0 = none). A level that comes
     * from their role stays — change the role for that.
     */
    public function set(Request $request)
    {
        $this->authorize('update-staff-permissions');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)->where('status', 'active')],
            'level' => ['required', 'integer', 'in:0,1,2'],
        ]);

        $user = User::with(['roles.permissions:id,name'])->findOrFail($validated['user_id']);
        $roleLevel = $this->level($user->roles->flatMap(fn ($role) => $role->permissions->pluck('name')));
        $newLevel = max($validated['level'], $roleLevel);

        // Hires and leavers wait for a level 2 approver — never leave none.
        $otherL2 = User::permission(self::L2)->where('status', 'active')->whereKeyNot($user->id)->count();

        if ($newLevel < 2 && $otherL2 === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Someone must be able to give final (level 2) approval. Make someone else level 2 first.',
            ], 409);
        }

        DB::transaction(function () use ($user, $validated) {
            $user->revokePermissionTo(array_values(array_filter([self::L1, self::L2], fn ($p) => $user->hasDirectPermission($p))));

            if ($validated['level'] > 0) {
                $user->givePermissionTo($validated['level'] === 2 ? self::L2 : self::L1);
            }
        });

        $message = 'Approval level saved.';

        if ($roleLevel > $validated['level']) {
            $message .= " {$user->name} is still level {$roleLevel} through their role.";
        }

        return response()->json(['status' => true, 'message' => $message, 'data' => ['level' => $newLevel]]);
    }

    private function level(iterable $permissions): int
    {
        $names = collect($permissions);

        return $names->contains(self::L2) ? 2 : ($names->contains(self::L1) ? 1 : 0);
    }
}
