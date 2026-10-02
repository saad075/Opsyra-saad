<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadStageChangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStageChange extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'from_lead_pipeline_stage_id',
        'to_lead_pipeline_stage_id',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function newFactory(): LeadStageChangeFactory
    {
        return LeadStageChangeFactory::new();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function fromStage()
    {
        return $this->belongsTo(LeadPipelineStage::class, 'from_lead_pipeline_stage_id');
    }

    public function toStage()
    {
        return $this->belongsTo(LeadPipelineStage::class, 'to_lead_pipeline_stage_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
