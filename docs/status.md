# Status — where this stands right now

Last updated 2026-09-29. Read this first when resuming; it's
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
- **Frontend** (`frontend/`, Vue 3 + TypeScript + Naive UI; see
  `docs/decisions.md`): sign in, forgot/reset password, forced password
  change, app shell with a permission-filtered sidebar, branch switcher and
  light/dark mode, the Setup wizard, Company details, Branches, HR and
  Payroll dashboards, Staff (list, profile, add/edit, offboard), Approvals,
  My tasks, checklists, and **Payroll: pay runs with bulk approve,
  printable payslips, cheques, salary queries, My payslips** (2026-09-25),
  and **every Setup wizard step now has its screen**: departments and job
  titles, roles, approvers, leaving reasons, checklist templates, pay
  components, salaries (live preview) and bank details (2026-09-26).
  Payroll rules added 2026-09-26 (user): nobody approves their own
  payslip; carry forward only takes pay that hasn't gone out.
- **Attendance module** (new `app/Modules/Attendance`, 2026-09-26; see
  `docs/research/attendance.md` and `docs/decisions.md`): weekly working
  hours and holidays; clocking from the staff app or the browser, with the
  location checked against the branch (flagged, never refused); an hourly
  close-out that settles each day; HR marking and staff correction
  requests; **client visits for home care** (Kindstead) with GPS check-in
  per visit, visit plans and a coordinator review queue; the payroll link
  (hourly pay from verified time; optional attendance deductions); an
  Attendance dashboard and screens for all of it. The demo has three weeks
  of attendance and a three-caregiver home-care team paid by the hour.
  The **app API** (for the coming staff app) mirrors SLMS360's:
  `attendance/today|clock|month|my-schedule`, `attendance/corrections/*`,
  `visits/my-day|check-in|check-out|my-month`.
- **Opsyra Central** (`central/`): landing site, pricing structure (per
  country, admin-editable), free-trial sign-up, super-admin subscription
  dashboard, and the nightly-rebuilt live demo company.
- **Tests: 259 country API + 34 Central + 25 frontend, all passing**; the API ones run against real Postgres 17 + Memurai (not
  mocks/sqlite) — this matters because the whole isolation design is a
  Postgres RLS feature that doesn't exist on sqlite.

## Not done yet (Phase 3 continues)

**Agreed order (user, 2026-09-25):** 1) login / auth (done), 2) **the
frontend (in progress)**, 3) **Opsyra Central** (a new app for the landing site, sign-up,
billing and super-admin; see `docs/research/subscription-architecture.md`)
together with the test deployment, before the first paying customer. The
modules below continue alongside as agreed.

Then, per `docs/inventory.md`: **attendance hub** (plugs deductions into
payslips via `PayslipContributor`; picks up `ApprovalHistory`, the
attendance backfill on onboarding approval, and hourly pay), HR dashboard,
staff ID card (build new: no source to port), subscription enforcement, US
payroll, Excel engine, then ToddlersApp's accounts/fees/billing/ledger
(ledger is new-build — see Checkpoint 1 decisions).

**Next (user, 2026-09-27): a "post-deployment fixes" batch**, gathered
while the user tries the deployed test system.

0. **Sign-ups failing (fixed and deployed 2026-09-29).** Three
   trial sign-ups (kindstead, brains, 1234) showed "Setup failed": the US
   deployment created each company, then crashed sending the welcome email
   because the Elastic Email account is **out of credit** (SMTP 421 "Not
   enough credit"); Retry then got 409 "already taken" forever. Fixed:
   welcome and password-reset emails are queued (`Auth/Jobs/SendSignInLink`);
   a repeated sign-up for the same company + admin succeeds and re-sends
   the link; Central has "Resend welcome email"; the US deployment records
   email failures (`Platform/Support/MailHealth`) and Central's dashboard
   shows a red alert when a country's email is failing. Deployed; Retry
   worked on all three (2026-09-29). Email now goes through the Google
   Workspace relay (see "Test deployment" below).

1. **Company account owner (done in code 2026-09-30, needs deploying).**
   See `docs/decisions.md`. Was: the first Administrator (the sign-up
   person; Alex Morgan in the demo) was protected by convention only — a
   company can delete/edit the Administrator role, strip it from its only
   admin, or offboard them, and lock itself out. Add: one owner per company
   (the sign-up admin) who can't be deleted/offboarded or lose Administrator;
   the Administrator role can't be deleted or reduced; the last administrator
   can never be removed; owner-only, audited ownership transfer; Central
   shows the owner and the super admin can reassign it if a company is
   locked out.

More items get added here as the user finds them. After this batch: the
Leave module (types, yearly allowances, requests, approvals — it takes over
the "on leave (paid/unpaid)" marking HR does by hand now).

**Test deployment: live** (first deploy 2026-09-27 19:07 UTC).
`opsyra.brainstormertech.com` (Central; super admin at `/admin/login`) and
`us.opsyra.brainstormertech.com` (the app + API), on the BrainStormers
cPanel server (AlmaLinux 8, PostgreSQL 16, PHP 8.3). GitHub
(`brainstormertech/Opsyra`) Actions tests, builds a bundle
(`deploy/build-bundle.sh`) and pushes it to the cPanel repository
`~/repositories/opsyra-deploy`, whose `.cpanel.yml` runs `deploy/deploy.sh`
(release folders, `opsyra:preflight`, migrations, atomic switch). See
`docs/deployment.md`. Email goes through Elastic Email SMTP (the account
SLMS360 uses), **out of credit since at least 2026-09-28**. **Switched
2026-09-29 to Google Workspace's SMTP relay** (by server IP, no password;
WHM SMTP Restrictions disabled, as it redirected the connection to the local
Exim; see `docs/deployment.md` step 3). The relay test email arrived, in spam.
DNS done by the user 2026-09-29 (SPF/DKIM/DMARC, stray SES MX removed).
SLMS360 moved to the same relay 2026-09-30 (its `config/mail.php` now
reads `.env`; admin, alis, seventhgen deployed and tested). Gotcha: the
relay refuses EHLO `[127.0.0.1]` (421 4.7.0), so an app needs
`MAIL_EHLO_DOMAIN` or a default from APP_URL (Opsyra has the default). The user first asked for a cPanel
mailbox; that was checked and rejected:
brainstormertech.com's mail is on Google Workspace (MX), DNS is at
Namecheap (registrar-servers.com), the SPF record only lists Amazon SES,
and the server's reverse DNS is a generic web-hosting.com name, so mail
sent from the server would likely go to spam. Recommended instead: Google
Workspace SMTP relay (free with the existing Workspace), chosen. Also found: an
Amazon SES feedback MX on the root domain alongside Google's; that looks
misconfigured and may lose incoming mail. Open deployment follow-ups: turn off SSH password logins
(password-guessing seen in the server log); move SLMS360 onto the same
pipeline later (its email password is in `config/mail.php`).

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

- **AVG antivirus on this machine** flagged test files as `IDP.Generic`
  (a heuristic false positive), quarantined them, and held connections
  open. That made test runs appear to hang, and it left idle-in-transaction
  database connections holding locks. The user added a folder exception
  for the project on 2026-09-25. The quarantined names
  `tests/Feature/Platform/DemoCompanyTest.php` and `PublicDemoTest.php`
  stay blocked until AVG's Virus Chest is cleared; the test lives in
  `DemoResetTest.php`. If tests ever seem to hang, check `pg_stat_activity`
  on `opsyra_us_test` for stale "idle in transaction" connections first.

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
