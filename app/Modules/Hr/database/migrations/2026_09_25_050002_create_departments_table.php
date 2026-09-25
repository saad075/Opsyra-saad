<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's 2026_09_12_000003_create_departments_table.php —
// client_id -> tenant_id (real FK + RLS, not just an indexed column).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('departments');
    }

    public function down(): void
    {
        TenantRls::disable('departments');
        Schema::dropIfExists('departments');
    }
};
