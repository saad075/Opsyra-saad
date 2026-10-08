<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Lead extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }

    protected $fillable = [
        'title',
        'description',
        'lead_value',
        'status',
        'lost_reason',
        'closed_at',
        'expected_close_date',
        'contact_name',
        'contact_email',
        'contact_phone',
        'lead_source_id',
        'lead_type_id',
        'lead_pipeline_id',
        'lead_pipeline_stage_id',
        'owner_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'lead_value' => 'decimal:2',
            'status' => 'boolean',
            'closed_at' => 'datetime',
            'expected_close_date' => 'date',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function type()
    {
        return $this->belongsTo(LeadType::class, 'lead_type_id');
    }

    public function pipeline()
    {
        return $this->belongsTo(LeadPipeline::class, 'lead_pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(LeadPipelineStage::class, 'lead_pipeline_stage_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function stageChanges()
    {
        return $this->hasMany(LeadStageChange::class)->latest('id');
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class)->latest('id');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->latest('id');
    }

    public function files()
    {
        return $this->hasMany(LeadFile::class)->latest('id');
    }

    public function emails()
    {
        return $this->hasMany(LeadEmail::class)->latest('id');
    }

    /**
     * Moves the lead onto $stage, keeping lead_pipeline_id in sync (a stage
     * belongs to exactly one pipeline) and applying won/lost side effects
     * by the stage's code — never its display name, which a tenant can
     * rename freely.
     *
     * Reopening: moving off a won/lost stage onto anything else clears
     * status/closed_at/lost_reason, same as Krayin — a lead doesn't stay
     * "won" once dragged back into an active stage.
     *
     * Logs a LeadStageChange only when the stage actually changes — the
     * initial stage a lead is created on isn't logged (nothing to change
     * from; see create_lead_stage_changes_table).
     */
    public function moveToStage(LeadPipelineStage $stage, ?string $lostReason = null, ?int $changedBy = null): void
    {
        $fromStageId = $this->lead_pipeline_stage_id;

        $this->lead_pipeline_id = $stage->lead_pipeline_id;
        $this->lead_pipeline_stage_id = $stage->id;

        if ($stage->isWon()) {
            $this->status = true;
            $this->closed_at = Carbon::now();
            $this->lost_reason = null;
        } elseif ($stage->isLost()) {
            $this->status = false;
            $this->closed_at = Carbon::now();
            $this->lost_reason = $lostReason;
        } else {
            $this->status = null;
            $this->closed_at = null;
            $this->lost_reason = null;
        }

        $this->save();

        if ($fromStageId !== $stage->id) {
            $this->stageChanges()->create([
                'from_lead_pipeline_stage_id' => $fromStageId,
                'to_lead_pipeline_stage_id' => $stage->id,
                'changed_by' => $changedBy,
            ]);
        }
    }

    /**
     * Stale if it's sat in its current stage longer than the pipeline's
     * rotten_days — approximated from updated_at (this phase has no
     * activity log to measure real last-activity from; see
     * docs/research/crm-leads.md). Never rotten once closed.
     */
    public function isRotten(): bool
    {
        if ($this->status !== null || ! $this->pipeline || ! $this->updated_at) {
            return false;
        }

        return $this->updated_at->lt(Carbon::now()->subDays($this->pipeline->rotten_days));
    }
}
