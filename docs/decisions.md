# Decisions log

Running log of choices made during the Opsyra port. Newest entries at the top.

## 2026-10-01 — Email is globally unique, not per company

- **Reverses the 2026-09-25 decision** ("emails are unique per company
  only", logged under Login by client code + email + password below).
  Product requirement: one email must belong to at most one account across
  the whole country deployment, not just within its own tenant.
- **The real guarantee is a plain `unique(email)` constraint on `users`**
  (was `unique(tenant_id, email)`). This works correctly even though
  `users` is RLS-protected and one tenant's session can never see another
  tenant's rows: Postgres validates unique constraints — at index build
  time and on every subsequent insert/update — against the whole physical
  table, independent of RLS policies. RLS only filters what a query's
  result set shows, never what a unique index checks. Confirmed
  empirically against this schema (a scratch RLS-protected table, two
  tenants, a cross-tenant duplicate) before relying on it, not assumed from
  documentation.
- **`user_emails`** (new, global, no `tenant_id`, no RLS — same shape as
  `client_directory`) exists only so the app can answer "does this email
  exist anywhere" for a friendly validation error. A plain
  `Rule::unique('users', 'email')` can't do this itself: run under a
  tenant's session, RLS still limits what it can see to that tenant's own
  rows, so it would report an email "available" right up until the actual
  insert hits the real constraint and throws a raw `QueryException`.
  `App\Modules\Tenancy\Concerns\HasGloballyUniqueEmail` keeps it in sync
  with `users` (created/updated events; deletion is a DB cascade, not an
  app hook). `StaffController` and `TenantProvisioner` validate against it
  instead of the old `unique('users')->where('tenant_id', ...)`.
- **Migration order matters**: `make_users_email_globally_unique` runs
  first and fails loudly (via Postgres's own "Duplicate keys exist") if any
  two tenants already share an email — by the time `user_emails` is
  created and backfilled in the next migration, uniqueness is already
  guaranteed, so the backfill can't itself conflict.
- **Migration backfill caveat**: a plain `DB::table('users')->get()` inside
  a migration sees zero rows — RLS blinds even the migration's own
  connection when no `app.tenant_id` is set, which is the normal state
  during `migrate`. The backfill walks every tenant's own context instead
  (same pattern `TenantProvisioner`/`DemoCompanyBuilder` already use), not
  one unscoped query.
- Regression tests: `Tests\Feature\Tenancy\GlobalEmailUniquenessTest`
  (registry sync, DB-level rejection, validation rule, delete-frees-email)
  and `TenantProvisioningTest::test_an_email_already_used_by_another_company_cannot_become_a_new_admin`.
  `AuthTest::test_the_same_email_in_another_company_is_a_different_account`
  — which proved the old behavior — is replaced by
  `test_the_same_email_cannot_be_reused_by_another_company`.

## 2026-09-30 — One permissions cache per company

- **Bug (live):** Spatie caches one "permission → roles" list for the
  whole app, but roles are tenant rows behind RLS, so the list only held
  the roles of whichever company's request built it. Every other company's
  administrators were refused everything (Kindstead's owner could open
  nothing) until the cache expired. Tests missed it: each test builds its
  own cache inside one company.
- **Fix:** `Rbac/Support/TenantPermissionRegistrar` replaces Spatie's
  registrar (bound in `RbacServiceProvider::boot`, after Spatie's own
  boot). The cache key and the in-memory list are per tenant; any role or
  permission change bumps a version in the key, so every company's list
  is rebuilt on next use. Regression test: `PermissionCacheIsolationTest`.

## 2026-09-30 — Company account owner

- **Why:** a company could lock itself out: delete or cut down the
  Administrator role, take it from its only admin, or offboard them.
- **One owner per company** (`tenants.owner_user_id`), the sign-up
  administrator to start with; existing companies were backfilled with
  their first administrator. The owner **always keeps the Administrator
  role** (staff edit refuses, 409) and **can't be offboarded** (refused at
  submit and again at approval).
- **The Administrator role is locked:** it can't be renamed, have
  permissions removed, or be deleted (409). New permissions keep being
  added to it by `grantToAdministrators`.
- **"The last administrator can never be removed"** follows from those
  two: the owner is always an active administrator. There is no separate
  count-the-admins check.
- **Transfer:** only the owner, re-entering their password, to an active
  member of staff who isn't leaving. The new owner gets Administrator; the
  old owner keeps it (the new owner can change that). Every transfer is
  recorded in `ownership_transfers` (RLS) with who did it and why, shown on
  Company details.
- **Opsyra support** can reassign from Central (by email + reason) for a
  locked-out company; recorded as "Opsyra support (<super admin>)".
- **Central learns the owner** through the hourly usage pull (name and
  email only) and keeps its customer contact current. That's an exception
  to "no personal data leaves the country", accepted because Central
  already holds that contact from sign-up.
- Locked in the public demo. Ideas from Frappe (Administrator user can't
  lose System Manager) and Odoo (admin user can't be archived).

## 2026-09-29 — Sign-ups must not depend on email

- **Why:** the first real trial sign-ups all showed "Setup failed". The
  US deployment had created each company, then sending the welcome email
  failed (the SMTP account was out of credit), the request returned 500,
  and Central marked the sign-up failed. A retry then got 409 "code
  taken", so it could never recover.
- **Emails that carry a sign-in link are queued** (`SendSignInLink`,
  3 tries, 1 then 5 minutes apart). It carries ids, not models: a queued
  notification restores its User before the tenant context exists and
  RLS would hide it. The token is minted when the email is sent.
  Forgot-password is queued too, which also stops a mail error from
  revealing that an account exists.
- **Provisioning is repeatable:** the same client code with the same admin
  email is treated as success (200, `already_existed`) and re-sends the
  link. A different admin email is still 409.
- **Email health is reported to Central** (`MailHealth`: last send, last
  failure, recorded from `MessageSent` and from reported mailer transport
  exceptions) with the hourly usage pull, and shown as a red dashboard
  alert. It is per country, not per company, because an outage affects
  everyone.

## 2026-09-26 — Attendance stages B–D: visits, payroll link, screens

- **Visits (home care)** are part of the Attendance module: visit locations
  (a client's name, address and map point — deliberately nothing about their
  care), repeating visit plans that generate dated visits two weeks ahead,
  GPS check-in/out per visit (far or no location → flagged, never refused),
  a coordinator review queue. Only verified or accepted visit time counts.
- **Hourly pay** is a pay component type (`per_hour`): its value is a rate,
  paid for the period's hours from a `WorkedHoursProvider` (Payroll's
  contract; Attendance answers). A salary needs basic pay, an hourly rate,
  or both. Attendance deductions for salaried staff are a
  `PayslipContributor`, off until the company switches them on.
- **Maps: Leaflet (BSD-2-Clause, checked in its LICENSE file) with
  OpenStreetMap's public tiles** by default, configurable through
  `VITE_MAP_TILE_URL`. OSM's public tile servers are for light use; a busy
  production deployment should point this at a commercial or self-hosted
  tile provider.
- **No address look-up (geocoding).** Turning an address into coordinates
  would send clients' home addresses to a third party. Coordinators place
  the point by clicking the map, typing coordinates, or "use where I am
  now" at the door. Revisit if a customer wants look-up (with a provider
  under a data-processing agreement).
- **Times set from a local clock are stored through `App\Casts\UtcDateTime`**
  on every `timestamptz` column. Eloquent's `datetime` cast writes the clock
  time without its zone, so 08:00 in Chicago was stored as 08:00 UTC (found
  on the demo; the tests ran in UTC). The cast writes UTC with an explicit
  offset and reads back immutable Carbons — a mutable one was changed by
  `->addHour()` and re-saved through Eloquent's cast cache. Query bindings
  are not converted, so queries compare against `->utc()` moments.

## 2026-09-26 — Attendance module, stage A (new `Attendance` module)

User decisions (see `docs/research/attendance.md`):

- **Attendance now, Leave next.** HR marks leave by hand (paid or unpaid,
  also ahead of time) until the Leave module takes it over.
- **Simple, per-company pay rules.** A day records facts — status (present,
  half day, absent, on leave, remote, day off, holiday) plus late / left
  early / missed check-out and minutes. The company's attendance policy
  judges short days (half-day / absent thresholds) and, at pay-run time,
  what they cost. Replaces SLMS360's 14 status codes, which froze pay
  decisions (LT vs LTD…) into the record at check-in.
- **Clocking is through the staff app** (like SLMS360's), so the backend
  exposes an app API modelled on SLMS360's: `attendance/today`, `clock`,
  `month`, `my-schedule`, `corrections/*`. Location is recorded and checked
  against the branch; a far-away clock-in is **flagged for review, never
  refused** (phone GPS indoors is often 50–200 m out).
- **Fixed weekly schedules** (with night shifts), assigned with dates; the
  company default applies to anyone not assigned. A week-by-week rota
  (ToddlersApp) comes later.
- **Visit-based staff** (home care — Kindstead): no fixed workplace; each
  visit to a client's home is checked in/out with GPS matched to the
  address, planned by a coordinator in Opsyra, paid **by the hour of visit
  time**. Stage B; the calendar already treats `attendance_mode = visits`.

Also decided while building:

- **The database session runs in UTC** (`config/database.php`). The
  attendance tables are the first to use `timestamptz`; the dev server's
  Postgres defaulted to America/Chicago, which silently shifted every
  clock time by 5–6 hours. Any server must behave the same.
- **Days are settled hourly** (`attendance:close-out`), because "the day is
  over" depends on each branch's timezone and on night shifts. Each day is
  settled once (`closed_at`); later edits re-judge it. The close-out never
  invents absences before a person joined or before the company started
  keeping attendance (`attendance_started_on`).
- New permissions: `HRMS-AttendanceManagement` (HR Manager and
  Administrator by default) and `HRMS-VisitPlanning` (Administrator; a
  company adds it to a coordinator role).

## 2026-09-25 — Pricing structure (Central)

User direction: keep "plans coming soon" on the site for now, but put the
structure in place for **feature tiers with per-employee pricing**, fully
configurable by the super admin per country.

- **Plans** (tiers) have a name, description, order, "offered" flag, and a
  set of **features** from a fixed catalogue (`config/opsyra.php`; roadmap
  features are marked "coming soon"). Feature codes are stable because
  they'll be pushed to country deployments for enforcement.
- **Price per plan per country** is three numbers: starting monthly
  price, employees included, and price per extra employee. So monthly =
  `base + max(0, employees − included) × extra`, rounded per currency
  (OMR has three decimals). Three plans × three numbers = the "9 numbers
  per country" the user described.
- **Publishing is per country.** Unpublished (the default) keeps "plans
  coming soon" on the site. A country can't be published until every
  offered plan has a price there.
- **Starter data:** Base / Plus / Premium with the user's example US
  prices ($15 / $30 / $50 for up to 10 employees, then $1 / $1.50 / $2
  each), saved as an unpublished draft. Other countries are empty. Plan
  names and features-per-tier are placeholders for the user to decide.
- **Super-admin area** in Central (`/admin`): Central's own `users` table
  holds BrainStormers staff, with session login and throttling, entirely
  separate from company staff. Create an admin or reset a password with
  `php artisan central:admin`.

## 2026-09-25 — The product starts at the landing website (Central brought forward)

The user reviewed the first app screens and said Opsyra must start the way
their SonariAI product does (https://sonariai.us/, code in
`Software/MAI`, built with Next.js): a landing website with Demo, Log in
and Get started, then the next steps. Decisions:

- **The website is part of Opsyra Central**: Laravel with server-rendered
  Blade pages and Tailwind, in `central/` in this repo, deployed
  separately. Central's billing and super-admin follow later, as agreed.
- **Central hosts the global directory** (client code → country URL),
  where ADR-001 placed it. **Log in** asks for the company code and
  redirects to that country's app sign-in with the code filled in.
- **Get started = free trial, no card.** Central asks the country
  deployment to provision the company over a signed server-to-server
  call. The country deployment emails the admin their temporary password,
  so Central never handles it.
- **Demo = a live sample company, like Horilla's demo**
  (hr.demo.horilla.com): one click into a fully populated Demo Company,
  with the credentials shown on the sign-in page, rebuilt nightly. The
  demo account can't change its password or trigger emails, so visitors
  can't lock each other out.

## 2026-09-25 — Live demo company

- **`demo:reset`** (nightly at 03:00 UTC) deletes the demo company and
  rebuilds it with `DemoCompanyBuilder`, **through Opsyra's own services**
  (onboarding, offboarding, checklists, payroll, approvals), so it behaves
  exactly like a real company. Dates are relative to today; no Faker (it's
  a dev-only dependency), so names and data are fixed and deterministic.
- **Contents:** 3 branches, 20+ staff with a reporting hierarchy,
  departments and job titles, default roles assigned, custom pay
  components, salaries, bank details (3 missing, so paid by cheque), 3
  approved monthly runs plus this month half-approved, bonuses, an open
  salary query, a recent joiner with an overdue checklist task, an approved
  future starter, a hire and a leaver awaiting level-2 approval, a
  scheduled leaver, a past leaver, login history, and setup complete.
- **Wiping a company** relies on every company table cascading from
  `tenants` (checked: all 31 do). Two tables that block deleting rows they
  reference are cleared first.
- **The demo is locked** (`ProtectDemoCompany`) so visitors can't lock the
  next visitor out: no password change, no role edits, and no changes to
  the shared account's own staff record. Everything else is open.
  Forgot-password never emails for the demo, and all demo addresses use
  the reserved `.example` domain.
- **Public sign-in:** `admin@demo-company.example` / `opsyra-demo-2026`,
  shown on the website's Demo page, which links to `/login?demo=1` (filled
  in, one click). Central registers the demo as `is_demo`, so company-code
  login works but it never counts as revenue.

## 2026-09-25 — Frontend stack

- **Vue 3 + TypeScript + Vite** in `frontend/` (same repo), with Vue Router,
  Pinia and axios. It talks to the API with the same Sanctum bearer tokens
  as the future mobile app, which the user confirmed. In dev, Vite proxies
  `/api` to Laravel, so there's no CORS. Production hosting and CORS are
  decided with the test deployment.
- **UI kit: Naive UI (MIT, confirmed from its LICENSE file)**, with
  `@vicons/ionicons5` (MIT). **Not PrimeVue:** since v5 it's under the
  commercial "PrimeUI License" (licence key; free only for organisations
  under 10 employees / $1M revenue / 5 developers, renewed yearly;
  otherwise paid per developer), despite an MIT badge in its README. It
  was installed, spotted and removed. **Check a UI package's LICENSE file,
  not just npm's field or badge.**
- **Brand** taken from ADR-001's page: teal accent `#0E6E62` (dark
  `#5CC4B4`), ink `#16202A`, background `#F6F7F9`; Plus Jakarta Sans for
  headings and Source Sans 3 for body text; light and dark themes.
- **Layout reference: Horilla**, which the user reviewed. Ideas only.
- **The token is kept in `localStorage`** for now, simplest for a
  bearer-token SPA. Revisit with a strict Content-Security-Policy, or
  cookie-based SPA auth, before production.

## 2026-09-25 — Login / auth (new `Auth` module)

- **Fixed a latent bug: real bearer tokens never worked.** Sanctum finds
  the token in `personal_access_tokens` (outside RLS), then loads its user
  from `users` (under RLS) with no tenant context set, so the user is
  invisible and the request fails with 401. Every earlier test used
  `actingAs()`, which skips the token path. Tokens now store `tenant_id`,
  and `TenantAccessToken::findToken()` switches into that tenant before
  the user is loaded. Proven by disabling the fix, which makes the new
  real-token test fail with 401. **Tests of auth must use real tokens.**
- **Login by client code + email + password** (emails are unique per
  company only). The password is checked before any account status is
  revealed; SLMS360 answered "account not active" for any known email.
  Identical messages for unknown company, unknown email and wrong
  password. Throttled per account+IP (5/min) and per IP (30/min).
- **`EnsureAccountIsUsable` runs on every API request.** It refuses
  `pending_approval` / `offboarded` accounts even with a surviving token,
  and holds anyone with `must_change_password` at the change-password
  screen. `pending` (approved, not yet started) is allowed, because new
  starters have onboarding tasks.
- **Password reset** is tenant-scoped: `password_reset_codes` replaces
  Laravel's email-keyed table, stores only the token's hash, is
  single-use, lasts 60 minutes, and signs out every session.
  `forgot-password` never reveals whether an account exists.
- **Login history** ported from SLMS360 (`HRMS-ViewLoginLogs`).
- **`DefaultRoles::seedFor()` no longer re-applies permissions** to roles
  that already exist, so a tenant's edits survive. New catalogue
  permissions reach existing Administrators via `grantToAdministrators()`.
- Not ported: SLMS360's one-device-per-staff binding, which was
  commented out there anyway. It belongs with Attendance's check-in
  anti-fraud.

## 2026-09-25 — Subscriptions: hybrid central + per country (amends ADR-001)

Full reasoning is in `docs/research/subscription-architecture.md`.

- **One central "Opsyra Central"** for the landing site, sign-up, plans,
  payments, invoices and the BrainStormers super-admin. **Each country
  deployment keeps a local enforcement copy** of its tenants'
  plan/subscription/feature flags, pushed from central over a signed
  bridge (SLMS360's pattern). Employee data never leaves its country;
  central receives only aggregate usage.
- **Amends ADR-001**, which put plans and subscriptions in per-country
  tables without saying where they come from. The tables stay, as a copy.
- **New app, not SLMS360's `main_front`**, porting its proven parts.
  main_front is the whole school app with per-student pricing and one
  installation per client.
- **Built after login and the frontend**, alongside the test deployment.

## 2026-09-25 — Company provisioning, permission catalogue, default roles

Needed before the first real company: nothing created permissions outside
the tests, and nothing gave a new company's first admin access to the
Setup wizard.

- **One permission catalogue** (`PermissionCatalogue`), with SLMS360's names
  and a plain description each, installed by migration and
  `permissions:sync`. A test scans `app/` and fails if any checked
  permission is missing from it.
- **Five default roles per tenant**: Administrator (everything), HR
  Manager, Payroll Officer, Approver (L1) and Senior approver (L2).
  Tenants can edit them.
- **`TenantProvisioner`** creates the tenant (whose `created` event seeds
  every module's defaults), the first Administrator, the company-name
  setting and the directory entry, in one transaction.
- **A deployment only hosts its own country's companies**
  (`APP_COUNTRY` → `config('app.country')`), matching the per-country
  deployment design.
- **Operator command for now** (`tenants:create`). A super-admin panel or
  self sign-up would call the same service. The temporary password is
  printed once and never stored in plain text.
- Test impact: permissions now exist from migration onwards, so tests use
  `Permission::findOrCreate`, and test roles no longer use default role
  names.

## 2026-09-25 — Phase 3, module 6: Setup wizard (new `Setup` module)

Moved up by the user, so that a company can be walked through configuring
everything at initial setup. Research is in `docs/research/setup-wizard.md`.

- **Completion is computed live from the data**, never stored (the
  SLMS360 and Horilla approach), so it can't drift however the data was
  entered. Only the admin's choices are stored: skipped, or reviewed.
- **Each module registers its own steps** (`SetupStepProvider`, container
  tag `setup.step_providers`). Company steps live in Setup, HR steps in Hr,
  and payroll steps in Payroll. A new module with per-company settings
  must add steps, which is now an explicit rule in `docs/status.md`.
- **"Review" steps** for anything pre-filled from country defaults
  (leaving reasons, checklist templates, pay components). Those are never
  empty, so "done" means an admin has confirmed them. Data steps can't be
  ticked off by hand.
- Skipping moves the wizard on but never counts as complete for a
  required step.
- **New permission `HRMS-CompanySettings`**; SLMS360 guarded its
  organisation settings with nothing. Each step's own screens keep their
  own permissions.
- **Company profile** in a per-tenant key/value `tenant_settings` table.
  The currency setting now drives new salaries' default currency. The
  timezone is stored but not yet used by the nightly jobs.
- **Branch management built** (was an open gap), with address, city,
  phone and a per-branch timezone.
- Dropped from SLMS360's wizard: the classes and students steps (school
  only).

## 2026-09-25 — Phase 3, module 5: Salaries hub (new `Payroll` module)

Research and the user's three choices are in `docs/research/salaries.md`;
the port mapping is in `docs/provenance.md`. Highlights:

- **Configurable pay components** (Frappe/Horilla model) replace SLMS360's
  fixed columns. They're seeded per country, and Pakistan gets SLMS360's
  set. A component's type and calculation are fixed once created. The
  single Basic component is seeded and can't be deactivated.
- **Salaries are effective-dated.** The one in force at a period's end is
  used for the whole period; a mid-period raise is not split. Revisit if
  customers need it (Frappe does split).
- **Monthly and biweekly.** Periods are start/end dates; salary amounts are
  per period of the salary's own frequency; overlapping runs of the same
  frequency are refused.
- **Proration by calendar days employed** in the period, applied to fixed
  amounts on components flagged `prorate`, not by working days. SLMS360 did
  the same via attendance.
- **Attendance plugs in later** through the `PayslipContributor` interface
  (container tag `payroll.payslip_contributors`), and adds its own payslip
  lines.
- **Tax % is worked out on the salary's gross before adjustments**, as in
  SLMS360. Country tax engines (US federal, UK PAYE, Pakistan slabs) are
  future work.
- **Approvals:** L1 then L2; an L2 holder may approve a draft outright; no
  downgrades; the same person can't act at both levels. Payment method
  (transfer/cheque) is set at final approval.
- **Approved pay is never rewritten.** Only drafts regenerate, and salary
  claims are settled by an adjustment on a later payslip. SLMS360
  regenerated approved salaries in place.
- **Money is decimal(15,3)**, rounded per currency (OMR has 3 decimals).
- Not ported: hourly wage basis (needs hours from Attendance), the
  "salary generated" email (no mail templates yet), US federal tax,
  garnishments and employer taxes (the US payroll module).

## 2026-09-24 — Phase 3, module 4: onboarding/offboarding checklists

New build: SLMS360 has no equivalent. Design taken from the
pattern Frappe HRMS, Horilla and Odoo share (ideas only, no code; see
`docs/research/onboarding-offboarding.md`):

- **Checklists start on approval, not submission.** A rejected hire never
  hands tasks to future colleagues. Frappe's "tasks that block creating the
  employee" was not adopted: the L2 gate is already the blocker, and
  keeping the checklist separate means approval never fails for lack of a
  template.
- **Assignees are resolved once, when the checklist is created** (Odoo's
  manager/employee idea, plus Frappe's role pools). A later change of
  manager doesn't move tasks already handed out. Tasks that resolve to
  nobody (no manager, or a named user who has left) stay visible to HR,
  who can complete any task.
- **Tasks are copied from the template**, so editing or deleting a template
  never changes a checklist already under way. That's why deleting a
  template is always allowed.
- **Status is derived:** complete when every task is done or skipped,
  reopened if a task goes back to todo. Required tasks can't be skipped.
  Progress is shown as counts (`tasks_count`, `closed_tasks_count`,
  `overdue_tasks_count`), not a stored percentage.
- One default template per type per tenant, enforced by a Postgres partial
  unique index as well as in code.
- Deferred: skipping holidays when working out due dates (needs the
  Attendance Hub's holiday data), email/notification of new tasks (no mail
  templates yet), and Horilla's self-service portal for new hires.
- For an exit that applies immediately, the leaver's own tasks (e.g.
  "hand over work") land on someone who has just lost API access. HR
  completes those, which is acceptable but worth revisiting when the
  frontend exists.

## 2026-09-24 — Phase 3, module 3: onboarding/offboarding approvals

Full detail in `docs/provenance.md`. Highlights:

- **Scope chosen with the user after comparing Frappe HRMS, Horilla and
  Odoo** (`docs/research/onboarding-offboarding.md`): port SLMS360's L2
  gate and direct-report reassignment (neither of the others does these
  well), add configurable departure reasons (Odoo's idea), and **defer the
  onboarding/offboarding checklist** — templates, per-person tasks, derived
  status, which all three mature systems have — to its own module next.
- `ApprovalHistory` moved out of this module to the Attendance Hub (only
  leave approvals use it in SLMS360).
- Rehiring an offboarded person now goes through the L2 gate too — closing
  a bypass where a rejected candidate could be reactivated without approval.
- Offboarding/cancelling now require a permission (SLMS360 required none).
- No permission seeder exists yet: `HRMS-Approval-L2`, like every other
  permission so far, is only created in tests. Needs one before first real
  tenant (likely with the setup wizard).
- **UI direction** (not yet decided — no frontend stack chosen): the user
  reviewed Horilla's demo and found it acceptable as a reference for layout
  (stage pipelines, approval queues, per-person task lists).

## 2026-09-24 — External design references (open-source HR/ERP/CRM)

- Added third-party open-source projects as **design references only** —
  data models, workflow states and feature coverage. **No code is copied
  or translated from them**, because most are copyleft and Opsyra is
  proprietary SaaS. Licences, checked from each repo's own licence file:
  Frappe HRMS GPL-3.0, Horilla LGPL-2.1, Odoo Community LGPL-3.0 (Odoo
  Enterprise is proprietary and not referenced at all), OroCRM OSL-3.0
  (copyleft that covers network deployment — the strictest of the set),
  Krayin MIT (the only permissive one; code reuse would be allowed with
  attribution, but still prefer re-deriving).
- Clones live in `_reference/` next to SLMS360/ToddlersApp: shallow, sparse
  (only the relevant directories), gitignored. Unlike the two source repos,
  `origin` is **kept** with its push URL set to `DISABLED` — sparse partial
  clones fetch file contents lazily from origin, so removing it breaks
  them. We have no write access to these upstreams anyway.
- Findings per module go in `docs/research/<module>.md`. First one:
  `docs/research/onboarding-offboarding.md`.

## 2026-09-25 — Phase 3, module 2: HR core (staff, departments, teams)

Full detail in `docs/provenance.md`. Highlights:

- **Dropped SLMS360's `role` string column** (Client/Staff/Parent/Super
  Admin) entirely — it existed to mark "this row is the tenant owner," which
  Opsyra's real `tenants` platform table already makes unnecessary. Every row
  in a tenant's `users` table is now unambiguously that tenant's staff.
- **Deliberately excluded onboarding/offboarding approval workflow** (the L2
  gate) from this module even though it lived in the same 1098-line
  `StaffController` in SLMS360 — `create()` here always activates a staff
  record directly, no approval gate; that's the next module's job, layered on
  top rather than half-built here.
- Consolidated ~15 separate SLMS360 `alter_users_*` migrations (accumulated
  2024–2026) into one migration scoped to this module's actual needs.
  `cnic` generalized to `national_id` (four-country product, not
  Pakistan-only).
- Closed real IDOR gaps the Phase 1 inventory flagged: `activeDirectReports`,
  `updateAvatar`, `clearDeviceId` had **zero tenant scoping** in SLMS360
  (harmless there — single tenant per deployment). Fixed via `BelongsToTenant`
  + RLS (structural) plus an explicit `manage-staff-member` Gate ability
  (permission + ownership check, matching RolePolicy's pattern) for defense
  in depth.
- Added permission gates to every mutating staff/department/designation
  action — SLMS360 had none for plain CRUD (only the L2/permission-update
  sub-actions were gated, and inconsistently: `create()` never checked
  `HRMS-StaffPermissionUpdate`, only `update()` did). Read/list actions stay
  ungated, matching SLMS360's actual (reasonable) behavior.

## 2026-09-25 — Phase 3, module 1: RBAC

Full detail in `docs/provenance.md`. Highlights:

- Used spatie/laravel-permission's built-in **"teams" feature** for
  multi-tenancy rather than hand-rolling role/permission tenant scoping —
  `team_foreign_key` set to `tenant_id`. A custom `TenantPermissionsTeamResolver`
  reads the team id from our existing `TenantContext` (not the package's own
  static resolver), so tenant identity has exactly one source of truth across
  the whole app, RBAC included.
- `roles` / `model_has_roles` / `model_has_permissions` are tenant-scoped
  (RLS + `BelongsToTenant` via a thin `Role` subclass). `permissions` /
  `role_has_permissions` are left global — permissions are the app's fixed
  capability list, not tenant data, same treatment as the Platform tables.
- Replaced SLMS360's inline `Auth::user()->can(...)` checks with a `RolePolicy`
  that checks permission **and** tenant ownership — the exact issue class
  (permission-only checks, no ownership check) the task says the v2.5.0
  SLMS360 review found. This is the pattern every future module's
  authorization should follow.
- `guard_name` is `sanctum` (this app's real auth guard), not SLMS360's `api`
  (Passport) — required adding an explicit `sanctum` guard + making it the
  default guard in `config/auth.php` so spatie's guard auto-detection and
  `Auth::user()`/`$request->user()` all agree on which guard resolved a
  request.
- Learned while testing: `assertDatabaseHas`/raw DB assertions made *after* an
  HTTP test request completes run with no tenant context (`ResolveTenantFromUser
  ::terminate()` correctly clears it once the response is sent) — RLS then
  shows zero rows, not the row you just created. Not a bug; any post-request
  assertion against a tenant table needs to re-open tenant context itself
  (`Tenant::runAs(...)`), same as any other out-of-request code would.

## 2026-09-25 — Phase 2: scaffold and tenancy core

**Local dev: native Postgres 17 + Memurai, not Docker.** Docker Desktop isn't
installed on this machine (checked PATH, Program Files, services, WSL — none
present). Rather than block on an install, we switched to native Windows
installs: PostgreSQL 17 (winget) and Memurai Developer (winget) — Memurai is a
maintained, Redis-protocol-compatible server for Windows; the official
`Redis.Redis` winget package is an abandoned 2016 port (v3.0.504) and wasn't
used. `docker-compose.yml` and `docker/` are still written (per the task's own
requirement) but are **unverified** — built for later use, not exercised here.
Local Postgres role `opsyra` (password `opsyra_local_dev`) owns four
databases: `opsyra_us`, `opsyra_uk`, `opsyra_om`, `opsyra_pk`, plus
`opsyra_us_test` for the test suite (`phpunit.xml` points at Postgres, not
sqlite, since RLS — the thing the cross-tenant harness verifies — doesn't
exist on sqlite).

**Laravel 12, not 11.** Laravel 11.x has two advisories with no 11.x patch
(only fixed in 12.60+/13.x): a high-severity CRLF injection in the default
`email` validation rule, and a medium-severity signed-URL path-confusion bug.
Confirmed with the user per the task's own "same major version as SLMS360
unless there's a strong reason not to — tell me first" rule; moved to
Laravel 12 (`composer audit` reports clean). SLMS360's Laravel 11 code still
ports over with only minor compatibility changes expected during Phase 3.

**This machine's environment needed two local fixes**, unrelated to the app
itself but worth recording: (1) AVG Antivirus does TLS interception ("AVG
Web/Mail Shield") on this machine, which broke Composer/PHP-curl (OpenSSL
does its own cert validation, unlike the system `curl.exe`/schannel) until
AVG's root cert was appended to `cacert.pem`; (2) Composer 2.10's advisory
resolver blocks installing any package version affected by a known advisory
by default — bypassed for the initial Laravel 11 scaffold attempt via
`--no-blocking` before we decided to move to 12 anyway, where it wasn't
needed.

**Tenant resolution is derived from the authenticated user, not a
client-supplied header/code.** ADR-001 and the task both described porting
ToddlersApp's resolution mechanism, but the Phase 1 inventory found there
isn't one to port (see docs/inventory.md §2) — so this was designed new.
`ResolveTenantFromUser` middleware (`app/Modules/Tenancy/Http/Middleware`)
reads `$request->user()->tenant_id` after Sanctum auth and sets Postgres's
`app.tenant_id` for the request; a client can't spoof its way into another
tenant by sending a different code once authenticated, closing the exact bug
class the v2.5.0 SLMS360 review found. The one place a client-supplied
`client_code` is trusted is the Directory module's `/api/resolve` endpoint
and each module's own future login controller, because there's no
authenticated user yet at that point to derive a tenant from.

**RLS gotcha found by the isolation test itself, fixed**: Postgres `RESET` on
a custom (namespaced) GUC like `app.tenant_id` reverts it to an empty string,
not `NULL`, once it's been `SET` at least once in that session — so
`current_setting('app.tenant_id', true)::bigint` throws after a request ends
and `TenantContext::clear()` runs, instead of just returning `NULL`. Fixed
with `NULLIF(current_setting('app.tenant_id', true), '')::bigint` in every
RLS policy (see `App\Modules\Tenancy\Support\TenantRls`). Caught by
`test_row_level_security_fails_closed_when_no_tenant_context_is_set` —
exactly the kind of thing the harness is for.

**Module folder convention**: `app/Modules/<Name>/{Models,Http/Controllers,
database/migrations,routes/api.php}`. `App\Modules\<Name>\...` needs no extra
composer.json entry (resolves under the existing `"App\\": "app/"` PSR-4
mapping). `App\Providers\ModulesServiceProvider` auto-loads every module's
migrations and routes (routes get the `api` middleware group + `/api` prefix)
— dropping a new module in is enough, no manual registration per module.

**Platform tables** (`tenants`, `plans`, `plan_features`, `subscriptions`,
`tax_tables`, `super_admins`) live in a `Platform` module and deliberately do
**not** use `BelongsToTenant`/RLS — they're the tenant registry, not tenant
data. `super_admins` gets its own auth guard (`config/auth.php`), separate
from every tenant user guard, so a compromised tenant user can never
authenticate as platform staff.

**Cross-tenant isolation harness** (`tests/Support/{SeedsTwoTenants,
AssertsTenantIsolation}.php`) checks three independent layers so a failure
says which one broke: the `BelongsToTenant` app-layer scope, raw SQL under
RLS (proves the DB layer alone is sufficient), and that the record is still
visible to its own tenant (so a pass isn't just "nothing is ever visible").
`tests/Feature/Tenancy/CrossTenantIsolationTest.php` exercises it against
`users` (the only tenant-scoped table that exists yet) plus a real Sanctum
HTTP request. Every Phase 3 module adds its own
`assertModelIsTenantIsolated()` case rather than inventing a new check.

## 2026-09-24 — Checkpoint 1 decisions

- **Task/Project Management (SLMS360)**: **Port**, not exclude. Already rebuilt at
  v2.7.1 with tenant/branch/permission scoping, closing the IDOR gaps that caused
  its original removal — treated as reusable admin scope.
- **Staff ID card**: **build new** in Phase 3, no reference pull-in needed. Neither
  backend has staff-card logic to adapt.
- **Double-entry ledger**: **build a real one** (chart of accounts, journal entries,
  AR/AP) in a later phase. ToddlersApp's wallet-transaction pattern is not reused
  for tenant bookkeeping — it may still inform the separate *platform* billing
  concern (Brainstormer billing the tenant), not tenant accounting.

## 2026-09-24 — Phase 1 inventory findings

Full detail in `docs/inventory.md`. Three findings corrected assumptions from the
original scope/ADR-001 and are pending a decision at Checkpoint 1 (see
`docs/inventory.md` §7): SLMS360's Task/Project Management module is present and
already security-rebuilt at v2.7.1 (contradicts "removed, exclude"); no Staff ID
Card implementation exists in either backend; ToddlersApp's "double-entry ledger"
is actually a single-entry wallet balance, not double-entry bookkeeping.

Also corrected: ADR-001 assumed ToddlersApp already has a portable tenant-resolution
mechanism (global scope, resolver middleware, client-code directory) to port as-is.
It doesn't — isolation there is manual/inline per-controller on a `client_id` column,
with no scope, no middleware, and no client-code directory at all. Nothing changes
about the Phase 2 plan (the architecture section already scoped the resolver,
`BelongsToTenant` trait, and RLS as things to *build*), but "port the tenant
resolution layer from ToddlersApp" should be read as "reuse the `client_id` →
`tenant_id` naming lesson," not "copy an existing mechanism."

## 2026-09-24 — Bootstrap

- **SLMS360 source**: tag `v2.7.1` from the local clone at
  `Software/SLMS360` (origin `brainstormertech/slms360`, commit `fa18b472`).
  This tag postdates and supersedes the "v2.5.0" module set referenced in
  ADR-001 — it includes a rebuild of Task/Project Management with tenant,
  branch and permission scoping (Task/Project itself stays **excluded** per
  scope; the rebuild is only relevant as the source of the current security
  baseline).
- **ToddlersApp source**: `master` branch from the local clone at
  `Software/toddlersapp` (origin `brainstormertech/toddlersapp`, commit
  `a22725a9`). The prompt referenced a branch "Main", which doesn't exist;
  confirmed with the user that `master` (the default branch) is correct.
  The working copy on disk also has a checked-out branch `Toddler-Kashif`
  with uncommitted local edits to student-fee code — irrelevant here since
  clone only pulls committed history, and `master` was the chosen ref anyway.
- **SLMS360 mobile app** (`slms360_management_app` / `SLMSAPP-Staff`, Flutter
  staff/attendance app): found locally but **not** added as a reference
  input for Phase 1. Confirmed with the user — HR/attendance/ID-card scope
  is inventoried as backend (API) only for now; mobile port deferred.
- Reference clones live in `_reference/slms360` and `_reference/toddlersapp`,
  cloned from the local repos above with `origin` removed. Gitignored.
