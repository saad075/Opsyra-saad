<?php

use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The global email registry: email -> user_id, for every tenant in this
 * country deployment. Deliberately NOT tenant-scoped and NOT RLS-protected
 * (like client_directory) — its entire purpose is to answer "does this
 * email exist anywhere" for validation, which a tenant-scoped, RLS-protected
 * table such as `users` can never do for itself (see TenantRls: a session
 * only ever sees its own tenant's rows, by design). The real, race-free
 * uniqueness guarantee is the plain unique(email) constraint added to
 * `users` in the previous migration; this table exists purely so the app
 * can give a friendly validation error before ever reaching that
 * constraint, kept in sync via App\Modules\Tenancy\Concerns\HasGloballyUniqueEmail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_emails');
    }

    /**
     * A plain `DB::table('users')->get()` here would see zero rows: RLS
     * blinds even the migration's own connection when no app.tenant_id is
     * set (confirmed empirically — this isn't a theoretical caveat). So this
     * walks every tenant's own context, the same pattern TenantProvisioner
     * and DemoCompanyBuilder already use for the same reason. `tenants`
     * itself isn't tenant-scoped, so reading it needs no such workaround.
     */
    private function backfill(): void
    {
        $tenantContext = app(TenantContext::class);

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $tenantContext->runAs($tenantId, function () {
                $users = DB::table('users')->select('id', 'email', 'created_at', 'updated_at')->get();

                foreach ($users as $user) {
                    DB::table('user_emails')->insert([
                        'email' => $user->email,
                        'user_id' => $user->id,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ]);
                }
            });
        }
    }
};
