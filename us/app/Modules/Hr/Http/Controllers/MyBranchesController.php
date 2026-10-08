<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\UserBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * The branch switcher. Staff lists are scoped to the branches the viewer
 * has *selected* (SLMS360's getMySelectedBranchesId(), ported with the HR
 * core); this lets the viewer choose which of their own branches that is.
 * Nobody can select a branch they don't belong to.
 */
class MyBranchesController extends Controller
{
    public function index(Request $request)
    {
        $data = UserBranch::with('branch:id,name,status')
            ->where('user_id', $request->user()->id)
            ->get()
            ->filter(fn (UserBranch $ub) => $ub->branch?->status)
            ->map(fn (UserBranch $ub) => ['id' => $ub->branch_id, 'name' => $ub->branch->name, 'selected' => (bool) $ub->status])
            ->sortBy('name')
            ->values();

        return response()->json(['status' => true, 'message' => 'My branches', 'data' => $data]);
    }

    public function select(Request $request)
    {
        $mine = UserBranch::where('user_id', $request->user()->id)->pluck('branch_id')->all();

        $validated = $request->validate([
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', ValidationRule::in($mine)],
        ], ['branch_ids.*.in' => 'You can only view branches you belong to.']);

        UserBranch::where('user_id', $request->user()->id)->update(['status' => false]);
        UserBranch::where('user_id', $request->user()->id)->whereIn('branch_id', $validated['branch_ids'])->update(['status' => true]);

        return $this->index($request);
    }
}
