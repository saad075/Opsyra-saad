<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitPlan;
use Carbon\CarbonImmutable;

/**
 * Turns repeating visit plans into dated visits, two weeks ahead, so
 * caregivers and coordinators see real visits they can change one by one
 * (swap a caregiver for a day, cancel, move). Idempotent: a plan makes at
 * most one visit a day, and a visit a coordinator cancelled or changed is
 * never recreated.
 */
class VisitScheduler
{
    public const DAYS_AHEAD = 14;

    public function __construct(private WorkCalendar $calendar) {}

    /** @return int visits created */
    public function generate(?CarbonImmutable $from = null, int $days = self::DAYS_AHEAD): int
    {
        $created = 0;

        VisitPlan::with(['staff', 'location'])
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', ($from ?? CarbonImmutable::today())->toDateString()))
            ->each(function (VisitPlan $plan) use ($from, $days, &$created) {
                $created += $this->generateFor($plan, $from, $days);
            });

        return $created;
    }

    public function generateFor(VisitPlan $plan, ?CarbonImmutable $from = null, int $days = self::DAYS_AHEAD): int
    {
        $staff = $plan->staff;

        if (! $staff || ! $plan->location?->is_active || in_array($staff->status, ['offboarded', 'inactive'], true)) {
            return 0;
        }

        $tz = $this->calendar->timezoneFor($staff);
        $start = ($from ?? CarbonImmutable::now($tz))->setTimezone($tz)->startOfDay();
        $existing = Visit::where('visit_plan_id', $plan->id)
            ->whereBetween('date', [$start->toDateString(), $start->addDays($days)->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $created = 0;

        for ($day = $start; $day->lte($start->addDays($days)); $day = $day->addDay()) {
            $date = $day->toDateString();

            if ($existing->has($date) || ! $plan->runsOn($date, $day->isoWeekday()) || ! $this->employedOn($staff, $date)) {
                continue;
            }

            [$plannedStart, $plannedEnd] = $this->times($day, $plan->start_time, $plan->end_time);

            Visit::create([
                'visit_location_id' => $plan->visit_location_id,
                'user_id' => $plan->user_id,
                'visit_plan_id' => $plan->id,
                'date' => $date,
                'planned_start' => $plannedStart,
                'planned_end' => $plannedEnd,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * After a plan changes: its future visits that haven't started are
     * replaced; anything already checked into, or changed by hand, stays.
     */
    public function refresh(VisitPlan $plan): int
    {
        $tz = $this->calendar->timezoneFor($plan->staff);
        $now = CarbonImmutable::now($tz);

        Visit::where('visit_plan_id', $plan->id)
            ->where('status', 'planned')
            ->whereNull('updated_by')
            ->where('planned_start', '>', $now->utc()) // query bindings aren't converted: compare in UTC
            ->delete();

        return $this->generateFor($plan->fresh(['staff', 'location']));
    }

    /**
     * Local start and end for a day; an end before the start is the next
     * morning (an overnight stay).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function times(CarbonImmutable $day, string $start, string $end): array
    {
        $from = $day->setTimeFromTimeString($start);
        $to = $day->setTimeFromTimeString($end);

        return [$from, $to->lte($from) ? $to->addDay() : $to];
    }

    private function employedOn(User $staff, string $date): bool
    {
        return (! $staff->joined_at || $staff->joined_at->toDateString() <= $date)
            && (! $staff->lefted_at || $staff->lefted_at->toDateString() >= $date);
    }
}
