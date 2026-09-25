# Status — where this stands right now

Paused 2026-09-25 for a system restart. Read this first when resuming; it's
the "what's done, what's next, how to get running again" summary. Details
live in `docs/decisions.md` (choices + reasoning), `docs/provenance.md`
(module → source → what changed), `docs/inventory.md` (the Phase 1 port plan).

## Done

- **Phase 1** — reference clones (`_reference/slms360@v2.7.1`,
  `_reference/toddlersapp@master`, read-only, origins removed), full module
  inventory, Checkpoint 1 decisions recorded.
- **Phase 2** — Laravel 12 app scaffolded; tenancy core built and tested:
  `BelongsToTenant` trait + `TenantScope`, `TenantContext`/`Tenant` facade,
  `ResolveTenantFromUser` middleware, `TenantRls` (Postgres row-level
  security) migration helper, tenant-aware queue jobs, platform tables
  (`tenants`, `plans`, `subscriptions`, `tax_tables`, `super_admins`), the
  Directory module (`POST /api/resolve`), and the cross-tenant isolation
  test harness (`tests/Support/{SeedsTwoTenants,AssertsTenantIsolation}.php`).
- **Phase 3, module 1 — RBAC**: spatie/laravel-permission with its "teams"
  feature wired to `tenant_id` via a custom resolver reading `TenantContext`;
  `RolePolicy` (permission + tenant-ownership checks).
- **Phase 3, module 2 — HR core**: staff CRUD, departments/designations,
  teams (org-hierarchy derived, not a stored entity). Onboarding/offboarding
  approval workflow (the L2 gate) deliberately excluded — separate next
  module.
- **45/45 tests passing**, all against real Postgres 17 + Memurai (not
  mocks/sqlite) — this matters because the whole isolation design is a
  Postgres RLS feature that doesn't exist on sqlite.

## Not done yet (Phase 3 continues)

Next module per the inventory order: **onboarding/offboarding approvals
(the L2 gate)** — `StaffOnboardingRequest`, `StaffOffboardRequest`,
`ApprovalHistory`, wraps on top of the HR core module's `StaffController`
rather than replacing it. After that, per `docs/inventory.md`: salaries hub,
attendance hub, HR dashboard, staff ID card (build new — no source to port),
setup wizard, subscription enforcement, US payroll, Excel engine, then
ToddlersApp's accounts/fees/billing/ledger (ledger is new-build — see
Checkpoint 1 decisions).

## How to resume locally

Everything is committed (`git log` — last commit `37d09db`, working tree
clean as of pause). PostgreSQL 17 and Memurai are both Windows services set
to **Automatic** startup, so they'll already be running after a reboot —
nothing to launch manually.

```
cd "C:\Personal Doc\Business\BrainStormers\Software\Opsyra"
php artisan test            # should show 45 passed
php artisan serve           # http://localhost:8000
```

If either service isn't running for some reason:
`Start-Service postgresql-x64-17` / `Start-Service Memurai` (PowerShell).

Databases already exist and don't need recreating: `opsyra_us`, `opsyra_uk`,
`opsyra_om`, `opsyra_pk` (dev, role `opsyra`) and `opsyra_us_test` (test
suite — `phpunit.xml` points here, not sqlite).

## Known gaps / open items

- `docker-compose.yml`/`docker/` are written but **unverified** — no Docker
  on this machine; everything so far was built and tested against the native
  Postgres/Memurai install instead (see `docs/decisions.md`, 2026-09-25 entry).
- No CRUD routes exist yet for `branches` themselves (full branch
  management) — the HR core module only created the table as a staff-CRUD
  dependency; see `docs/provenance.md`'s HR core entry.
- Staff ID card, US payroll tax tables, and the double-entry ledger are all
  confirmed new-build items (no source to port) per Checkpoint 1 — not
  started.
