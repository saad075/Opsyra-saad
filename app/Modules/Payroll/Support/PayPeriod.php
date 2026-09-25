<?php

namespace App\Modules\Payroll\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * A pay period is a start and end date. Monthly periods are calendar months
 * (SLMS360's staff:post-salary); biweekly periods are 14 days from whatever
 * start the company pays on (SLMS360's staff:post-salary-biweekly took an
 * explicit start/end instead).
 */
final class PayPeriod
{
    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function for(string $frequency, mixed $start): self
    {
        $start = CarbonImmutable::parse($start)->startOfDay();

        return match ($frequency) {
            'monthly' => $start->day === 1
                ? new self($start, $start->endOfMonth()->startOfDay())
                : throw new InvalidArgumentException('A monthly pay period must start on the 1st.'),
            'biweekly' => new self($start, $start->addDays(13)),
            default => throw new InvalidArgumentException("Unknown pay frequency: {$frequency}"),
        };
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    /**
     * Days of this period someone was employed, given their joining and
     * leaving dates (either may be null). Zero if they never overlap —
     * including a joining date after the leaving date, which is what a
     * rejected-before-starting candidate looks like.
     */
    public function employedDays(mixed $joinedAt, mixed $leftAt): int
    {
        $from = $joinedAt ? CarbonImmutable::parse($joinedAt)->startOfDay()->max($this->start) : $this->start;
        $to = $leftAt ? CarbonImmutable::parse($leftAt)->startOfDay()->min($this->end) : $this->end;

        return $from->gt($to) ? 0 : (int) $from->diffInDays($to) + 1;
    }

    public function contains(mixed $date): bool
    {
        return Carbon::parse($date)->between($this->start, $this->end);
    }
}
