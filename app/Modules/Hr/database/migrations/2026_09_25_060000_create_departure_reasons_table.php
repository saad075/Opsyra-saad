<?php

use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New — no SLMS360 equivalent. SLMS360 stored the offboarding reason as free
// text (with an "Other: <note>" convention); a configurable per-tenant list
// follows Odoo's hr.departure.reason design (see
// docs/research/onboarding-offboarding.md), so turnover can be reported by
// reason. Defaults are seeded per tenant from the tenant's country.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departure_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sequence')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('requires_note')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('departure_reasons');

        // Tenants that already exist when this ships get the same defaults
        // a newly created tenant gets from HrServiceProvider.
        Tenant::withTrashed()->each(fn (Tenant $tenant) => DepartureReason::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        TenantRls::disable('departure_reasons');
        Schema::dropIfExists('departure_reasons');
    }
};
