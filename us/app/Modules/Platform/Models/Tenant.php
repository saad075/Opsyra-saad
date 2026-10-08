<?php

namespace App\Modules\Platform\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Platform model — deliberately does NOT use BelongsToTenant. This table
 * defines what a tenant is; it can't be scoped by the thing it defines.
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Laravel's factory auto-resolution assumes models live under App\Models;
     * this one doesn't (it's App\Modules\Platform\Models\Tenant), so the
     * factory needs to be named explicitly.
     */
    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    protected $fillable = [
        'client_code',
        'name',
        'country',
        'status',
        'database_connection',
        'trial_ends_at',
        'plan_code',
        'features',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'owner_user_id' => 'integer',
            'features' => 'array',
            'is_demo' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
