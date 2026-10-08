<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LeadFile extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'lead_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected static function newFactory(): LeadFileFactory
    {
        return LeadFileFactory::new();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected static function booted(): void
    {
        // The physical file belongs to this row, not the other way round —
        // deleting the row (directly, or via the lead/tenant cascade) must
        // not leave an orphaned file on disk.
        static::deleting(function (LeadFile $file) {
            Storage::disk('local')->delete($file->path);
        });
    }
}
