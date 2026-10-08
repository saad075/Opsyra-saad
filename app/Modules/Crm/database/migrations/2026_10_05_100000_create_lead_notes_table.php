<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The second (and, for this phase, last) piece of Krayin's Activity feed
 * ported — plain notes on a lead, not the polymorphic multi-target notes
 * from the earlier (reverted) Companies/People/Opportunities attempt.
 * lead_id is a direct FK, not a notable_type/notable_id pair — this phase
 * only ever attaches notes to leads, so the polymorphism that build needed
 * doesn't apply here. Calls/Meetings/Lunches/Files/Emails/Mail/Tags stay
 * out of scope (see docs/research/crm-leads.md and the 2026-10-05 decision).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id']);
        });

        TenantRls::enable('lead_notes');
    }

    public function down(): void
    {
        TenantRls::disable('lead_notes');
        Schema::dropIfExists('lead_notes');
    }
};
