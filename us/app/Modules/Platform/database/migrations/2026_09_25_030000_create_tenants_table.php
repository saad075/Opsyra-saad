<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform table — not tenant-scoped, no RLS. This IS the tenant registry for
 * this country deployment; every tenant-scoped table's tenant_id references it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->unique();
            $table->string('name');
            $table->string('country', 2);
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled'])->default('trial');

            // Escape hatch: when set, this tenant's queries use this named
            // connection (see config/database.php) instead of the country
            // deployment's default shared connection — no code changes needed
            // to move a large tenant onto its own database.
            $table->string('database_connection')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
