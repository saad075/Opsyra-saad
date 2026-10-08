<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users becomes the first tenant-scoped table: every user belongs to exactly
 * one tenant, and (unlike a single-tenant app) email is only unique within a
 * tenant, not globally — two different tenants can each have their own
 * admin@example.com.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);

            $table->foreignId('tenant_id')
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'email']);
        });

        TenantRls::enable('users');
    }

    public function down(): void
    {
        TenantRls::disable('users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('email');
        });
    }
};
