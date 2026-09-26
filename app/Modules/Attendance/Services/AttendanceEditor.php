<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\AttendanceCorrection;
use App\Modules\Attendance\Models\AttendanceDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Changing a day after the fact — by HR directly, or by approving a staff
 * member's correction request. Original punches are voided, never deleted,
 * so the record of what the app actually captured survives.
 */
class AttendanceEditor
{
    public function __construct(private WorkCalendar $calendar, private DayEvaluator $evaluator) {}

    /**
     * Replace the day's clocking with one in/out pair (local times). An
     * out-time earlier than the in-time is the next morning (night shift).
     */
    public function setTimes(User $user, string $date, string $in, ?string $out, User $actor, string $source = 'manual'): AttendanceDay
    {
        $this->guardDate($user, $date);

        return DB::transaction(function () use ($user, $date, $in, $out, $actor, $source) {
            $tz = $this->calendar->timezoneFor($user);
            $inAt = CarbonImmutable::parse("{$date} {$in}", $tz);
            $outAt = $out ? CarbonImmutable::parse("{$date} {$out}", $tz) : null;

            if ($outAt && $outAt->lte($inAt)) {
                $outAt = $outAt->addDay();
            }

            $day = $this->dayFor($user, $date);
            $day->punches()->whereNull('voided_at')->update(['voided_at' => now()]);

            foreach (array_filter(['in' => $inAt, 'out' => $outAt]) as $type => $at) {
                $day->punches()->create(['user_id' => $user->id, 'type' => $type, 'punched_at' => $at, 'source' => $source]);
            }

            $day->update(['is_manual' => false, 'source' => $source, 'updated_by' => $actor->id]);

            return $this->reevaluate($user, $day);
        });
    }

    /** Set the status by hand, e.g. on leave (paid or unpaid), remote, absent. */
    public function setStatus(User $user, string $date, string $status, ?bool $leavePaid, ?string $note, User $actor, string $source = 'manual'): AttendanceDay
    {
        // Leave can be recorded ahead (until the Leave module takes it over); nothing else can.
        if ($status !== 'on_leave') {
            $this->guardDate($user, $date);
        }

        return DB::transaction(function () use ($user, $date, $status, $leavePaid, $note, $actor, $source) {
            $day = $this->dayFor($user, $date);
            $day->update([
                'status' => $status,
                'leave_paid' => $status === 'on_leave' ? ($leavePaid ?? true) : null,
                'is_manual' => true,
                'source' => $source,
                'note' => $note ?? $day->note,
                'updated_by' => $actor->id,
            ]);

            return $this->reevaluate($user, $day);
        });
    }

    /** Hand the day back to the clock: its status comes from the punches again. */
    public function reset(User $user, string $date, User $actor): AttendanceDay
    {
        return DB::transaction(function () use ($user, $date, $actor) {
            $day = $this->dayFor($user, $date);
            $day->update(['is_manual' => false, 'leave_paid' => null, 'updated_by' => $actor->id]);

            return $this->reevaluate($user, $day);
        });
    }

    /**
     * Apply an approved correction. For "on leave" the approver decides
     * whether it's paid.
     */
    public function applyCorrection(AttendanceCorrection $correction, User $actor, ?bool $leavePaid = null): AttendanceDay
    {
        $user = User::findOrFail($correction->user_id);
        $date = $correction->date->toDateString();

        if ($correction->requested_status) {
            return $this->setStatus($user, $date, $correction->requested_status, $leavePaid, null, $actor, 'correction');
        }

        return $this->setTimes($user, $date, $correction->requested_in, $correction->requested_out, $actor, 'correction');
    }

    private function dayFor(User $user, string $date): AttendanceDay
    {
        return AttendanceDay::where('user_id', $user->id)->whereDate('date', $date)->lockForUpdate()->first()
            ?? AttendanceDay::create(['user_id' => $user->id, 'date' => $date, 'status' => 'absent', 'source' => 'manual']);
    }

    private function reevaluate(User $user, AttendanceDay $day): AttendanceDay
    {
        $date = $day->date->toDateString();
        $isOver = $this->calendar->isOver($user, $date);
        $day = $this->evaluator->evaluate($day->fresh(), $this->calendar->expectationFor($user, $date), $user, $isOver);

        if ($isOver && ! $day->closed_at) {
            $day->update(['closed_at' => now()]);
        }

        return $day->fresh('punches');
    }

    private function guardDate(User $user, string $date): void
    {
        if (CarbonImmutable::parse($date)->gt($this->calendar->today($user))) {
            throw new AttendanceException('Attendance can’t be recorded for a future date.');
        }
    }
}
