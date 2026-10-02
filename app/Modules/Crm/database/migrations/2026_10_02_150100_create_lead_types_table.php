<?php

use App\Modules\Crm\Models\LeadType;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Same shape and reasoning as lead_sources — see that migration.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sequence')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('lead_types');

        Tenant::withTrashed()->each(fn (Tenant $tenant) => LeadType::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        TenantRls::disable('lead_types');
        Schema::dropIfExists('lead_types');
    }
};
