<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\AttendancePunch;
use App\Modules\Attendance\Support\Geo;
use App\Modules\Hr\Models\Branch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Clocking in and out from the app (SLMS360's attendance/mark). Location is
 * recorded and checked against the branch, but a far-away punch is only
 * flagged for review, never refused (user decision 2026-09-26: phone GPS
 * indoors is often 50–200 m out).
 */
class AttendanceClock
{
    /** A clock-out this long after the last clock-in still closes that shift. */
    private const MAX_SHIFT_HOURS = 20;

    public function __construct(private WorkCalendar $calendar, private DayEvaluator $evaluator) {}

    /**
     * @param  array{type?: ?string, branch_id?: ?int, latitude?: ?float, longitude?: ?float, accuracy_m?: ?int, device?: ?string, source?: string}  $input
     */
    public function clock(User $user, array $input, ?CarbonImmutable $at = null): AttendancePunch
    {
        if ($user->attendance_mode === 'visits') {
            throw new AttendanceException('You check in at each visit instead — open today’s visits.');
        }

        if ($user->attendance_mode === 'not_tracked') {
            throw new AttendanceException('Attendance isn’t tracked for you. Ask HR if that’s wrong.');
        }

        $at ??= CarbonImmutable::now();

        return DB::transaction(function () use ($user, $input, $at) {
            // One clock at a time per person: a double tap can't make two "in"s.
            User::whereKey($user->id)->lockForUpdate()->first();

            $last = AttendancePunch::where('user_id', $user->id)->whereNull('voided_at')->latest('punched_at')->latest('id')->first();
            $stillIn = $last && $last->type === 'in' && $last->punched_at->diffInHours($at) < self::MAX_SHIFT_HOURS;
            $type = $stillIn ? 'out' : 'in';

            if (! empty($input['type']) && $input['type'] !== $type) {
                throw new AttendanceException($type === 'out' ? 'You’re already clocked in.' : 'You’re not clocked in.');
            }

            // Clocking out belongs to the day it was clocked in on (night shifts).
            $date = $stillIn ? $last->day->date->toDateString() : $this->dayFor($user, $at);

            $day = AttendanceDay::firstOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                ['status' => 'open', 'source' => $input['source'] ?? 'app', 'branch_id' => $input['branch_id'] ?? null],
            );

            $branch = ! empty($input['branch_id']) ? Branch::find($input['branch_id']) : null;
            [$distance, $withinRange] = $this->locate($branch, $input);

            $punch = $day->punches()->create([
                'user_id' => $user->id,
                'type' => $type,
                'punched_at' => $at,
                'branch_id' => $branch?->id,
                'latitude' => $input['latitude'] ?? null,
                'longitude' => $input['longitude'] ?? null,
                'accuracy_m' => $input['accuracy_m'] ?? null,
                'distance_m' => $distance,
                'within_range' => $withinRange,
                'source' => $input['source'] ?? 'app',
                'device' => $input['device'] ?? null,
            ]);

            $this->evaluator->evaluate($day, $this->calendar->expectationFor($user, $date), $user, isOver: false);

            return $punch;
        });
    }

    /**
     * The working day a clock-in belongs to: normally today (local), but an
     * early-morning clock-in during last night's overnight shift belongs to
     * yesterday.
     */
    public function dayFor(User $user, CarbonImmutable $at): string
    {
        $local = $at->setTimezone($this->calendar->timezoneFor($user));
        $yesterday = $local->subDay()->toDateString();
        $expected = $this->calendar->expectationFor($user, $yesterday);

        if ($expected->isWorkDay() && $expected->end && $local->lt($expected->end) && $expected->end->toDateString() === $local->toDateString()) {
            return $yesterday;
        }

        return $local->toDateString();
    }

    /** @return array{0: ?int, 1: ?bool} [metres from the branch, within its radius] */
    private function locate(?Branch $branch, array $input): array
    {
        if (! isset($input['latitude'], $input['longitude']) || ! $branch || $branch->latitude === null || $branch->longitude === null) {
            return [null, null];
        }

        $distance = Geo::distanceMetres($input['latitude'], $input['longitude'], $branch->latitude, $branch->longitude);

        return [$distance, $branch->geofence_radius_m ? $distance <= $branch->geofence_radius_m : null];
    }
}
