<?php

namespace App\Modules\Crm\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadPipelineStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadPipelineStage extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): LeadPipelineStageFactory
    {
        return LeadPipelineStageFactory::new();
    }

    /** Business logic reads this, never the display name — see the migration. */
    public const WON = 'won';

    public const LOST = 'lost';

    protected $fillable = [
        'lead_pipeline_id',
        'code',
        'name',
        'probability',
        'sort_order',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pipeline()
    {
        return $this->belongsTo(LeadPipeline::class, 'lead_pipeline_id');
    }

    public function isWon(): bool
    {
        return $this->code === self::WON;
    }

    public function isLost(): bool
    {
        return $this->code === self::LOST;
    }
}
