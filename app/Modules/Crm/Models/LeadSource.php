<?php

namespace App\Modules\Crm\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Database\Factories\LeadSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSource extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): LeadSourceFactory
    {
        return LeadSourceFactory::new();
    }

    protected $fillable = [
        'name',
        'sequence',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /** A sensible first list every tenant can rename, reorder, deactivate or extend. */
    public static function defaults(): array
    {
        return [
            ['name' => 'Website', 'sequence' => 10],
            ['name' => 'Referral', 'sequence' => 20],
            ['name' => 'Cold Call', 'sequence' => 30],
            ['name' => 'Social Media', 'sequence' => 40],
            ['name' => 'Email Campaign', 'sequence' => 50],
            ['name' => 'Other', 'sequence' => 90],
        ];
    }

    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () {
            foreach (static::defaults() as $source) {
                static::firstOrCreate(
                    ['name' => $source['name']],
                    ['sequence' => $source['sequence'], 'is_system' => true],
                );
            }
        });
    }
}
