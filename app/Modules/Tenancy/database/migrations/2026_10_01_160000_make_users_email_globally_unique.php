<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email becomes unique across the whole country deployment, not just within
 * its own tenant (user decision, 2026-10-01 — reverses the "unique per
 * company only" design from add_tenant_id_to_users_table). See
 * docs/decisions.md for the full reasoning.
 *
 * A plain unique index is the right enforcement even though `users` is
 * RLS-protected and tenant B's session can never SELECT tenant A's row:
 * Postgres validates unique constraints (at build time and on every
 * subsequent insert/update) against the whole physical table, independent
 * of RLS policies — RLS only filters what a query's result set shows, never
 * what a unique index checks. Confirmed empirically against this schema
 * before writing this migration, rather than assumed.
 *
 * If any two tenants already share an email, ADD constraint below fails
 * with Postgres's own "could not create unique index ... Duplicate keys
 * exist" — surfaced here with context on what to do next, since the bare
 * Postgres error doesn't say which table/column caused it.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'email']);
                $table->unique('email');
            });
        } catch (QueryException $e) {
            throw new RuntimeException(
                'Could not make users.email globally unique — two or more tenants already share an '.
                'email address. Resolve the conflicting accounts (merge or rename one) before re-running '.
                'this migration.',
                previous: $e
            );
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });
    }
};
