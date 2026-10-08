<?php

use App\Modules\Crm\Models\LeadSource;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads module — design-referenced against Krayin's packages/Webkul/Lead
 * (MIT, no code copied — see docs/research/crm-leads.md and
 * docs/decisions.md). lead_sources is a per-tenant configurable list, same
 * shape as Krayin's own, extended with the sequence/is_active/is_system
 * columns Opsyra already uses for every other configurable list
 * (departure_reasons, pay_components) rather than inventing a new pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sequence')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('lead_sources');

        Tenant::withTrashed()->each(fn (Tenant $tenant) => LeadSource::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        TenantRls::disable('lead_sources');
        Schema::dropIfExists('lead_sources');
    }
};
