# Provenance

Module → source → what changed, for every piece of Opsyra ported (not
new-built) from SLMS360 or ToddlersApp. See `docs/inventory.md` for the
original port-decision table and `docs/decisions.md` for the reasoning behind
each change.

---

## HR core (staff, departments/designations, teams)

- **Source**: SLMS360 @ `fa18b472de1a8b7153191bff42e1536918b4ebda` (tag
  `v2.7.1`). `StaffController` last touched at `48bec2bcd4e174acaf215496bf7da2899fcb1048`
  (2026-09-19), `DepartmentController` at `556cddc58f2e32526ae0cd05b8fd420bd749d86d`
  (2026-09-11), `HR/TeamController` at `97ef27ac5f9f417b94e0167b256e2b9ecb0f0ae6`
  (2026-09-06), `User` model at `5b78a0cd3b6e8d89041b3f85640b31f806f9b4c9`
  (2026-09-18).
- **Original paths**:
  `app/Http/Controllers/Client/StaffController.php` (1098 lines — plain CRUD
  subset only, see below), `app/Http/Controllers/Client/DepartmentController.php`,
  `app/Http/Controllers/Client/HR/TeamController.php`,
  `app/Models/{User,Department,Designation,Branch,UserBranch}.php`,
  `database/migrations/{2024_05_09_054714_create_branches_table,
  2024_05_09_060713_create_user_branches_table,
  2026_09_12_000003_create_departments_table,
  2026_09_12_000004_create_designations_table}.php`, plus ~15 separate
  `alter_users_*` migrations (2024–2026) adding the staff-related columns one
  at a time, `app/Helpers/Helper.php` (`getMySelectedBranchesId()`).
- **New paths**:
  `app/Modules/Hr/Http/Controllers/{StaffController,DepartmentController,TeamController}.php`,
  `app/Modules/Hr/Models/{Branch,UserBranch,Department,Designation,UserUpdateLog}.php`,
  `app/Modules/Hr/HrServiceProvider.php`,
  `app/Modules/Hr/routes/api.php`,
  `app/Modules/Hr/database/migrations/*` (6 files — see below),
  additions to `app/Models/User.php`.
- **Module boundary — what's excluded from this port**: everything in
  SLMS360's `StaffController` touching `StaffOnboardingRequest`,
  `StaffOffboardRequest`, `ApprovalHistory`, the `HRMS-Approval-L2` gate, or
  the attendance backfill on approval — that's a separate module (Onboarding/
  Offboarding approvals) per `docs/inventory.md`, not yet ported. Concretely:
  `create()` here always activates a staff record directly (or leaves it
  `pending` if `joined_at` is in the future) with **no approval gate at all**
  — the next module layers that on top rather than this one half-building it.
  Also deferred to the module that owns them: `UserSalary` creation (Salaries
  Hub), the account-creation email (no email templates ported), the
  `attendance_shift_id`/`annual_leaves_allowed` fields (Attendance Hub),
  `country_id` (no Country model ported). Full branch management (create/
  edit/delete branches, `BranchController.php`, 226 lines) is also not
  ported — this module only creates the `branches` table as a dependency
  staff records validate against; branch CRUD itself is a separate concern.
- **What changed**:
  - **`role` string column (Client/Staff/Parent/Super Admin) is dropped
    entirely, not ported.** SLMS360 needed it because the tenant *was* a
    `User` row (`role='Client'`); Opsyra has a real `tenants` platform table,
    so every row in a tenant's `users` table already means "this tenant's
    staff" — no discriminator needed. Every `where('role', 'Staff')` filter
    in the original controllers is simply absent here.
  - **~15 separate `alter_users_*` migrations consolidated into one**
    (`2026_09_25_050004_add_staff_columns_to_users_table.php`), scoped to
    exactly the columns this module's ported endpoints use. `cnic` (Pakistan
    national ID) generalized to `national_id` — this product targets four
    countries, not just Pakistan. `username`, colour/social/stripe/
    subscription/payroll-classification columns not carried over (unrelated
    concerns / other future modules).
  - `branches`, `user_branches`, `departments`, `designations` all get
    `tenant_id` + RLS instead of SLMS360's `user_id`-as-tenant-key (branches)
    or plain `client_id` column with no DB-level enforcement. `user_branches`
    gains real FK constraints and a `unique(tenant_id, user_id, branch_id)` —
    SLMS360's version had neither, so duplicate branch assignments were
    possible there.
  - New `user_update_logs` table (tenant_id + RLS) for the permission/branch
    change audit trail `StaffController::update()` writes to — SLMS360 wrote
    to a plain, tenant-less table via `DB::table(...)->insert()`.
  - **Every mutating action now checks a permission — SLMS360 checked none**
    for plain staff/department/designation CRUD (only the deferred L2/
    permission-update sub-actions were gated). Added `HRMS-StaffManagement`
    (via the `manage-hr` Gate ability) for create/update/delete of staff,
    departments, and designations; kept SLMS360's existing
    `HRMS-StaffPermissionUpdate` (now `update-staff-permissions`) for the
    permission/role-sync sub-actions within staff create/update, applied
    consistently in *both* now (SLMS360 only gated it in `update()`, not
    `create()`). Read/list actions stay open to any authenticated tenant
    user, matching SLMS360's actual behavior — a staff directory reasonably
    is visible to all of a tenant's own staff.
  - **Ownership checks added everywhere a target user is involved**
    (`manage-staff-member` Gate ability: permission + `$target->tenant_id ===
    $user->tenant_id`) — `activeDirectReports`, `updateAvatar`, and
    `clearDeviceId` had **zero tenant scoping at all** in SLMS360 (real IDOR
    in a multi-tenant world, harmless there only because it's single-tenant
    per deployment). `BelongsToTenant` + RLS already make this structurally
    impossible via `User::findOrFail()`/`Rule::exists()->where('tenant_id',
    ...)`, but the Gate check is kept explicit anyway, consistent with
    RolePolicy's pattern.
  - **Validation is tenant-scoped everywhere a foreign id is submitted**:
    `branches.*`, `role_id`, `hierarchy_parent_id`, `user_id`/`exclude_id`,
    and email uniqueness on create/update all use
    `Rule::exists()/Rule::unique()->where('tenant_id', ...)`. SLMS360 mostly
    validated with unscoped `exists:`/`unique:` rules (or no existence check
    at all, e.g. `activeDirectReports`'s `id` param) — fine there
    (single-tenant), a real cross-tenant guessing/collision risk here.
  - Password generation uses `Str::password(16)` instead of SLMS360's
    `mt_rand(10000000, 99999999)` (an 8-digit numeric string) — meaningfully
    stronger, low-risk change made while touching this code anyway.
  - `getMySelectedBranchesId()` (a global helper in SLMS360) is now a private
    method on `StaffController` — the "which branches is the current viewer
    looking at" concept is presentation-layer scoping, not a model concern,
    and didn't need to be global.
  - `getAllHierarchyChildrenIds()`/`hierarchyChildren()` moved onto
    `App\Models\User` largely as-is (same recursive descendant-walk
    algorithm) — tenant safety here comes for free from `BelongsToTenant`'s
    global scope on the relation query, with no extra code needed; verified
    directly in `HrIsolationTest::test_hierarchy_traversal_never_crosses_tenants`.
  - `TeamController` confirms the finding from Phase 1 inventory: a "team" is
    not a stored entity, just `users.team_name` (head) +
    `hierarchy_parent_id` tree — ported as-is, no schema change needed for it
    specifically.
  - No MySQL-specific SQL in any of the four ported files — nothing to
    convert for Postgres.
- **Tests**: `tests/Feature/Hr/{StaffControllerTest,DepartmentControllerTest,
  TeamControllerTest,HrIsolationTest}.php` — permission/ownership enforcement,
  tenant-scoped validation on every foreign id, the branch-scoped staff
  listing, hierarchy traversal isolation, and the isolation harness against
  `Branch`/`Department`/`Designation`/`UserBranch`. 45/45 tests passing
  project-wide after this module.

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
