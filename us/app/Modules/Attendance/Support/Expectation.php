<?php

namespace App\Modules\Attendance\Support;

use App\Modules\Attendance\Models\WorkSchedule;
use Carbon\CarbonImmutable;

/**
 * What the working calendar expects of one person on one day.
 *
 * kind: work (scheduled hours), day_off, holiday, visits (a visit-based
 * worker — their day comes from their visits), not_tracked (not employed
 * that day, attendance switched off, or no schedule at all).
 */
final class Expectation
{
    public function __construct(
        public readonly string $kind,
        public readonly string $date,
        public readonly string $timezone,
        public readonly ?WorkSchedule $schedule = null,
        public readonly ?CarbonImmutable $start = null,
        public readonly ?CarbonImmutable $end = null,
        public readonly int $requiredMinutes = 0,
        public readonly ?string $holidayName = null,
    ) {}

    public function isWorkDay(): bool
    {
        return $this->kind === 'work';
    }

    public function isTracked(): bool
    {
        return $this->kind !== 'not_tracked';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'date' => $this->date,
            'timezone' => $this->timezone,
            'schedule' => $this->schedule?->only(['id', 'name']),
            'start' => $this->start?->toIso8601String(),
            'end' => $this->end?->toIso8601String(),
            'required_minutes' => $this->requiredMinutes,
            'holiday' => $this->holidayName,
        ];
    }
}
