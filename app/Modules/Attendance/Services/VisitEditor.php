<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * A coordinator changing visits: moving or reassigning one, cancelling,
 * reviewing a flagged check-in, or setting the times when the app's record
 * is wrong. A change to a finished day re-settles that caregiver's day.
 */
class VisitEditor
{
    public function __construct(private WorkCalendar $calendar, private VisitDaySettler $settler, private VisitScheduler $scheduler) {}

    /** Move, re-time or reassign a visit that hasn't started. */
    public function reschedule(Visit $visit, ?int $userId, ?string $date, ?string $start, ?string $end, User $actor): Visit
    {
        if ($visit->status !== 'planned') {
            throw new AttendanceException('Only a visit that hasn’t started can be moved or reassigned.');
        }

        return $this->change($visit, $actor, function (Visit $visit) use ($userId, $date, $start, $end) {
            $staff = User::findOrFail($userId ?? $visit->user_id);
            $tz = $this->calendar->timezoneFor($staff);
            $day = CarbonImmutable::parse($date ?? $visit->date->toDateString(), $tz)->startOfDay();
            $localStart = $visit->planned_start->setTimezone($this->calendar->timezoneFor($visit->staff));
            $localEnd = $visit->planned_end->setTimezone($this->calendar->timezoneFor($visit->staff));

            [$plannedStart, $plannedEnd] = $this->scheduler->times($day, $start ?? $localStart->format('H:i'), $end ?? $localEnd->format('H:i'));

            $visit->update(['user_id' => $staff->id, 'date' => $day->toDateString(), 'planned_start' => $plannedStart, 'planned_end' => $plannedEnd]);
        });
    }

    public function cancel(Visit $visit, ?string $reason, User $actor): Visit
    {
        if ($visit->status !== 'planned') {
            throw new AttendanceException('Only a visit that hasn’t started can be cancelled.');
        }

        return $this->change($visit, $actor, fn (Visit $v) => $v->update(['status' => 'cancelled', 'cancel_reason' => $reason]));
    }

    /**
     * Record the real times — e.g. a missed check-out, or the phone was
     * flat. Local times on the visit's date; an end before the start is
     * the next morning. Counts as reviewed.
     */
    public function setTimes(Visit $visit, string $in, string $out, ?string $note, User $actor): Visit
    {
        if ($visit->status === 'cancelled') {
            throw new AttendanceException('This visit was cancelled.');
        }

        return $this->change($visit, $actor, function (Visit $visit) use ($in, $out, $note, $actor) {
            [$inAt, $outAt] = $this->scheduler->times(CarbonImmutable::parse($visit->date->toDateString(), $this->calendar->timezoneFor($visit->staff)), $in, $out);

            if ($inAt->gt(CarbonImmutable::now())) {
                throw new AttendanceException('Visit times can’t be in the future.');
            }

            $visit->update([
                'status' => 'completed',
                'checked_in_at' => $inAt,
                'checked_out_at' => $outAt,
                'missed_checkout' => false,
                'worked_minutes' => (int) floor(($outAt->getTimestamp() - $inAt->getTimestamp()) / 60),
                'review_status' => 'accepted',
                'review_note' => $note,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /** Accept or reject a flagged visit. Rejected time doesn't count, or get paid. */
    public function review(Visit $visit, bool $accept, ?string $note, User $actor): Visit
    {
        if ($visit->review_status !== 'needs_review') {
            throw new AttendanceException('This visit isn’t waiting for review.');
        }

        if ($accept && $visit->missed_checkout) {
            throw new AttendanceException('Set the visit’s times first — the check-out time isn’t known.');
        }

        return $this->change($visit, $actor, fn (Visit $v) => $v->update([
            'review_status' => $accept ? 'accepted' : 'rejected',
            'review_note' => $note,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]));
    }

    private function change(Visit $visit, User $actor, \Closure $apply): Visit
    {
        return DB::transaction(function () use ($visit, $actor, $apply) {
            $visit = Visit::with('staff')->whereKey($visit->id)->lockForUpdate()->firstOrFail();
            $before = [$visit->user_id, $visit->date->toDateString()];

            $apply($visit);
            $visit->update(['updated_by' => $actor->id]);
            $visit->refresh();

            // Re-settle every day this touched (a reassignment touches two people).
            foreach (array_unique([implode('|', $before), $visit->user_id.'|'.$visit->date->toDateString()]) as $key) {
                [$userId, $date] = explode('|', $key);
                $this->resettle(User::findOrFail((int) $userId), $date);
            }

            return $visit->load(['location', 'staff:id,name']);
        });
    }

    private function resettle(User $user, string $date): void
    {
        $expected = $this->calendar->expectationFor($user, $date);

        if ($expected->kind === 'visits' && $this->calendar->isOver($user, $date, null, $expected)) {
            $this->settler->settle($user, $expected, isOver: true);
        }
    }
}
