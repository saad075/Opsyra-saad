<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The reusable harness every tenant-scoped model must pass, per the project
 * rule that every future module proves isolation the same way. Combine with
 * SeedsTwoTenants in a Feature test:
 *
 *   $this->assertModelIsTenantIsolated(
 *       Staff::class,
 *       fn () => Staff::factory()->for($this->tenantB)->create(),
 *   );
 *
 * Checks three independent layers so a failure tells you exactly which one
 * broke: the app-layer BelongsToTenant scope, raw SQL under RLS (proving
 * isolation holds even if a query bypasses Eloquent entirely), and that the
 * record is still visible to its own tenant (so a pass isn't just "nothing is
 * ever visible").
 */
trait AssertsTenantIsolation
{
    use SeedsTwoTenants;

    protected function assertModelIsTenantIsolated(string $modelClass, \Closure $createUnderTenantB): void
    {
        /** @var Model $record */
        $record = $this->tenantContext()->runAs($this->tenantB->id, $createUnderTenantB);

        $table = $record->getTable();
        $id = $record->getKey();

        // Layer 1: app-layer scope, as tenant A.
        $this->tenantContext()->runAs($this->tenantA->id, function () use ($modelClass, $id) {
            $this->assertNull(
                $modelClass::find($id),
                "{$modelClass}::find() returned another tenant's row — BelongsToTenant scope is missing or broken."
            );

            $this->assertFalse(
                $modelClass::query()->pluck('id')->contains($id),
                "{$modelClass}::all() included another tenant's row — BelongsToTenant scope is missing or broken."
            );
        });

        // Layer 2: raw SQL under RLS, as tenant A — proves the DB layer holds
        // even for code that bypasses Eloquent's global scope entirely.
        $this->tenantContext()->runAs($this->tenantA->id, function () use ($table, $id) {
            $row = DB::table($table)->where('id', $id)->first();

            $this->assertNull(
                $row,
                "Raw query under tenant A's RLS session returned another tenant's row from {$table} — RLS policy is missing, not forced, or misconfigured."
            );
        });

        // Layer 3: sanity check — the record is visible to its own tenant,
        // so layers 1–2 aren't just passing because nothing is ever visible.
        $this->tenantContext()->runAs($this->tenantB->id, function () use ($modelClass, $id) {
            $this->assertNotNull(
                $modelClass::find($id),
                "{$modelClass}::find() couldn't find its own tenant's row — check the fixture, not just isolation."
            );
        });
    }
}
