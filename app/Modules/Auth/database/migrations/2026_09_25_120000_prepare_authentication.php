<?php

use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - personal_access_tokens.tenant_id: Sanctum loads a token's user from
 *   `users`, which is under RLS — with no tenant context set yet, the user
 *   was invisible and every real bearer-token request failed (tests only
 *   passed because actingAs() skips the token path). The token now records
 *   its tenant, and TenantAccessToken switches into it before loading the
 *   user. The table itself stays outside RLS: it has to be readable before
 *   any tenant is known, and lookups are by a SHA-256 hash of a secret.
 * - users.must_change_password: set for anyone given a password they
 *   didn't choose (a provisioned admin, a newly created staff member).
 * - login_logs: adapted from SLMS360's LoginLog (ip, user agent, time).
 * - password_reset_codes: tenant-scoped replacement for Laravel's
 *   password_reset_tokens, which is keyed by email alone — emails are only
 *   unique within a tenant here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false);
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_name')->nullable();
            $table->timestamp('login_at');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'login_at']);
        });

        TenantRls::enable('login_logs');

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64); // sha256 of the emailed token
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });

        TenantRls::enable('password_reset_codes');

        // New permission for the login history; existing Administrators get it.
        DefaultRoles::grantToAdministrators('HRMS-ViewLoginLogs');
    }

    public function down(): void
    {
        TenantRls::disable('password_reset_codes');
        Schema::dropIfExists('password_reset_codes');
        TenantRls::disable('login_logs');
        Schema::dropIfExists('login_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
