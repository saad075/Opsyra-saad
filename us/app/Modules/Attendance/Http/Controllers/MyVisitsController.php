<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Services\VisitClock;
use App\Modules\Attendance\Services\WorkCalendar;
use App\Modules\Attendance\Support\VisitPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * The caregiver app: my visits for a day, and checking in and out of each
 * one with the phone's location. Only ever the signed-in person's visits.
 */
class MyVisitsController extends Controller
{
    public function __construct(private WorkCalendar $calendar) {}

    public function day(Request $request)
    {
        $validated = $request->validate(['date' => ['nullable', 'date']]);
        $user = $request->user();
        $tz = $this->calendar->timezoneFor($user);
        $date = $validated['date'] ?? $this->calendar->today($user)->toDateString();

        $visits = Visit::with('location')
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('planned_start')
            ->get();

        $inProgress = $visits->firstWhere('status', 'in_progress')
            ?? Visit::with('location')->where('user_id', $user->id)->where('status', 'in_progress')->first();

        return response()->json([
            'status' => true,
            'message' => 'My visits',
            'data' => [
                'date' => $date,
                'timezone' => $tz,
                'in_progress' => $inProgress ? VisitPresenter::visit($inProgress, $tz) : null,
                'next' => ($next = $visits->firstWhere('status', 'planned')) ? $next->id : null,
                'visits' => $visits->map(fn (Visit $v) => VisitPresenter::visit($v, $tz))->values(),
            ],
        ]);
    }

    public function checkIn(Request $request, VisitClock $clock)
    {
        $visit = $this->findOwn($request);
        $validated = $this->validateLocation($request);

        $visit = $clock->checkIn($request->user(), $visit, $validated);

        return response()->json([
            'status' => true,
            'message' => $this->message('Checked in', $visit->check_in_within_range, $visit->check_in_distance_m),
            'data' => VisitPresenter::visit($visit, $this->calendar->timezoneFor($request->user())),
        ]);
    }

    public function checkOut(Request $request, VisitClock $clock)
    {
        $visit = $this->findOwn($request);
        $validated = $this->validateLocation($request) + $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $visit = $clock->checkOut($request->user(), $visit, $validated);

        return response()->json([
            'status' => true,
            'message' => $this->message('Checked out', $visit->check_out_within_range, $visit->check_out_distance_m),
            'data' => VisitPresenter::visit($visit, $this->calendar->timezoneFor($request->user())),
        ]);
    }

    /** A month of my visits, newest first, with totals. */
    public function month(Request $request)
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $user = $request->user();
        $tz = $this->calendar->timezoneFor($user);
        $start = CarbonImmutable::parse(($validated['month'] ?? $this->calendar->today($user)->format('Y-m')).'-01', $tz);

        $visits = Visit::with('location')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('planned_start')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'My visits this month',
            'data' => [
                'month' => $start->format('Y-m'),
                'summary' => [
                    'completed' => $visits->where('status', 'completed')->count(),
                    'missed' => $visits->where('status', 'missed')->count(),
                    'awaiting_review' => $visits->where('review_status', 'needs_review')->count(),
                    'countable_minutes' => (int) $visits->sum(fn (Visit $v) => $v->countableMinutes()),
                ],
                'visits' => $visits->map(fn (Visit $v) => VisitPresenter::visit($v, $tz))->values(),
            ],
        ]);
    }

    private function findOwn(Request $request): Visit
    {
        $validated = $request->validate([
            // Someone else's visit id is simply "not found".
            'visit_id' => ['required', 'integer', ValidationRule::exists('visits', 'id')->where('tenant_id', $request->user()->tenant_id)->where('user_id', $request->user()->id)],
        ]);

        return Visit::findOrFail($validated['visit_id']);
    }

    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    private function message(string $done, ?bool $within, ?int $distance): string
    {
        return match ($within) {
            false => $distance !== null
                ? "{$done}. You’re {$distance} m from the client’s address, so a coordinator will check this visit."
                : "{$done}. Your location couldn’t be read, so a coordinator will check this visit.",
            default => "{$done}.",
        };
    }
}
