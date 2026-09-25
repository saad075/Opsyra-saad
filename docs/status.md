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
- **Company provisioning** (`app/Modules/Platform/Services/TenantProvisioner`,
  operator command `php artisan tenants:create`): creates the tenant (every
  module seeds its defaults), a first Administrator holding every
  permission, and the directory entry; refuses a country other than this
  deployment's `APP_COUNTRY`. **Permission catalogue**
  (`Rbac/Support/PermissionCatalogue`) installed by migration and by
  `permissions:sync`, plus five **default roles** per tenant. A test fails
  if code checks a permission that isn't in the catalogue.
- **Login / auth** (new `app/Modules/Auth`): login by client code + email
  + password (rate-limited, no account enumeration), logout, `me`, forced
  password change for temporary passwords, password reset by email, login
  history. Also **fixed a latent bug: real bearer tokens never worked
  under RLS** (see `docs/decisions.md`).
- **Frontend, first slice** (`frontend/`, Vue 3 + TypeScript + Naive UI;
  see `docs/decisions.md`): sign in, forgot/reset password, forced
  password change, app shell with a permission-filtered sidebar and
  light/dark mode, the Setup wizard, Company details, Branches. 6 unit
  tests on the routing rules; type-check and production build pass.
- **159/159 tests passing**, all against real Postgres 17 + Memurai (not
  mocks/sqlite) — this matters because the whole isolation design is a
  Postgres RLS feature that doesn't exist on sqlite.

## Not done yet (Phase 3 continues)

**Agreed order (user, 2026-09-25):** 1) login / auth (done), 2) **the
frontend (next)**, 3) **Opsyra Central** (a new app for the landing site, sign-up,
billing and super-admin; see `docs/research/subscription-architecture.md`)
together with the test deployment, before the first paying customer. The
modules below continue alongside as agreed.

Then, per `docs/inventory.md`: **attendance hub** (plugs deductions into
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
php artisan test            # should show 159 passed
php artisan serve           # API on http://localhost:8000

cd frontend
npm install                 # first time only
npm run dev                 # app on http://localhost:5173 (proxies /api to :8000)
npm test                    # frontend unit tests
npm run build               # type-check + production build
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
- **Dev login:** Demo Company, client code `demo`, `admin@demo.test`,
  password `OpsyraDemo2026`. **Dev database only; never reuse it anywhere
  else.** Emails go to `storage/logs/laravel.log` (`MAIL_MAILER=log`).
- New staff get a random password and set their own with "forgot password".
  A proper invitation email should come with email templates.
- Sanctum tokens don't expire (`sanctum.expiration` is null). Decide a
  lifetime when the mobile app's needs are known.
- No super-admin panel or self sign-up yet: companies are created by an
  operator with `tenants:create`.
- `_reference/` now also holds design-reference clones (Frappe HRMS,
  Horilla, Odoo, Krayin, OroCRM) — ideas only, no code copying; see
  `docs/decisions.md` for the licence policy.
