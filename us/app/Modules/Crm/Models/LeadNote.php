<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadNote extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'lead_id',
        'body',
        'created_by',
    ];

    protected static function newFactory(): LeadNoteFactory
    {
        return LeadNoteFactory::new();
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
