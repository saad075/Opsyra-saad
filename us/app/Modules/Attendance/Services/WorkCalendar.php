<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Models\Holiday;
use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Attendance\Models\WorkScheduleAssignment;
use App\Modules\Attendance\Support\Expectation;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\UserBranch;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Tenancy\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Answers "what should this person be doing on this date?" from their
 * attendance mode, employment dates, holidays and work schedule. One place
 * for the rules SLMS360 repeated in its check-in endpoint, nightly command
 * and leave approval.
 *
 * Lookups are remembered for the life of this object (one request, or one
 * command run): settling a month for everyone would otherwise re-read the
 * same holidays and schedules thousands of times. Company-wide data is
 * keyed by company, so a command looping over companies stays correct.
 */
class WorkCalendar
{
    /** @var array<int, string> */
    private array $timezones = [];

    /** @var array<int, Collection<int, int>> */
    private array $branchIds = [];

    /** @var array<int, Collection<int, WorkScheduleAssignment>> */
    private array $assignments = [];

    /** @var array<int, Collection<string, Collection<int, Holiday>>> */
    private array $holidays = [];

    /** @var array<int, ?WorkSchedule> */
    private array $defaultSchedule = [];

    public function __construct(private TenantContext $tenant) {}

    /**
     * A person's working timezone: their current branch's, else the
     * company's, else UTC. Dates ("today", "a day") are always in this zone.
     */
    public function timezoneFor(User $user): string
    {
        return $this->timezones[$user->id] ??= $this->primaryBranch($user)?->timezone
            ?? TenantSetting::get('timezone')
            ?? 'UTC';
    }

    public function primaryBranch(User $user): ?Branch
    {
        $link = UserBranch::with('branch')->where('user_id', $user->id)->orderByDesc('status')->orderBy('id')->first();

        return $link?->branch;
    }

    public function today(User $user): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezoneFor($user))->startOfDay();
    }

    public function expectationFor(User $user, string $date): Expectation
    {
        $tz = $this->timezoneFor($user);
        $day = CarbonImmutable::parse($date, $tz)->startOfDay();
        $notTracked = new Expectation('not_tracked', $day->toDateString(), $tz);

        if ($user->attendance_mode === 'not_tracked') {
            return $notTracked;
        }

        if ($user->joined_at && $day->lt(CarbonImmutable::parse($user->joined_at->toDateString(), $tz))) {
            return $notTracked;
        }

        if ($user->lefted_at && $day->gt(CarbonImmutable::parse($user->lefted_at->toDateString(), $tz))) {
            return $notTracked;
        }

        $holiday = $this->holidayFor($user, $day->toDateString());

        // Care visits don't stop for public holidays: a visit-based worker's
        // day is whatever visits are planned, holiday or not.
        if ($user->attendance_mode === 'visits') {
            return new Expectation('visits', $day->toDateString(), $tz, holidayName: $holiday?->name);
        }

        if ($holiday) {
            return new Expectation('holiday', $day->toDateString(), $tz, holidayName: $holiday->name);
        }

        $schedule = $this->scheduleFor($user, $day->toDateString());

        if (! $schedule) {
            return $notTracked;
        }

        $scheduleDay = $schedule->day($day->isoWeekday());

        if (! $scheduleDay || ! $scheduleDay->is_working || ! $scheduleDay->start_time || ! $scheduleDay->end_time) {
            return new Expectation('day_off', $day->toDateString(), $tz, schedule: $schedule);
        }

        $start = $day->setTimeFromTimeString($scheduleDay->start_time);
        $end = $day->setTimeFromTimeString($scheduleDay->end_time);

        if ($scheduleDay->isOvernight()) {
            $end = $end->addDay();
        }

        return new Expectation('work', $day->toDateString(), $tz, $schedule, $start, $end, $scheduleDay->requiredMinutes());
    }

    /**
     * Whether a day is finished and can be settled: its date has passed in
     * the person's timezone, and — for a shift running past midnight — its
     * end was a few hours ago (time to clock out late before it's called
     * a missed checkout).
     */
    public function isOver(User $user, string $date, ?CarbonImmutable $now = null, ?Expectation $expected = null): bool
    {
        $expected ??= $this->expectationFor($user, $date);
        $now = ($now ?? CarbonImmutable::now())->setTimezone($expected->timezone);

        if ($now->lt(CarbonImmutable::parse($date, $expected->timezone)->addDay()->startOfDay())) {
            return false;
        }

        return ! $expected->end || $now->gte($expected->end->addHours(4));
    }

    /**
     * The first day attendance is kept for this company: the close-out never
     * invents absences from before the company started using it.
     */
    public function trackingStartsOn(): ?string
    {
        return TenantSetting::get('attendance_started_on');
    }

    /**
     * The person's assigned schedule on a date, else the company's default —
     * so a small company with one working week needs no assigning at all.
     */
    public function scheduleFor(User $user, string $date): ?WorkSchedule
    {
        $this->assignments[$user->id] ??= WorkScheduleAssignment::with('schedule.days')
            ->where('user_id', $user->id)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();

        $assignment = $this->assignments[$user->id]->first(
            fn (WorkScheduleAssignment $a) => $a->starts_on->toDateString() <= $date && (! $a->ends_on || $a->ends_on->toDateString() >= $date),
        );

        if ($assignment) {
            return $assignment->schedule;
        }

        $tenant = $this->tenant->id();

        if (! array_key_exists($tenant, $this->defaultSchedule)) {
            $this->defaultSchedule[$tenant] = WorkSchedule::with('days')->where('is_default', true)->where('is_active', true)->first();
        }

        return $this->defaultSchedule[$tenant];
    }

    public function holidayFor(User $user, string $date): ?Holiday
    {
        $this->holidays[$this->tenant->id()] ??= Holiday::orderByRaw('branch_id is null')->get()->groupBy(fn (Holiday $h) => $h->date->toDateString());
        $this->branchIds[$user->id] ??= UserBranch::where('user_id', $user->id)->pluck('branch_id');

        return $this->holidays[$this->tenant->id()]->get($date)
            ?->first(fn (Holiday $h) => $h->branch_id === null || $this->branchIds[$user->id]->contains($h->branch_id));
    }
}
