<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SLMS360 writes to a plain `user_update_logs` table (no model, no tenant
// key at all — DB::table('user_update_logs')->insert(...) in StaffController
// ::update()). Log tables get tenant_id + RLS the same as any other table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_update_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });

        TenantRls::enable('user_update_logs');
    }

    public function down(): void
    {
        TenantRls::disable('user_update_logs');
        Schema::dropIfExists('user_update_logs');
    }
};
