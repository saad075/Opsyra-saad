<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A weekly working pattern (SLMS360's "shift"): which weekdays are worked,
 * from when to when, with grace periods for lateness and leaving early.
 */
class WorkSchedule extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'description', 'grace_minutes', 'early_leave_grace_minutes', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'grace_minutes' => 'integer',
            'early_leave_grace_minutes' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function days()
    {
        return $this->hasMany(WorkScheduleDay::class)->orderBy('weekday');
    }

    public function day(int $isoWeekday): ?WorkScheduleDay
    {
        return $this->days->firstWhere('weekday', $isoWeekday);
    }

    /**
     * The working week each country deployment starts with — Oman works
     * Sunday to Thursday; elsewhere Monday to Friday. Times are a starting
     * point for the company to edit in Setup.
     *
     * @return array{name: string, working: list<int>, start: string, end: string, break: int}
     */
    public static function defaultFor(string $country): array
    {
        return match (strtolower($country)) {
            'om' => ['name' => 'Standard week (Sun–Thu)', 'working' => [7, 1, 2, 3, 4], 'start' => '08:00', 'end' => '16:00', 'break' => 60],
            default => ['name' => 'Standard week (Mon–Fri)', 'working' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:00', 'break' => 60],
        };
    }

    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () use ($tenant) {
            if (! TenantSetting::get('attendance_started_on')) {
                TenantSetting::set('attendance_started_on', today()->toDateString());
            }

            if (static::exists()) {
                return;
            }

            $default = static::defaultFor($tenant->country);

            DB::transaction(function () use ($default) {
                $schedule = static::create(['name' => $default['name'], 'is_default' => true]);

                foreach (range(1, 7) as $weekday) {
                    $working = in_array($weekday, $default['working'], true);
                    $schedule->days()->create([
                        'weekday' => $weekday,
                        'is_working' => $working,
                        'start_time' => $working ? $default['start'] : null,
                        'end_time' => $working ? $default['end'] : null,
                        'break_minutes' => $working ? $default['break'] : 0,
                    ]);
                }
            });
        });
    }
}
