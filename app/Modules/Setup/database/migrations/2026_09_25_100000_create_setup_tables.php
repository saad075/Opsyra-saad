<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New. tenant_settings holds the company profile (name, address, timezone,
// currency…) — SLMS360 kept these as getSetting() rows and columns on the
// client's users row. setup_step_states records only what an admin chose
// (skipped, or reviewed defaults), never completion — completion is always
// computed from the data (docs/research/setup-wizard.md).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        TenantRls::enable('tenant_settings');

        Schema::create('setup_step_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('step_key');
            $table->string('state'); // skipped, reviewed
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'step_key']);
        });

        TenantRls::enable('setup_step_states');
    }

    public function down(): void
    {
        TenantRls::disable('setup_step_states');
        Schema::dropIfExists('setup_step_states');
        TenantRls::disable('tenant_settings');
        Schema::dropIfExists('tenant_settings');
    }
};
