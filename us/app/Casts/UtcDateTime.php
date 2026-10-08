<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * For timestamptz columns that are set from a local time (e.g. a visit
 * planned for 08:00 in Chicago). Eloquent's own datetime cast writes the
 * clock time without its zone, so the database — in UTC — would read
 * 08:00 Chicago as 08:00 UTC. This writes the moment in UTC with an
 * explicit offset, and reads it back in UTC.
 *
 * Read back as immutable on purpose: Eloquent caches cast objects and
 * saves them again, so `$visit->planned_end->addHour()` on a mutable
 * Carbon silently moved the stored end by an hour (found in testing).
 *
 * @implements CastsAttributes<CarbonImmutable|null, mixed>
 */
class UtcDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->utc();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $moment = $value instanceof CarbonInterface || $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value); // a string without an offset is taken as UTC (the app timezone)

        return $moment->utc()->format('Y-m-d H:i:sP');
    }
}
