<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Hr\Models\StaffChecklist;
use App\Modules\Hr\Models\StaffChecklistTask;
use App\Modules\Hr\Models\StaffOffboardRequest;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The HR module's dashboard (Horilla-style: one per module). Everything the
 * page shows in one request; all queries run under the caller's tenant
 * (BelongsToTenant + RLS), so nothing crosses companies.
 */
class HrDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('manage-hr');

        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();

        $staff = User::query()->get(['id', 'name', 'designation', 'status', 'joined_at', 'lefted_at', 'avatar']);

        $active = $staff->where('status', 'active');

        return response()->json([
            'status' => true,
            'message' => 'HR dashboard',
            'data' => [
                'headcount' => [
                    'active' => $active->count(),
                    'starting_soon' => $staff->where('status', 'pending')->count(),
                    'awaiting_approval' => $staff->where('status', 'pending_approval')->count(),
                    'joined_this_month' => $staff->filter(fn ($u) => $u->joined_at && $u->status !== 'pending_approval'
                        && $u->joined_at->betweenIncluded($monthStart, $today))->count(),
                    'left_this_month' => $staff->filter(fn ($u) => $u->lefted_at && $u->lefted_at->betweenIncluded($monthStart, $today->endOfDay()))->count(),
                ],

                'trend' => $this->headcountTrend($staff, $today),

                'by_branch' => DB::table('user_branches')
                    ->join('branches', 'branches.id', '=', 'user_branches.branch_id')
                    ->join('users', 'users.id', '=', 'user_branches.user_id')
                    ->where('users.status', 'active')
                    ->groupBy('branches.name')
                    ->orderByDesc(DB::raw('count(distinct users.id)'))
                    ->get(['branches.name as label', DB::raw('count(distinct users.id) as value')]),

                'by_designation' => $active->groupBy(fn ($u) => $u->designation ?: 'Not set')
                    ->map(fn ($group, $label) => ['label' => $label, 'value' => $group->count()])
                    ->sortByDesc('value')->values()->take(8),

                'approvals' => [
                    'onboarding' => StaffOnboardingRequest::where('status', 'pending')->count(),
                    'offboarding' => StaffOffboardRequest::where('status', 'pending')->count(),
                    'rejected_awaiting_notice' => StaffOnboardingRequest::where('status', 'rejected')->whereNull('completed_at')->count(),
                ],

                'checklists' => [
                    'in_progress' => StaffChecklist::where('status', 'in_progress')->count(),
                    'overdue_tasks' => StaffChecklistTask::whereNotIn('status', StaffChecklistTask::CLOSED_STATUSES)
                        ->whereDate('due_date', '<', $today)
                        ->whereHas('checklist', fn ($q) => $q->where('status', 'in_progress'))
                        ->count(),
                    'completed_this_month' => StaffChecklist::where('status', 'completed')->where('completed_at', '>=', $monthStart)->count(),
                ],

                'upcoming_joiners' => $staff->filter(fn ($u) => $u->status === 'pending' && $u->joined_at?->betweenIncluded($today, $today->addDays(30)))
                    ->sortBy('joined_at')->values()->take(8)
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'designation' => $u->designation, 'date' => $u->joined_at->toDateString()]),

                'upcoming_leavers' => $staff->filter(fn ($u) => in_array($u->status, ['active', 'pending'], true) && $u->lefted_at?->betweenIncluded($today, $today->addDays(30)))
                    ->sortBy('lefted_at')->values()->take(8)
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'designation' => $u->designation, 'date' => $u->lefted_at->toDateString()]),
            ],
        ]);
    }

    /**
     * Staff employed at the end of each of the last 6 months, worked out
     * from joining and leaving dates.
     */
    private function headcountTrend($staff, CarbonImmutable $today): array
    {
        return collect(range(5, 0))->map(function (int $monthsAgo) use ($staff, $today) {
            $monthEnd = $today->subMonthsNoOverflow($monthsAgo)->endOfMonth();
            $asOf = $monthsAgo === 0 ? $today : $monthEnd;

            return [
                'label' => $monthEnd->format('M'),
                'value' => $staff->filter(fn ($u) => $u->status !== 'pending_approval'
                    && $u->joined_at && $u->joined_at->lte($asOf)
                    && (! $u->lefted_at || $u->lefted_at->gte($asOf)))->count(),
            ];
        })->all();
    }
}
