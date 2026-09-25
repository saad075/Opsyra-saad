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
- **Phase 3, module 4 — onboarding/offboarding checklists** (new build, no
  SLMS360 source): per-tenant templates whose tasks go to the staff
  member's manager, the staff member, a role, or a named person, due N days
  before/after the start date or last working day. Approval starts a
  checklist from the chosen or default template; "my tasks" plus ticking
  off; the checklist completes itself when every task is done or skipped.
  Every tenant starts with a starter onboarding and offboarding template.
- **Phase 3, module 5 — Salaries hub** (new `app/Modules/Payroll`):
  configurable pay components with country defaults, effective-dated
  salaries, encrypted bank details, adjustments, monthly and biweekly pay
  runs (queued), itemised payslips, L1→L2 approval, transfer/cheque,
  carry-forward, salary queries. Attendance deductions plug in later via
  `PayslipContributor`. See `docs/research/salaries.md`.
- **Phase 3, module 6 — Setup wizard** (new `app/Modules/Setup`, moved up
  by the user): live-computed progress across every module's steps, each
  module registering its own via `SetupStepProvider`; data steps and
  "review the defaults" steps; skip / come back; company profile settings
  (name, address, timezone, currency); branch management (was a gap). See
  `docs/research/setup-wizard.md`. **Rule: any new module that adds
  per-company configuration must register setup steps for it.**
- **139/139 tests passing**, all against real Postgres 17 + Memurai (not
  mocks/sqlite) — this matters because the whole isolation design is a
  Postgres RLS feature that doesn't exist on sqlite.

## Not done yet (Phase 3 continues)

Next, per `docs/inventory.md`: **attendance hub** (plugs deductions into
payslips via `PayslipContributor`; picks up `ApprovalHistory`, the
attendance backfill on onboarding approval, and hourly pay), HR dashboard,
staff ID card (build new: no source to port), subscription enforcement, US
payroll, Excel engine, then ToddlersApp's accounts/fees/billing/ledger
(ledger is new-build — see Checkpoint 1 decisions).

**Frontend: no stack chosen yet.** Discussed with the user 2026-09-24 —
recommendation is a Vue 3 app on the existing Sanctum API (same family as
SLMS360, one backend path shared with the Flutter app, own design rather
than Quasar's). The user liked Horilla's demo as a layout reference. Decide
before building any screens.

**Test deployment: on hold until the frontend is ready** (user decision,
2026-09-24). Target is `opsyra.brainstormertech.com` on BrainStormers'
existing server, alongside SLMS360. DNS is at the domain registrar; the user
adds the A record. Unconfirmed: the server is probably `203.161.41.187`
(the only non-GitHub host in this PC's `~/.ssh/known_hosts`), and SLMS360's
root `.htaccess` suggests Apache, possibly under cPanel. Nobody has
connected to it yet: ask before any SSH, starting read-only. Hard
requirement when it happens: the app's Postgres role must not be a
superuser or have BYPASSRLS, or tenant isolation silently switches off.

## How to resume locally

Check `git status` / `git log` for what's committed. PostgreSQL 17 and Memurai are both Windows services set
to **Automatic** startup, so they'll already be running after a reboot —
nothing to launch manually.

```
cd "C:\Personal Doc\Business\BrainStormers\Software\Opsyra"
php artisan test            # should show 139 passed
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
- Staff ID card, US payroll tax tables, and the double-entry ledger are all
  confirmed new-build items (no source to port) per Checkpoint 1 — not
  started.
- **"Today" is UTC for every tenant.** The nightly jobs and immediate-vs-
  scheduled exit decisions use the app timezone; a Pakistan or Oman tenant's
  midnight isn't UTC midnight. The company timezone is now stored
  (`TenantSetting::get('timezone')`, set in the Setup wizard) and branches
  have their own; the jobs don't use them yet.
- **No login endpoint yet**, and nothing blocks a non-active user
  (`pending_approval`, `offboarded`) from using an existing API token other
  than offboarding revoking tokens. Needs a status check once auth is built.
- **No permission seeder** — permissions (`HRMS-StaffManagement`,
  `HRMS-Approval-L2`, `HRMS-SalaryManagement`, `HRMS-CompanySettings`, …)
  are only created in tests so far. Also: nothing gives a new tenant's first
  admin `HRMS-CompanySettings`, so nobody could open the Setup wizard. Both
  are needed before the first real company (tenant provisioning).
- `_reference/` now also holds design-reference clones (Frappe HRMS,
  Horilla, Odoo, Krayin, OroCRM) — ideas only, no code copying; see
  `docs/decisions.md` for the licence policy.
