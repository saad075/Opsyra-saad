<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// offboarding_new_manager_id: from SLMS360's
// 2026_09_06_000004_add_offboarding_new_manager_id_column_to_users_table.php
// — holds the chosen replacement manager for a future-dated exit until
// staff:process-offboarding applies it. departure_reason_id is new: the
// structured reason behind the existing free-text left_reason, for reporting.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('offboarding_new_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('departure_reason_id')->nullable()->constrained('departure_reasons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offboarding_new_manager_id');
            $table->dropConstrainedForeignId('departure_reason_id');
        });
    }
};
