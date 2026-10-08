<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadEmailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadEmail extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'lead_id',
        'to_email',
        'subject',
        'body',
        'sent_by',
    ];

    protected static function newFactory(): LeadEmailFactory
    {
        return LeadEmailFactory::new();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
