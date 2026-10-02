<?php

use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stages belong to exactly one pipeline, each with its own code/name/
 * probability/sort_order — not a shared lead_stages lookup reused across
 * pipelines. This matches Krayin's CURRENT design, not its original 2021
 * one: Krayin started with a shared lead_stages table + a
 * lead_pipeline_stages.lead_stage_id FK, then (2021_09_30_154222 /
 * 2021_11_11_180804 in its history) denormalized code+name directly onto
 * lead_pipeline_stages and repointed leads at a specific pipeline's stage
 * row, not the shared lookup — so porting the shared-table version would
 * mean porting since-abandoned design, not "the Leads module" as it
 * actually works today.
 *
 * `code` is how business logic (not display text) recognises a terminal
 * stage — LeadController treats code 'won' / 'lost' specially, same
 * convention as Krayin's own stage codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_pipeline_id')->constrained('lead_pipelines')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('probability')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['lead_pipeline_id', 'code']);
            $table->unique(['lead_pipeline_id', 'name']);
        });

        TenantRls::enable('lead_pipeline_stages');

        Tenant::withTrashed()->each(fn (Tenant $tenant) => LeadPipeline::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        TenantRls::disable('lead_pipeline_stages');
        Schema::dropIfExists('lead_pipeline_stages');
    }
};
