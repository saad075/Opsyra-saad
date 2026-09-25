# Decisions log

Running log of choices made during the Opsyra port. Newest entries at the top.

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
