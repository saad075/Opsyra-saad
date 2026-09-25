# Decisions log

Running log of choices made during the Opsyra port. Newest entries at the top.

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
