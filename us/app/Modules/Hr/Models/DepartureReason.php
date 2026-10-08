<?php

namespace App\Modules\Hr\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class DepartureReason extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'sequence',
        'is_active',
        'is_system',
        'requires_note',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'requires_note' => 'boolean',
        ];
    }

    /**
     * Starting defaults per country deployment — a sensible first list each
     * tenant can rename, reorder, deactivate or extend, not statutory reason
     * codes. System reasons can be deactivated but not deleted (Odoo's rule),
     * so reporting on historical exits never loses its categories.
     */
    public static function defaultsFor(string $country): array
    {
        $defaults = [
            ['name' => 'Resignation', 'sequence' => 10],
            ['name' => 'Dismissal', 'sequence' => 20],
            ['name' => 'End of contract', 'sequence' => 30],
            ['name' => 'Retirement', 'sequence' => 40],
            ['name' => 'Mutual agreement', 'sequence' => 50],
        ];

        $defaults = array_merge($defaults, match (strtolower($country)) {
            'gb', 'uk' => [['name' => 'Redundancy', 'sequence' => 25]],
            'us' => [['name' => 'Layoff', 'sequence' => 25]],
            default => [],
        });

        $defaults[] = ['name' => 'Other', 'sequence' => 90, 'requires_note' => true];

        return $defaults;
    }

    /**
     * Idempotent — safe to call for a tenant that already has some or all of
     * the defaults. Runs under the tenant's own context because RLS only
     * accepts inserts whose tenant_id matches the session's.
     */
    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () use ($tenant) {
            foreach (static::defaultsFor($tenant->country) as $reason) {
                static::firstOrCreate(
                    ['name' => $reason['name']],
                    [
                        'sequence' => $reason['sequence'],
                        'is_system' => true,
                        'requires_note' => $reason['requires_note'] ?? false,
                    ],
                );
            }
        });
    }
}
