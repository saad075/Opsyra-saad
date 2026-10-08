<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's 2026_09_12_000004_create_designations_table.php —
// client_id -> tenant_id (real FK + RLS). This table is a reference/taxonomy
// list; users.designation stays a free-text column (see the users-alter
// migration in this module), matching SLMS360's own design — designations is
// additive to a hardcoded global list, not an enforced foreign key.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('designations');
    }

    public function down(): void
    {
        TenantRls::disable('designations');
        Schema::dropIfExists('designations');
    }
};
