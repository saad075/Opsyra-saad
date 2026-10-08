<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * rotten_days (default 30, matching Krayin's own default): a lead is
 * considered stale once it's sat this long in its current stage. Opsyra has
 * no activity log in this phase (see docs/research/crm-leads.md — contact
 * info is inline on the lead, not a separate Contacts/Activity module), so
 * "stale" is approximated from the lead's own updated_at rather than a real
 * last-activity timestamp — a deliberate simplification, not Krayin's actual
 * mechanism.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('rotten_days')->default(30);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('lead_pipelines');
    }

    public function down(): void
    {
        TenantRls::disable('lead_pipelines');
        Schema::dropIfExists('lead_pipelines');
    }
};
