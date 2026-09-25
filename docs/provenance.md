# Provenance

Module → source → what changed, for every piece of Opsyra ported (not
new-built) from SLMS360 or ToddlersApp. See `docs/inventory.md` for the
original port-decision table and `docs/decisions.md` for the reasoning behind
each change.

---

## RBAC

- **Source**: SLMS360 @ `fa18b472de1a8b7153191bff42e1536918b4ebda` (tag
  `v2.7.1`). `RoleController` last touched at `1bd800bb85e0c582a80695e9633b5e972049921a`
  (2026-09-11).
- **Original paths**:
  `app/Http/Controllers/Client/RoleController.php`,
  `routes/client/BasicInformationRoutes.php` (roles route group),
  `config/permission.php` (spatie/laravel-permission, `teams` disabled).
- **New paths**:
  `app/Modules/Rbac/Http/Controllers/RoleController.php`,
  `app/Modules/Rbac/routes/api.php`,
  `app/Modules/Rbac/Models/Role.php`,
  `app/Modules/Rbac/Policies/RolePolicy.php`,
  `app/Modules/Rbac/Support/TenantPermissionsTeamResolver.php`,
  `app/Modules/Rbac/RbacServiceProvider.php`,
  `app/Modules/Rbac/database/migrations/2026_09_25_040000_create_permission_tables.php`
  (published from the spatie/laravel-permission package itself, not from
  SLMS360 — SLMS360 never published/modified the package's own migration).
- **What changed**:
  - **Multi-tenancy via spatie's own "teams" feature**, not a hand-rolled
    scope: `config/permission.php` sets `'teams' => true` and
    `team_foreign_key = 'tenant_id'` (SLMS360 had `teams` disabled — it's
    single-tenant per deployment, so had no need for it). A custom
    `TenantPermissionsTeamResolver` reads the team id from our own
    `TenantContext` instead of the package's default static resolver, so
    there's exactly one source of truth for "which tenant" rather than two
    systems that could drift out of sync.
  - `roles`, `model_has_roles`, `model_has_permissions` get RLS enabled
    (`TenantRls::enable()`) in the migration; `roles.tenant_id` is a real
    `NOT NULL` foreign key to `tenants`, not the package's default nullable,
    unconstrained column. `permissions` and `role_has_permissions` are left
    alone — permissions are the app's fixed, global capability list (like a
    Platform table), not tenant data.
  - A thin `App\Modules\Rbac\Models\Role` subclasses the package's `Role` and
    adds `BelongsToTenant`, so roles get the same app-layer scope every other
    tenant-scoped model gets, consistent with the two-layer isolation design
    — RLS alone would already be sufficient, this is for consistency/grep-ability.
  - **Authorization moved from inline `Auth::user()->can(...)` checks (one
    per controller action) to `RolePolicy`**, registered explicitly in
    `RbacServiceProvider` (auto-discovery doesn't find it — the model isn't
    under `App\Models`). Same permission name (`HRMS-RoleManagement`) as the
    original, but `update`/`delete` now also check `$role->tenant_id ===
    $user->tenant_id` — the issue class the v2.5.0 SLMS360 review found
    (permission-only checks, no ownership check). Belt-and-suspenders: RLS
    and `BelongsToTenant` already make loading another tenant's `$role`
    impossible, but the Policy is where this project wants that check
    explicit, not just implied elsewhere.
  - **Validation is tenant-scoped explicitly**: `Rule::unique('roles')
    ->where('tenant_id', ...)` and `Rule::exists('roles', 'id')
    ->where('tenant_id', ...)`, replacing SLMS360's global `unique:roles,name`
    / `exists:roles,id` (fine there — one tenant per deployment; would have
    let tenant A guess/collide with tenant B's role names and IDs here).
  - `guard_name` is `'sanctum'` (Opsyra's actual auth guard), not SLMS360's
    `'api'` (Passport). `config/auth.php` gained an explicit `sanctum` guard
    entry and default guard, both needed for spatie's guard auto-detection
    and `$request->user()`/`Auth::user()` to agree on which guard resolved
    the request.
  - Permissions are synced/validated **by name**, not ID — self-documenting
    wire format; no tenant check applies to a permission ID since permissions
    aren't tenant data.
  - `$request->user()` instead of the `Auth` facade, consistent with the rest
    of Opsyra.
  - No MySQL-specific SQL in the original controller — nothing to convert.
- **Tests**: `tests/Feature/Rbac/{RoleControllerTest,RoleIsolationTest}.php`
  — permission enforcement, tenant-scoped uniqueness, cross-tenant isolation
  (via the harness from `tests/Support/AssertsTenantIsolation.php`), and the
  "still assigned to staff" delete guard. 23/23 tests passing project-wide
  after this module (see `docs/decisions.md`).
