<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adapted from SLMS360's branches table (2024_05_09_054714_create_branches_table.php).
 * SLMS360 used branches.user_id as its de facto tenant key (the client-owner's
 * own User row — there's no separate tenant concept there). Opsyra has a real
 * tenants table, so this is tenant_id + RLS instead, and the geo/contact
 * fields (country_id, lat/long, phone, email, address) SLMS360 carries on
 * Branch are dropped — nothing in the HR-core module this supports reads or
 * writes them, and full branch management (those fields, a BranchController)
 * is its own not-yet-ported concern. See docs/provenance.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('branches');
    }

    public function down(): void
    {
        TenantRls::disable('branches');
        Schema::dropIfExists('branches');
    }
};
