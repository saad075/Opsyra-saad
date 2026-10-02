<?php

namespace App\Modules\Crm\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Database\Factories\LeadTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadType extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): LeadTypeFactory
    {
        return LeadTypeFactory::new();
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

    public static function defaults(): array
    {
        return [
            ['name' => 'New Business', 'sequence' => 10],
            ['name' => 'Existing Business', 'sequence' => 20],
        ];
    }

    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () {
            foreach (static::defaults() as $type) {
                static::firstOrCreate(
                    ['name' => $type['name']],
                    ['sequence' => $type['sequence'], 'is_system' => true],
                );
            }
        });
    }
}
