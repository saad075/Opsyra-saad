<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Adapted from SLMS360's app/Http/Controllers/Client/HR/TeamController.php.
 * A "team" is still not a stored entity — any staff member with team_name
 * set is a team head, and membership is the hierarchy_parent_id tree under
 * them (see User::getAllHierarchyChildrenIds). Tenant scoping now comes from
 * BelongsToTenant + RLS on every User query, including the recursive
 * traversal, instead of SLMS360's explicit ->where('parent_id', ...) calls.
 */
class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teamHeads = User::where('status', 'active')
            ->whereNotNull('team_name')
            ->where('team_name', '!=', '')
            ->orderBy('team_name')
            ->get(['id', 'name', 'designation', 'team_name']);

        $data = $teamHeads->map(fn (User $head) => [
            'team_name' => $head->team_name,
            'head_id' => $head->id,
            'head_name' => $head->name,
            'head_designation' => $head->designation,
            'member_count' => count($head->getAllHierarchyChildrenIds(25)),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Teams',
            'data' => $data,
        ]);
    }

    public function members(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $head = User::findOrFail($validated['id']);

        $memberIds = $head->getAllHierarchyChildrenIds(25);
        $members = User::whereIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'status', 'hierarchy_parent_id']);

        return response()->json([
            'status' => true,
            'message' => 'Team members',
            'data' => [
                'team_name' => $head->team_name,
                'head' => ['id' => $head->id, 'name' => $head->name, 'designation' => $head->designation],
                'members' => $members,
            ],
        ]);
    }
}
