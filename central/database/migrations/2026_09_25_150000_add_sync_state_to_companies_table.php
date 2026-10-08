<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central is the source of truth; each change is pushed to the company's
 * country deployment. These record whether the last push landed, so the
 * dashboard can show (and retry) any company whose country copy is stale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamp('suspended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['synced_at', 'sync_error', 'suspended_at']);
        });
    }
};
