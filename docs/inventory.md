# Opsyra port inventory — Phase 1

Source: read-only investigation of `_reference/slms360` (tag `v2.7.1`, commit `fa18b472`)
and `_reference/toddlersapp` (branch `master`, commit `a22725a9`). No files in either
reference clone were modified.

> ⚠️ Three findings below **contradicted assumptions in the original scope / ADR-001**
> and were resolved by decision at Checkpoint 1. See
> [Decisions made](#7-decisions-made-at-checkpoint-1).

---

## 1. Stack comparison

| | SLMS360 | ToddlersApp |
|---|---|---|
| Framework | Laravel 11 (`^11.0`), PHP `^8.2` | Laravel 11 (`^11.0`), PHP `^8.2` |
| Auth | `laravel/passport` (OAuth2) | `laravel/passport ^12.0` (OAuth2) |
| Authorization | `spatie/laravel-permission ^6.4`, checked inline (`$user->can(...)`) in controllers. **No `app/Policies`, zero Policy classes.** | `spatie/laravel-permission ^6.4` present but access control mostly runs on a plain `users.role` string column checked inline (`if ($user->role == 'Staff')`). |
| DB (current) | MySQL (`utf8mb4_unicode_ci` — case-insensitive collation) | `DB_CONNECTION` defaults to `sqlite` in repo config; production almost certainly MySQL via uncommitted `.env`. No Postgres config present in either. |
| Frontend | Hybrid: Blade (auth/email/front/payroll-print) **+** Vue 3/Quasar/Pinia SPA at `resources/client-admin` (this is the real admin UI) | Vue 3 SPA via Vite (`vue-router`, `pinia`, `quasar`, `apexcharts`) + some Blade/Alpine/Tailwind for non-SPA pages |
| Mobile/Flutter | None in this repo (staff mobile app is a separate repo, excluded from Phase 1 per your decision) | None in this repo |
| Other notable packages | `owen-it/laravel-auditing`, `barryvdh/laravel-dompdf`, `simplesoftwareio/simple-qrcode`, `stripe/stripe-php`, `kreait/firebase-php`, `league/flysystem-aws-s3-v3` | `stripe/stripe-php`, `barryvdh/laravel-dompdf`, `simplesoftwareio/simple-qrcode` |
| Migrations | 315 files | 233 files |

Same Laravel major version on both sides (11) — no framework-version conflict to resolve for the "same major version as SLMS360" rule.

---

## 2. How each product isolates tenants today

### SLMS360 — no multi-tenancy at all
Confirmed zero `tenant_id`-style column anywhere in `app/` or `database/migrations`.
Model is "one client per deployment": the client is effectively identity-less at the
DB level (deployment IS the client). Staff/org records relate to a `Branch` model for
intra-client branch scoping, and `User.hierarchy_parent_id` encodes reporting lines
(distinct from any tenant concept — don't confuse the two later).

### ToddlersApp — **the ADR-001 tenancy assumption is wrong**
ADR-001 and the task prompt assumed ToddlersApp already runs a single shared
deployment with automatic tenant scoping, a `BelongsToTenant`-style trait, a
tenant-resolution middleware, and a "client code → country endpoint" directory the
app calls before login. Direct investigation found:

- **Real tenant column is `client_id`** (not `tenant_id`), present on 82 tables. It's
  not a FK to a dedicated tenants table — it points at `users.id` for the row whose
  `role == 'Client'`.
- **No global scope, no `BelongsToTenant`-equivalent trait, no `app/Scopes` at all.**
  `addGlobalScope` has zero hits in `app/`.
- **No tenant-resolution middleware.** Only 3 middleware exist
  (`CheckClientSubscriptionMiddleware`, `IsPrimeAdminMiddleware`,
  `IsSuperAdminMiddleware`) — none resolve "current tenant."
  There is **no client-code entry screen and no client-code → endpoint directory** —
  login is `POST /api/client/logIn` against a single **global** `users` table with one
  global email namespace across every client in the deployment.
- **Isolation is manual and ad hoc**, computed inline per controller, e.g.
  (`app/Http/Controllers/Client/ClassRoomController.php:84-88`):
  ```php
  $clientId = $user->parent_id ?? $user->id;
  $classroom = ClassRoom::where('client_id', $clientId)->find($request->class_room_id);
  ```
  repeated wherever a controller author remembered to add it. This is the exact bug
  class ("missed filter → cross-tenant read") the v2.5.0 SLMS360 review flagged —
  ToddlersApp has the same structural exposure, just never audited.
- `branches` (nursery locations) scope under a client via `user_id`; `branch_id`
  appears on ~23 tables as a secondary scope, same pattern as SLMS360's `Branch`.
- `user_companies` is 1:1 business-profile metadata on a Client user — not a join key
  anything else references.

**What this means for Phase 2**: there is no portable resolver, scope, or directory
mechanism to copy from ToddlersApp. What *is* useful and reusable: the proven
column-based isolation model itself (a single scoping column per row, `client_id` →
we rename to `tenant_id`), and the branch-as-sub-tenant pattern both products already
use independently. The middleware, global scope, RLS, and client-code directory all
have to be built new per the architecture section — which the plan already scoped as
Phase 2 build work, so this mainly **corrects the record** on what "port" means for
that line item rather than blocking Phase 2. See
[Decisions needed](#decisions-needed-at-checkpoint-1) for confirmation.

---

## 3. Module inventory

Decision legend: **Port** / **Port w/ changes** / **Exclude** / **Duplicate (compare)**.

### From SLMS360

| Module | Key paths | Educational coupling | Decision | Effort |
|---|---|---|---|---|
| HR staff mgmt, departments/designations, teams | `Http/Controllers/Client/StaffController.php`, `HR/TeamController.php`, `Models/Department.php`, `Models/Designation.php`, `Models/User.php` | None | Port | L |
| Onboarding/offboarding approvals (L2 gate) | `StaffController.php` (`approveOnboarding`/`approveOffboarding`/reject), `Models/StaffOnboardingRequest.php`, `StaffOffboardRequest.php`, `ApprovalHistory.php` | None | Port | M |
| Salaries hub | `Client/Staff/SalaryController.php`, `SalaryClaimController.php`, `Models/UserSalary.php`, `UserSalaryLog.php`, `StaffSalaryPost(Claim).php`, `Services/Payroll/AttendanceDeductionCalculator.php` | None | Port | L |
| Attendance hub | `Client/Attendance/StaffAttendanceController.php`, `HR/Attendance/{Leave,Holiday,Shift}/*`, `Models/StaffAttendance*.php` | None | Port | L |
| HR dashboard / mgmt insights | `Client/ClientDashboardController.php`, `Client/DashboardsController.php` (raw SQL — see §4) | Verify no student KPIs are mixed in during port | Port w/ changes | M |
| RBAC (roles + direct permissions) | `spatie/laravel-permission`, `Client/RoleController.php`, inline checks (e.g. `HRMS-Approval-L2`) | None | Port w/ changes — centralize into Laravel Policies while porting (see §6) | M |
| Staff ID card | **Not found in this backend.** All `idcard`/`badge` hits are for *student* ID cards (excluded/educational). | — | **Build new** — decided at Checkpoint 1 (see §7); no existing logic to adapt, build fresh in Phase 3 | S |
| Setup wizard | `Client/SetupWizardController.php`, `resources/client-admin/pages/Setup/SetupWizard.vue` | Check wizard steps for school-specific steps mixed with org/HR setup | Port w/ changes | M |
| Subscription enforcement | `Http/Middleware/CheckClientSubscriptionMiddleware.php` — reads `is_at_trial`/`subscription_ends_at` **directly off the client's `users` row**, not the `Subscription`/`PortalPlan`/`PortalPlanFeature` models that exist but aren't actually read at runtime | None | Port w/ changes — rework to read Opsyra's platform `subscriptions`/`plans` tables | S–M |
| US federal payroll + tax tables | `Client/Payroll/{GarnishmentOrder,Nec1099,PayrollClassification,PayrollDeduction,Payslip,W2,W4Election,W9Profile,ContractorPayment}Controller.php`, `create_tax_table_cache_tables`, `create_staff_salary_post_{federal,employer}_taxes_table` | None | Port w/ changes — gate behind US-only feature flag | L |
| Excel import/export engine | **No reusable engine exists.** Only real usage is `Client/Student/StudentBulkImportController.php` (educational), implementing `FromArray`/`WithHeadings` inline — no `app/Imports`/`app/Exports` classes anywhere in the codebase | Only working example is educational | Port w/ changes — extract the pattern into a generic reusable engine, rebuild for HR bulk-import (staff, not students) | M |
| Task/Project Management | `Client/TaskManagement/*`, `Client/ProjectManagement/*` (7 controllers), `Models/{TaskManagement,TaskAttachment,TaskComment,TaskLog,ProjectTaskProgress}` | None | **Port** — decided at Checkpoint 1 (see §7); already rebuilt with tenant/branch/permission scoping at v2.7.1 | L |
| Staff Performance, Staff Evaluation, Leads/CRM, Revenue Reporting, Job Portal | No controllers or models exist for any of the other five | Confirmed absent | Exclude (nothing to exclude-cut, already gone) | — |

**Orphaned migrations** (tables for the 5 confirmed-removed modules, unreferenced by
any current code — do not port): `create_staff_performance_warnings_table`,
`create_staff_performance_coaching_cases_table`,
`create_staff_performance_strikes_table`, `create_job_openings_table` (+2 related
job_openings/job_applications migrations), `create_leads_table`,
`create_lead_activities_table`.

**Stray duplicate files** — do not copy, confirm the live one via `routes/` first:
`HR/Attendance/Leave/StaffLeaveController copy.php`, `StaffLeaveControllerOld.php`,
`Staff/SalaryClaimController copy.php`, `Attendance/StaffAttendanceController copy.php`.

### From ToddlersApp

| Module | Key paths | Educational coupling | Decision | Effort |
|---|---|---|---|---|
| Accounts & fees | Models `StudentFeeStructure(Log)`; Controllers `Client/Accounts/{ClientAccountController,ClientRevenueController,ClientStudentAddHocController,ClientStudentFeeSetupController,BatchInvoiceController,UKAdmissionInvoiceController}.php`; `app/Billing/{StudentFeeCheckout,UkMealFeeCalculator,UkCycleFeeCalculator,FeeStructureAdjustmentService,DiscountApplication,BillingPeriodResolver}.php` | **Heavy** — every fee row FKs to `students.id`; proration/discount/meal-plan logic assumes a student | Port w/ changes — strongest fee-engine candidate (discounts, adjustments, batch invoicing, UK admission-invoice flow are non-trivial and worth keeping), but every `student_id` needs generalizing to a generic billable-entity concept ("fees" → "invoicing" per ADR-001) | L |
| Billing (platform/subscription billing) | `Client/Billing/ClientBillingController.php`, `Parent/Billing/{BillingController,PaymentController}.php`; Models `WalletTransaction`, `Subscription`, `PortalPlan(Feature)`, `PortalPlanPaymentDetail` | None — this is Brainstormer billing the client, not the client billing its customers | Port w/ changes — this is the closer analog to Opsyra's *platform* `subscriptions`/`plans` tables, not a tenant-facing module. Keep conceptually separate from "Accounts & fees" above | M |
| "Vouchers" | Same files as Accounts & fees — a "voucher" here is a PDF fee invoice/receipt via `barryvdh/laravel-dompdf`, not a separate subsystem | Heavy (tied to students) | Folded into Accounts & fees generalization, not a standalone port | S |
| "Double-entry ledger" | `WalletTransaction` model + `wallet_transactions` table (`type: debit/credit`, running `balance_after`) | None | **Build new** — decided at Checkpoint 1 (see §7); wallet pattern isn't real double-entry bookkeeping, real ledger (chart of accounts, journal entries, AR/AP) is new-build work for a later phase | — |
| Tenant-resolution layer | N/A | — | See §2 — nothing to port beyond the naming lesson; build new in Phase 2 | — |
| Staff/HR-adjacent | `Models/{StaffDesignation,StaffAttendance(Date/Shift/ShiftDay/ShiftTime),StaffLeave,StaffHoliday,StaffQualification,StaffSalaryPost(Claim),StaffAdjustment,UserSalary,UkRotaShift,UkStaffAvailability}`, `Client/StaffController.php` | None apparent | **Duplicate (compare)** — see §5 recommendation below. Don't merge silently. | M (comparison) |

**Educational core to exclude** (ToddlersApp is early-years childcare, not K-12 — its
model names don't match the SLMS360-oriented exclude list verbatim; see §7):
`Student`, `ClassRoom`, guardian/parent relations, `Curriculum`, `Milestone(Area)`,
`Observation(Group/Review)`, `Assessment(Detail)`, `Activity(Area/Library/Plan)`,
`StudentHealthRecord`, `Medication`, `ParentalConsent`, `RsvpEvent(Participant)`,
`NurseryVisit`, `NurserySlot`, `ProgressReport`/`ProgressCriteria(Score)`.
No homework/diary, no timetable/generator, no "houses", no exam/report-cards
(replaced conceptually by `ProgressReport`), no academic sessions/terms — those items
from the original exclude list simply don't exist in this codebase.

---

## 4. MySQL-specific code needing Postgres conversion

### SLMS360
- **Raw SQL** (`DB::raw`/`whereRaw`/`selectRaw` etc., 12 files): `Console/Commands/Client/Staff/{RegenrateStaffSalaryCommand,StaffManualSalaryCommand}.php`, `Client/Accounts/ClientAccountController.php`, `Client/ClientDashboardController.php`, `Client/DashboardsController.php`, `Client/HR/Attendance/Leave/StaffLeaveController.php`, `Client/ProjectManagement/ProjectDashboardController.php`, `Client/StaffController.php`, `Client/StudentFee/FeePostController.php` (educational, skip), `Client/TaskManagement/ClientTaskManagementController.php`, `Front/Vote/VoteController.php`, `Services/Payroll/AttendanceDeductionCalculator.php`. Each needs a manual pass for `DATE_FORMAT`, `GROUP_CONCAT`, etc.
- **`ENUM` columns** (22 migrations total; relevant to port): `create_approval_histories_table` (core to L2 approval flow), `create_staff_attendance_shift_times_table`, `create_tax_table_cache_tables`. Rest belong to educational/excluded modules.
- **JSON functions**: none found — clean.
- **`ON DUPLICATE KEY`**: none found — clean (uses portable `updateOrInsert`).
- **Collation**: connection defaults to `utf8mb4_unicode_ci` (case-insensitive). Any uniqueness/`LIKE` check relying on that needs explicit `LOWER()`/Postgres `ILIKE` handling — flag per-module during its own port pass, not enumerated exhaustively here.

### ToddlersApp
- **`ENUM` columns**: 13 migration files — needs conversion (check constraint or native Postgres enum type).
- **`DB::raw`**: only 3 files — `Client/CustomList/StaffEvacuationListController.php`, `Client/Occupancy/OccupancySlotController.php`, `Client/V2/DashboardController.php`.
- **JSON columns**: 6 migrations use `$table->json(...)` (schema columns; ignore `response()->json()` false positives).
- **`ON DUPLICATE KEY` / raw upsert**: none found — uses `updateOrCreate`, DB-agnostic.
- **Collation**: no concerning hits in this pass; worth a second look per-module but not urgent.

Overall ToddlersApp's MySQL-specific surface is smaller than SLMS360's relative to
migration count — good sign for that side of the port.

---

## 5. HR/staff module overlap — recommendation (not silently merged)

Both products have a staff/HR layer. Per the task's explicit rule, here is a
recommendation rather than a silent merge:

**Base: SLMS360's HR core.** It has full RBAC, the L2 approval gate, a proper
onboarding/offboarding workflow, and a dedicated HR/management dashboard — this
matches the task's own instruction to do "RBAC and HR core first." ToddlersApp's
staff module has none of that structure.

**Cherry-pick from ToddlersApp during the Attendance Hub port**: `UkRotaShift`,
`UkStaffAvailability`, and the shift/rota-oriented attendance model are more suited
to hourly/shift-based staffing (relevant for small-business retail/hospitality-style
tenants) than SLMS360's model, which reads as salaried/office-oriented. Recommend
folding these in as an *extension* of SLMS360's attendance hub during Phase 3, not a
separate module — revisit with a concrete comparison once that module's port starts.

---

## 6. Cross-module coupling / other gotchas

- **SLMS360 authorization is almost entirely inline** (`$user->can(...)` scattered
  through controllers), with zero Laravel Policy classes and only 2 middleware files
  total. This is very likely the root cause class behind the v2.5.0 security
  findings, and directly matters for the task's rule "every action checks
  role/permission and ownership/branch, not just authentication." Porting is the
  opportunity to centralize into Policies instead of replicating the inline pattern.
- **SLMS360's `Subscription`/`PortalPlan(Feature)` models exist but aren't read by
  the enforcement middleware at runtime** — don't assume those models reflect real
  behavior; the real logic lives in `CheckClientSubscriptionMiddleware`.
- **SLMS360's admin UI is a Vue/Quasar SPA** (`resources/client-admin`), not plain
  Blade — porting HR modules means porting matching SPA pages/components too.
- **ToddlersApp's isolation is 100% manual/inline** (see §2) — same bug class as
  SLMS360's v2.5.0 findings, never audited. Nothing to specifically avoid copying
  (we're not porting the isolation code, just the domain logic), but a reminder that
  *none* of the inline "is this the right client?" checks in either source should be
  trusted as correct — re-derive authorization from scratch against `BelongsToTenant`
  + Policies for every ported controller.
- **ToddlersApp's Accounts & Fees module is tightly coupled to `Student`** — every fee
  row assumes a student exists with enrollment/admission dates, funding allocations,
  discount presets. Generalizing "fees" to "invoicing" per ADR-001 means either
  building a generic billable-entity abstraction, or accepting nursery-specific
  fields ride along unused for non-childcare tenants. Flagged as a product decision,
  not resolved here.

---

## 7. Decisions made at Checkpoint 1

1. **Task/Project Management (SLMS360)** — **Port.** Already rebuilt at v2.7.1 with
   tenant/branch/permission scoping per the tag message closing the IDOR gaps that
   led to its original removal; treated as reusable admin scope rather than
   excluded. Add to the module inventory as **Port** (was previously listed as
   excluded/flagged above — table left as-is for provenance, this entry is the
   authoritative decision). Effort: L (7 controllers, tenant/branch scoping to
   verify and adapt, own frontend pages).
2. **Staff ID card** — **Build new later.** No existing backend logic to adapt in
   either source; build fresh in Phase 3 when that HR sub-feature comes up. No
   reference pull needed.
3. **"Double-entry ledger"** — **Build a real double-entry ledger.** ToddlersApp's
   wallet-transaction pattern is not reused for tenant bookkeeping; this is
   new-build work (chart of accounts, journal entries, AR/AP) scheduled for a
   later phase, not a Phase 3 port item pulled from either source. The
   wallet-transaction pattern may still inform *platform* billing (Brainstormer
   billing the tenant), which is a separate concern — see §3 "Billing
   (platform/subscription billing)" row.
