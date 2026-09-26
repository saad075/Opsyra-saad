<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Support\Geo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Checking in and out of a client visit from the app. The phone's location
 * is compared with the client's address: within the location's radius is
 * verified; further away is recorded and flagged for a coordinator — never
 * refused (user decision 2026-09-26).
 */
class VisitClock
{
    /** A visit can be started this long before, or after, its planned time. */
    private const WINDOW_HOURS = 12;

    /**
     * @param  array{latitude?: ?float, longitude?: ?float, accuracy_m?: ?int}  $input
     */
    public function checkIn(User $user, Visit $visit, array $input, ?CarbonImmutable $at = null): Visit
    {
        $at ??= CarbonImmutable::now();

        return DB::transaction(function () use ($user, $visit, $input, $at) {
            User::whereKey($user->id)->lockForUpdate()->first();
            $visit = Visit::with('location')->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            $this->guardOwn($user, $visit);

            if ($visit->status !== 'planned') {
                throw new AttendanceException(match ($visit->status) {
                    'in_progress' => 'You’re already checked in to this visit.',
                    'cancelled' => 'This visit was cancelled.',
                    default => 'This visit is already finished.',
                });
            }

            if (abs($at->getTimestamp() - $visit->planned_start->getTimestamp()) > self::WINDOW_HOURS * 3600) {
                throw new AttendanceException('This visit isn’t due now — it’s planned for '.$visit->planned_start->format('j M, H:i').'.');
            }

            $open = Visit::with('location:id,name')->where('user_id', $user->id)->where('status', 'in_progress')->first();

            if ($open) {
                throw new AttendanceException("Check out of your visit to {$open->location->name} first.");
            }

            [$distance, $within] = $this->locate($visit, $input);

            $visit->update([
                'status' => 'in_progress',
                'checked_in_at' => $at,
                'check_in_latitude' => $input['latitude'] ?? null,
                'check_in_longitude' => $input['longitude'] ?? null,
                'check_in_accuracy_m' => $input['accuracy_m'] ?? null,
                'check_in_distance_m' => $distance,
                'check_in_within_range' => $within,
                'review_status' => $within === false ? 'needs_review' : $visit->review_status,
            ]);

            return $visit;
        });
    }

    /**
     * @param  array{latitude?: ?float, longitude?: ?float, accuracy_m?: ?int, note?: ?string}  $input
     */
    public function checkOut(User $user, Visit $visit, array $input, ?CarbonImmutable $at = null): Visit
    {
        $at ??= CarbonImmutable::now();

        return DB::transaction(function () use ($user, $visit, $input, $at) {
            $visit = Visit::with('location')->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            $this->guardOwn($user, $visit);

            if ($visit->status !== 'in_progress') {
                throw new AttendanceException('You’re not checked in to this visit.');
            }

            [$distance, $within] = $this->locate($visit, $input);

            $visit->update([
                'status' => 'completed',
                'checked_out_at' => $at,
                'check_out_latitude' => $input['latitude'] ?? null,
                'check_out_longitude' => $input['longitude'] ?? null,
                'check_out_accuracy_m' => $input['accuracy_m'] ?? null,
                'check_out_distance_m' => $distance,
                'check_out_within_range' => $within,
                'worked_minutes' => min(65_535, (int) floor(($at->getTimestamp() - $visit->checked_in_at->getTimestamp()) / 60)),
                'caregiver_note' => $input['note'] ?? $visit->caregiver_note,
                'review_status' => $within === false ? 'needs_review' : $visit->review_status,
            ]);

            return $visit;
        });
    }

    private function guardOwn(User $user, Visit $visit): void
    {
        if ($visit->user_id !== $user->id) {
            throw new AttendanceException('This visit isn’t assigned to you.');
        }
    }

    /** @return array{0: ?int, 1: ?bool} [metres from the client's address, within its radius] */
    private function locate(Visit $visit, array $input): array
    {
        if (! isset($input['latitude'], $input['longitude']) || ! $visit->location->hasPoint()) {
            // No phone location sent: can't be verified, so a coordinator looks.
            return [null, $visit->location->hasPoint() ? false : null];
        }

        $distance = Geo::distanceMetres($input['latitude'], $input['longitude'], $visit->location->latitude, $visit->location->longitude);

        return [$distance, $distance <= $visit->location->geofence_radius_m];
    }
}
