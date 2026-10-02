<?php

namespace App\Modules\Crm\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Database\Factories\LeadPipelineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadPipeline extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): LeadPipelineFactory
    {
        return LeadPipelineFactory::new();
    }

    protected $fillable = [
        'name',
        'is_default',
        'rotten_days',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stages()
    {
        return $this->hasMany(LeadPipelineStage::class)->orderBy('sort_order');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * code is how LeadController recognises the terminal stages, never the
     * display name — see create_lead_pipeline_stages_table.
     */
    public static function defaultStages(): array
    {
        return [
            ['code' => 'new', 'name' => 'New', 'probability' => 10, 'sort_order' => 10],
            ['code' => 'contacted', 'name' => 'Contacted', 'probability' => 25, 'sort_order' => 20],
            ['code' => 'qualified', 'name' => 'Qualified', 'probability' => 40, 'sort_order' => 30],
            ['code' => 'proposal', 'name' => 'Proposal', 'probability' => 60, 'sort_order' => 40],
            ['code' => 'negotiation', 'name' => 'Negotiation', 'probability' => 80, 'sort_order' => 50],
            ['code' => 'won', 'name' => 'Won', 'probability' => 100, 'sort_order' => 60],
            ['code' => 'lost', 'name' => 'Lost', 'probability' => 0, 'sort_order' => 70],
        ];
    }

    /** One default pipeline with the stock stage set — every tenant's starting point, freely editable after. */
    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () {
            if (static::where('is_default', true)->exists()) {
                return;
            }

            $pipeline = static::create(['name' => 'Sales Pipeline', 'is_default' => true]);

            foreach (static::defaultStages() as $stage) {
                $pipeline->stages()->create($stage);
            }
        });
    }
}
