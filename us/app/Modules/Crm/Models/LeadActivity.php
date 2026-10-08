<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPES = ['call', 'meeting', 'lunch', 'deadline', 'other'];

    protected $fillable = [
        'lead_id',
        'type',
        'title',
        'comment',
        'scheduled_at',
        'is_done',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'is_done' => 'boolean',
        ];
    }

    protected static function newFactory(): LeadActivityFactory
    {
        return LeadActivityFactory::new();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
