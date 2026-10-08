<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadActivity;
use App\Modules\Crm\Models\LeadEmail;
use App\Modules\Crm\Models\LeadFile;
use App\Modules\Crm\Models\LeadNote;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadPipelineStage;
use App\Modules\Crm\Models\LeadSource;
use App\Modules\Crm\Models\LeadStageChange;
use App\Modules\Crm\Models\LeadType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

/**
 * Per docs/decisions.md: every tenant-scoped module adds its own
 * assertModelIsTenantIsolated() case rather than inventing a new check.
 * Covers all ten Leads-module tables — both RLS layers each.
 */
class CrmLeadsTenantIsolationTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_lead_sources_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadSource::class,
            fn () => LeadSource::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_types_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadType::class,
            fn () => LeadType::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_pipelines_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadPipeline::class,
            fn () => LeadPipeline::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_pipeline_stages_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadPipelineStage::class,
            fn () => LeadPipelineStage::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_leads_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            Lead::class,
            fn () => Lead::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_stage_changes_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadStageChange::class,
            fn () => LeadStageChange::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_notes_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadNote::class,
            fn () => LeadNote::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_activities_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadActivity::class,
            fn () => LeadActivity::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_files_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadFile::class,
            fn () => LeadFile::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_lead_emails_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            LeadEmail::class,
            fn () => LeadEmail::factory()->for($this->tenantB)->create(),
        );
    }
}
