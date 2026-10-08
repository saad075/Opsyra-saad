<?php

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Tenancy\Support\TenantContext;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The company account owner (post-deployment fixes, 2026-09-30): one person
// per company who always keeps the Administrator role and can't be
// offboarded, so a company can't lock itself out. See docs/decisions.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        // Who handed ownership to whom, and why. Kept for good: it's the
        // record of who controls the company account.
        Schema::create('ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            // The owner themselves, or null when Opsyra support reassigned it.
            $table->foreignId('by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('by_label')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
        });

        TenantRls::enable('ownership_transfers');

        // Existing companies: the first administrator (the sign-up person),
        // preferring someone still active.
        Tenant::withTrashed()->each(function (Tenant $tenant) {
            $ownerId = app(TenantContext::class)->runAs($tenant->id, fn () => User::role(DefaultRoles::ADMINISTRATOR)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->value('users.id'));

            if ($ownerId) {
                $tenant->forceFill(['owner_user_id' => $ownerId])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        TenantRls::disable('ownership_transfers');
        Schema::dropIfExists('ownership_transfers');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }
};
