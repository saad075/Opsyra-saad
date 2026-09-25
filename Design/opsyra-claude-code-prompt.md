# Claude Code prompt: bootstrap Opsyra

> \*\*How to use:\*\* Fill in the four `<<...>>` placeholders below. Then open Claude Code in `C:\\Personal Doc\\Business\\BrainStormers\\Software\\` (the parent folder, because `Opsyra` doesn't exist yet) and paste everything from "PROMPT START" down.

\---

PROMPT START

You are bootstrapping **Opsyra**, a new multi-tenant SaaS for small businesses (HRMS, admin and finance now, CRM later). It is built by **copying and repurposing** code from two existing products, **SLMS360** and **ToddlersApp**. Reuse existing code wherever it fits. Only write new code where nothing reusable exists.

## Inputs

* SLMS360 source repo: `<<PATH OR GIT URL OF SLMS360>>`
* SLMS360 branch or commit to copy from (must include the v2.5.0 features shipped September 2026): `<<BRANCH, e.g. brainstormertech>>`
* ToddlersApp source repo(s) (backend and, if separate, the Flutter app): `<<PATH(S) OR GIT URL(S) OF TODDLERSAPP>>`
* ToddlersApp branch or commit: `<<BRANCH>>`
* New project location: `C:\\Personal Doc\\Business\\BrainStormers\\Software\\Opsyra`

## Hard rules (non-negotiable)

1. **Do not modify SLMS360 or ToddlersApp in any way.** Never write to, commit to, checkout, stash, reset, pull or push in the original repositories or their working folders. Never run migrations, seeders or scripts against their databases or `.env` settings.
2. Get the source code by cloning **fresh read-only reference copies** into `Opsyra\\\_reference\\slms360` and `Opsyra\\\_reference\\toddlersapp` (e.g. `git clone --branch <branch> <source> \_reference\\slms360`). Then run `git remote remove origin` inside each clone so nothing can ever be pushed back. Add `\_reference/` to Opsyra's `.gitignore`.
3. Only ever copy **from** `\_reference` **into** Opsyra. Never the other way.
4. Do not push Opsyra to any remote unless I explicitly ask.
5. Quote paths. The project path contains spaces (`Personal Doc`).
6. **Stop and wait for my review at each ✋ CHECKPOINT.**

## Architecture (already decided, don't redesign)

* **One codebase, one deployment per country:** `us`, `uk`, `om`, `pk`. Country is configuration (`APP\_COUNTRY`), not a fork.
* **Database: PostgreSQL.** One shared database per country. All clients (tenants) in that country share it and are isolated by `tenant\_id`.
* **Isolation has two layers:**

  * App layer: a `BelongsToTenant` trait with a global scope that filters every query and auto-sets `tenant\_id` on create. Opting out must be explicit and grep-able (e.g. `withoutTenantScope()`).
  * DB layer: Postgres **row-level security** on every tenant table, using `current\_setting('app.tenant\_id')`. Middleware sets this per request (`SET LOCAL` inside a transaction, or reset at the end of the request). Queued jobs set it too.
* **Tenant resolution:** port the mechanism **ToddlersApp** already uses (single endpoint, client resolved per request). The app flow stays: app → client code → endpoint. A small **global directory** maps `client code → country endpoint` and stores no personal data.
* **Per-country platform tables** (not tenant-scoped): `tenants`, `subscriptions`, `plans`, `plan\_features`, `tax\_tables`, `super\_admins`.
* **Escape hatch:** the tenant resolver returns a DB connection name, so a large tenant can later be moved to a dedicated database without code changes.
* Framework: same Laravel major version as SLMS360 unless there's a strong reason not to (tell me first). Use Docker Compose for local dev (app, Postgres, Redis).

## Scope

**Port from SLMS360 (base: its HRMS plus all v2.5.0 work):**
HR staff management, departments and designations, teams, staff onboarding and offboarding approvals (including the L2 gate), salaries hub, attendance hub, HR dashboard, management insights dashboard, RBAC (roles alongside direct permissions), staff ID card, setup wizard, subscription enforcement (rework it to read the central `subscriptions` table), the Excel import/export engine (maatwebsite/excel 3.x), and the US federal payroll module with its tax tables (US deployment only, behind a feature flag).

**Port from ToddlersApp:**
All non-educational admin modules, in particular accounts and fees, billing, vouchers and double-entry ledger, plus its tenant-resolution layer. Where ToddlersApp and SLMS360 both have a version of the same module, compare them in the inventory and recommend the better one. Don't merge them silently.

**Exclude (do not port):**

* Anything educational: students, parents, classes and sections, subjects, homework and diary, timetable and auto-generator, houses, exams, report cards, academic sessions.
* The six modules removed from SLMS360 in v2.5.0 for security reasons: Staff Performance, Staff Evaluation, Leads/CRM, Project \& Task Management, Revenue Reporting, Job Portal. CRM will be designed fresh later.

## Phases

### Phase 1: Set up the reference copies and the inventory

1. Create the `Opsyra` folder, run `git init`, and add `.gitignore` and a README stub.
2. Clone the reference copies (see Hard rules 2).
3. Read both codebases. Write `docs/inventory.md` with:

   * Stack of each (framework and versions, DB, auth, frontend/Flutter structure).
   * **How ToddlersApp isolates tenants** (column name, scoping mechanism, resolver, gaps you spot).
   * A table of every module: source repo, paths (models, migrations, controllers, routes, views/Flutter screens, jobs, tests), dependencies on excluded (educational) code, decision (Port / Port with changes / Exclude / Duplicate: pick one), effort (S/M/L).
   * MySQL-specific code that must change for Postgres (raw SQL, `GROUP BY` looseness, `ENUM` columns, `JSON` functions, case-insensitive collation assumptions, `ON DUPLICATE KEY`).
   * Any direct cross-module coupling to educational models that needs cutting.

✋ **CHECKPOINT 1:** stop and show me the inventory summary.

### Phase 2: Scaffold and tenancy core

1. Scaffold the Laravel app with Docker Compose (Postgres, Redis), `APP\_COUNTRY` config, and a module folder structure (`app/Modules/<Module>/...`).
2. Build the platform tables and the tenancy core: tenant resolver (ported from ToddlersApp), middleware, `BelongsToTenant` trait and global scope, RLS helper migration, tenant-aware queue jobs, tenant-prefixed cache keys and storage paths.
3. Build the global directory endpoint (`POST /api/resolve {code}` → country endpoint URL).
4. Add a **cross-tenant isolation test harness**: seed tenants A and B, act as A, and assert that every registered API route can't read or change B's data. Every future module must pass it.

✋ **CHECKPOINT 2:** stop and show me the tenancy core and passing tests.

### Phase 3: Port modules one at a time (order from the inventory: RBAC and HR core first)

For each module:

1. Copy the code from `\_reference` (don't retype it by hand). Keep its structure where it's sound.
2. Add `tenant\_id` to every table, including pivot, log, attachment and settings tables. Composite indexes lead with `tenant\_id`. Unique constraints include `tenant\_id`.
3. Enable RLS on the new tables.
4. Validate every submitted foreign ID within the tenant (e.g. `Rule::exists(...)->where('tenant\_id', ...)`).
5. Convert MySQL-specific code to Postgres.
6. Fix the v2.5.0 security review's issue class while porting: every action checks role/permission **and** ownership/branch, not just authentication.
7. Pass the module's own tests and the cross-tenant harness.
8. Record provenance in `docs/provenance.md` (module → source repo → commit SHA → original paths → what changed).
9. Commit with a message like `feat(hr): port staff management from SLMS360@<sha>`.

Report after each module. Don't start the next one until the current one passes.

### Phase 4: Country configuration

Feature flags per plan and per country (e.g. US payroll only in `us`), Docker/env templates for the four deployments, and a short `docs/deploy.md`.

## Working style

* When you're unsure whether something is educational or admin, ask. Don't guess.
* Prefer copying and adapting over rewriting. Explain when you choose to rewrite instead.
* Keep `docs/decisions.md` as a running log of choices made during the port.

PROMPT END

