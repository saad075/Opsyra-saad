<?php

namespace App\Modules\Attendance\Support;

use App\Modules\Attendance\Models\Visit;

/** The one shape a visit takes in API responses (app and coordinator screens). */
final class VisitPresenter
{
    /** @return array<string, mixed> */
    public static function visit(Visit $visit, string $timezone): array
    {
        $local = fn ($at) => $at?->copy()->setTimezone($timezone)->toIso8601String();
        $location = $visit->location;

        return [
            'id' => $visit->id,
            'date' => $visit->date->toDateString(),
            'status' => $visit->status,
            'planned_start' => $local($visit->planned_start),
            'planned_end' => $local($visit->planned_end),
            'planned_minutes' => $visit->plannedMinutes(),
            'location' => $location ? [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'city' => $location->city,
                'postcode' => $location->postcode,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'geofence_radius_m' => $location->geofence_radius_m,
                'access_notes' => $location->access_notes,
            ] : null,
            'staff' => $visit->relationLoaded('staff') && $visit->staff ? ['id' => $visit->staff->id, 'name' => $visit->staff->name] : null,
            'from_plan' => $visit->visit_plan_id !== null,
            'checked_in_at' => $local($visit->checked_in_at),
            'check_in_distance_m' => $visit->check_in_distance_m,
            'check_in_within_range' => $visit->check_in_within_range,
            'checked_out_at' => $local($visit->checked_out_at),
            'check_out_distance_m' => $visit->check_out_distance_m,
            'check_out_within_range' => $visit->check_out_within_range,
            'missed_checkout' => $visit->missed_checkout,
            'worked_minutes' => $visit->worked_minutes,
            'countable_minutes' => $visit->countableMinutes(),
            'review_status' => $visit->review_status,
            'review_note' => $visit->review_note,
            'caregiver_note' => $visit->caregiver_note,
            'cancel_reason' => $visit->cancel_reason,
        ];
    }
}
