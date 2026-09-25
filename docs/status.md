# Status — where this stands right now

Last updated 2026-09-24. Read this first when resuming; it's
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
  teams (org-hierarchy derived, not a stored entity).
- **Phase 3, module 3 — onboarding/offboarding approvals**: the L2 gate on
  new hires, rehires and exits; direct-report reassignment; configurable
  per-tenant departure reasons with country defaults; nightly
  `staff:activate-pending` / `staff:process-offboarding` looping every
  tenant. Scope chosen after comparing Frappe HRMS, Horilla and Odoo
  (`docs/research/onboarding-offboarding.md`).
- **76/76 tests passing**, all against real Postgres 17 + Memurai (not
  mocks/sqlite) — this matters because the whole isolation design is a
  Postgres RLS feature that doesn't exist on sqlite.

## Not done yet (Phase 3 continues)

Next: **onboarding/offboarding checklist** — templates, a task list per
person assigned to a role or "their manager", status derived from the
tasks. Agreed with the user as its own module, layered on the approval gate
(see the research doc's takeaways). Then, per `docs/inventory.md`: salaries
hub, attendance hub (picks up `ApprovalHistory` and the attendance backfill
on onboarding approval), HR dashboard, staff ID card (build new — no source
to port), setup wizard, subscription enforcement, US payroll, Excel engine,
then ToddlersApp's accounts/fees/billing/ledger (ledger is new-build — see
Checkpoint 1 decisions).

**Frontend: no stack chosen yet.** Discussed with the user 2026-09-24 —
recommendation is a Vue 3 app on the existing Sanctum API (same family as
SLMS360, one backend path shared with the Flutter app, own design rather
than Quasar's). The user liked Horilla's demo as a layout reference. Decide
before building any screens.

## How to resume locally

Check `git status` / `git log` for what's committed. PostgreSQL 17 and Memurai are both Windows services set
to **Automatic** startup, so they'll already be running after a reboot —
nothing to launch manually.

```
cd "C:\Personal Doc\Business\BrainStormers\Software\Opsyra"
php artisan test            # should show 76 passed
php artisan serve           # http://localhost:8000
```

If either service isn't running for some reason:
`Start-Service postgresql-x64-17` / `Start-Service Memurai` (PowerShell).

Databases already exist and don't need recreating: `opsyra_us`, `opsyra_uk`,
`opsyra_om`, `opsyra_pk` (dev, role `opsyra`) and `opsyra_us_test` (test
suite — `phpunit.xml` points here, not sqlite).

**`opsyra_uk`, `opsyra_om`, `opsyra_pk` are on hold** (user decision,
2026-09-24): development runs on `opsyra_us` only. Don't migrate or touch
the other three until the user says so — they're behind the latest schema,
which is expected.

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
- **"Today" is UTC for every tenant.** The nightly jobs and immediate-vs-
  scheduled exit decisions use the app timezone; a Pakistan or Oman tenant's
  midnight isn't UTC midnight. Needs a per-tenant/country timezone.
- **No login endpoint yet**, and nothing blocks a non-active user
  (`pending_approval`, `offboarded`) from using an existing API token other
  than offboarding revoking tokens. Needs a status check once auth is built.
- **No permission seeder** — permissions (`HRMS-StaffManagement`,
  `HRMS-Approval-L2`, …) are only created in tests so far.
- `_reference/` now also holds design-reference clones (Frappe HRMS,
  Horilla, Odoo, Krayin, OroCRM) — ideas only, no code copying; see
  `docs/decisions.md` for the licence policy.
