<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adapted from SLMS360's user_branches pivot (2024_05_09_060713_create_user_branches_table.php).
 * SLMS360's version had no timestamps, no FK constraints (just indexed
 * columns), and no unique constraint — duplicate (user_id, branch_id) rows
 * were possible. Opsyra adds tenant_id + RLS (a tenant table rule), real FK
 * constraints, and a unique(tenant_id, user_id, branch_id) to close that gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->boolean('status')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'branch_id']);
        });

        TenantRls::enable('user_branches');
    }

    public function down(): void
    {
        TenantRls::disable('user_branches');
        Schema::dropIfExists('user_branches');
    }
};
