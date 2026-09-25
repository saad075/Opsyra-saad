<?php

namespace App\Modules\Tenancy\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * DB-layer half of tenant isolation. Call TenantRls::enable($table) at the end
 * of any migration that creates or alters a tenant-scoped table (one with a
 * tenant_id column) — this is the backstop that still protects the row even
 * if a query forgets the app-layer BelongsToTenant scope.
 *
 * current_setting('app.tenant_id', true) uses missing_ok=true so it returns
 * NULL rather than erroring when the GUC was never set at all this session.
 * But Postgres quirk: once it HAS been set and is then RESET (as
 * TenantContext::clear() does at the end of every request), it reverts to an
 * empty string, not NULL — custom GUCs have no declared default to revert
 * to. NULLIF(..., '') normalizes both "never set" and "reset after being
 * set" to NULL before the ::bigint cast, so `tenant_id = NULL` (never true)
 * covers both cases instead of the empty-string cast throwing an error.
 */
class TenantRls
{
    public static function enable(string $table): void
    {
        self::assertSafeIdentifier($table);

        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");

        // FORCE is required because Postgres otherwise exempts the table
        // owner from RLS — and the app's own DB role owns every table it
        // migrates, so without FORCE the policy below would be a no-op.
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

        DB::statement("
            CREATE POLICY tenant_isolation_policy ON {$table}
            USING (tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::bigint)
            WITH CHECK (tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::bigint)
        ");
    }

    public static function disable(string $table): void
    {
        self::assertSafeIdentifier($table);

        DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
        DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
    }

    private static function assertSafeIdentifier(string $table): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new InvalidArgumentException("Unsafe table identifier for RLS: {$table}");
        }
    }
}
